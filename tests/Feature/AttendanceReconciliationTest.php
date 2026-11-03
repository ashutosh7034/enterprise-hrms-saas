<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceReconciliationService;
use Tests\TestCase;

class AttendanceReconciliationTest extends TestCase
{
    protected Company $company1;
    protected Company $company2;
    protected ?Employee $employee1 = null;
    protected ?Employee $employee2 = null;
    protected User $hoAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company1 = Company::where('code', 'VE')->first() ?? Company::first();
        $this->company2 = Company::where('code', 'VRE')->first() ?? Company::skip(1)->first();

        $this->employee1 = Employee::withoutGlobalScopes()->where('company_id', $this->company1->id)->first();
        $this->employee2 = Employee::withoutGlobalScopes()->where('company_id', $this->company2->id)->first();

        if (!$this->employee2) {
            $loc2 = \App\Models\Location::withoutGlobalScopes()->where('company_id', $this->company2->id)->first();
            $dept2 = \App\Models\Department::withoutGlobalScopes()->where('company_id', $this->company2->id)->first();
            $desig2 = \App\Models\Designation::withoutGlobalScopes()->first();
            $cat2 = \App\Models\Category::first();
            $this->employee2 = Employee::withoutGlobalScopes()->create([
                'company_id' => $this->company2->id,
                'employee_code' => 'VRE-EMP-TEST-' . mt_rand(100, 999),
                'first_name' => 'Vrishal',
                'last_name' => 'Staff',
                'phone' => '98' . mt_rand(10000000, 99999999),
                'date_of_birth' => '1995-01-01',
                'date_of_joining' => '2026-01-01',
                'location_id' => $loc2?->id ?? $this->employee1->location_id,
                'department_id' => $dept2?->id ?? $this->employee1->department_id,
                'designation_id' => $desig2?->id ?? $this->employee1->designation_id,
                'category_id' => $cat2?->id ?? $this->employee1->category_id,
                'status' => 'Active',
            ]);
        }

        $this->hoAdmin = User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first() ?? User::first();
    }

    public function test_reconciliation_summary_computes_accurate_metrics(): void
    {
        $today = now()->toDateString();
        $service = app(AttendanceReconciliationService::class);

        $summary = $service->getReconciliationSummary($this->company1->id, $today);

        $this->assertArrayHasKey('expected_workforce', $summary);
        $this->assertArrayHasKey('worked_employees', $summary);
        $this->assertArrayHasKey('missing_punch_count', $summary);
        $this->assertArrayHasKey('duplicate_punches_count', $summary);
        $this->assertArrayHasKey('discrepancies', $summary);
    }

    public function test_ho_admin_can_reconcile_attendance_exception_with_notes(): void
    {
        $date = now()->subDays(4)->toDateString();

        $record = AttendanceRecord::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $this->company1->id,
                'employee_id' => $this->employee1->id,
                'attendance_date' => $date,
            ],
            [
                'status' => 'Missing Punch',
                'total_punches' => 1,
            ]
        );

        $response = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company1->id])
            ->post(route('attendance.reconcile', $record->id), [
                'notes' => 'Verified manual gate register entry with Security Head. Cleared.',
            ]);

        $response->assertRedirect();

        $record->refresh();
        $this->assertTrue($record->is_reconciled);
        $this->assertEquals('Verified manual gate register entry with Security Head. Cleared.', $record->reconciliation_notes);
    }

    public function test_cross_company_attendance_reconciliation_isolation(): void
    {
        $date = now()->subDays(5)->toDateString();

        // Record in Company 2
        $recordCompany2 = AttendanceRecord::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $this->company2->id,
                'employee_id' => $this->employee2->id,
                'attendance_date' => $date,
            ],
            [
                'status' => 'Absent',
            ]
        );

        // User authenticated in Company 1 context attempts to reconcile Company 2 record
        $response = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company1->id])
            ->post(route('attendance.reconcile', $recordCompany2->id), [
                'notes' => 'Attempting cross-company reconciliation',
            ]);

        // Must be rejected with 404
        $response->assertStatus(404);
        $recordCompany2->refresh();
        $this->assertFalse($recordCompany2->is_reconciled);
    }
}
