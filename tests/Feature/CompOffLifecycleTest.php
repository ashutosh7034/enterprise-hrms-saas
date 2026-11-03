<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\CompOffRecord;
use App\Models\Employee;
use App\Models\User;
use App\Services\CompOffService;
use Carbon\Carbon;
use Tests\TestCase;

class CompOffLifecycleTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected User $hrUser;
    protected CompOffService $compOffService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->compOffService = app(CompOffService::class);
    }

    public function test_can_claim_comp_off_for_qualifying_weekly_off_duty(): void
    {
        $uniqueDate = '2026-05-' . sprintf('%02d', mt_rand(1, 28));
        AttendanceRecord::where('employee_id', $this->employee->id)->where('attendance_date', $uniqueDate)->delete();

        // Create verified attendance record for working 500 mins on Sunday (Weekly Off)
        $attendance = AttendanceRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => $uniqueDate,
            'status' => 'Weekly Off',
            'total_punches' => 2,
            'total_work_minutes' => 500,
            'actual_worked_minutes' => 500,
            'is_reconciled' => true,
        ]);

        $result = $this->compOffService->requestCompOff(
            employee: $this->employee,
            attendance: $attendance,
            remarks: 'Emergency plant maintenance shift'
        );

        $this->assertEquals('Success', $result['status']);
        $this->assertNotNull($result['record']);

        $record = $result['record'];
        $this->assertEquals(1.00, (float) $record->credited_days);
        $this->assertEquals('Pending Approval', $record->status);
        $this->assertEquals(Carbon::parse($uniqueDate)->addDays(60)->toDateString(), $record->expiry_date->toDateString());

        // Test duplicate claim prevention
        $duplicateResult = $this->compOffService->requestCompOff(
            employee: $this->employee,
            attendance: $attendance
        );
        $this->assertEquals('Duplicate', $duplicateResult['status']);
    }

    public function test_ineligible_attendance_duration_is_rejected(): void
    {
        $uniqueDate = Carbon::parse('2026-07-05')->addDays(mt_rand(1, 50))->toDateString();
        AttendanceRecord::where('employee_id', $this->employee->id)->where('attendance_date', $uniqueDate)->delete();

        // 120 mins worked (less than minimum 240 mins)
        $attendance = AttendanceRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => $uniqueDate,
            'status' => 'Weekly Off',
            'total_punches' => 2,
            'total_work_minutes' => 120,
            'actual_worked_minutes' => 120,
        ]);

        $result = $this->compOffService->requestCompOff(
            employee: $this->employee,
            attendance: $attendance
        );

        $this->assertEquals('Ineligible', $result['status']);
    }

    public function test_hr_approval_credits_comp_off_balance(): void
    {
        $uniqueDate = '2026-09-' . sprintf('%02d', mt_rand(1, 28));
        AttendanceRecord::where('employee_id', $this->employee->id)->where('attendance_date', $uniqueDate)->delete();

        $attendance = AttendanceRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'attendance_date' => $uniqueDate,
            'status' => 'Weekly Off',
            'total_punches' => 2,
            'total_work_minutes' => 480,
            'actual_worked_minutes' => 480,
        ]);

        $requestResult = $this->compOffService->requestCompOff($this->employee, $attendance);
        $record = $requestResult['record'];

        $approvalResult = $this->compOffService->approveCompOff($record, $this->hrUser, 'Approved by Plant HR');
        $this->assertEquals('Success', $approvalResult['status']);

        $record->refresh();
        $this->assertEquals('Approved', $record->status);
        $this->assertNotNull($record->approved_at);

        // Verify balance ledger transaction exists
        $this->assertDatabaseHas('leave_balance_transactions', [
            'employee_id' => $this->employee->id,
            'transaction_type' => 'Comp-Off Credit',
            'credit_days' => 1.00,
        ]);
    }
}
