<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\RegularizationService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceRegularizationTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected User $hrUser;
    protected User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->managerUser = User::whereHas('role', fn($q) => $q->where('name', 'manager_mss'))->first()
            ?? $this->hrUser;
    }

    public function test_can_apply_for_attendance_regularization_with_mandatory_reason(): void
    {
        $pastDate = now()->subDays(mt_rand(25, 80))->toDateString();

        AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('attendance_date', $pastDate)
            ->delete();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('regularization.store'), [
                'employee_id' => $this->employee->id,
                'attendance_date' => $pastDate,
                'regularization_type' => 'Missing OUT Punch',
                'requested_in_time' => "{$pastDate} 09:00:00",
                'requested_out_time' => "{$pastDate} 18:00:00",
                'reason' => 'Left factory floor urgently for client site emergency breakdown visit.',
            ]);

        $response->assertRedirect();

        $reg = AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('attendance_date', $pastDate)
            ->first();

        $this->assertNotNull($reg);
        $this->assertEquals('Pending Manager', $reg->status);
        $this->assertEquals('Missing OUT Punch', $reg->regularization_type);
    }

    public function test_future_date_regularization_is_strictly_rejected(): void
    {
        $futureDate = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('regularization.store'), [
                'employee_id' => $this->employee->id,
                'attendance_date' => $futureDate,
                'regularization_type' => 'Late Arrival',
                'reason' => 'Will be late day after tomorrow',
            ]);

        $response->assertSessionHasErrors('attendance_date');
    }

    public function test_manager_review_and_hr_final_approval_updates_attendance_muster(): void
    {
        $targetDate = now()->subDays(3)->toDateString();

        AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('attendance_date', $targetDate)
            ->delete();

        $regService = app(RegularizationService::class);

        // Submit regularization
        $reg = $regService->applyRegularization(
            companyId: $this->company->id,
            data: [
                'employee_id' => $this->employee->id,
                'attendance_date' => $targetDate,
                'regularization_type' => 'Business Travel / On Duty',
                'requested_in_time' => "{$targetDate} 09:00:00",
                'requested_out_time' => "{$targetDate} 18:00:00",
                'reason' => 'Annual equipment calibration audit in Mumbai.',
            ],
            applicant: $this->hrUser
        );

        $this->assertEquals('Pending Manager', $reg->status);

        // Manager Review Pass
        $regService->managerReview($reg, $this->managerUser, 'Approve', 'Confirmed site movement voucher');
        $this->assertEquals('Pending HR', $reg->status);

        // HR Final Approval
        $regService->hrApproval($reg, $this->hrUser, 'Approve', 'Approved against client intimation log');
        $this->assertEquals('Approved', $reg->status);

        // Check AttendanceRecord is now On Duty
        $record = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('attendance_date', $targetDate)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals('On Duty', $record->status);
        $this->assertGreaterThanOrEqual(480, $record->actual_worked_minutes);

        // Check Audit Log entry created
        $audit = AuditLog::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('auditable_type', AttendanceRegularization::class)
            ->where('auditable_id', $reg->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('REGULARIZATION_APPROVED', $audit->action);
    }
}
