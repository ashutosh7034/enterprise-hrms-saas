<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Tests\TestCase;

class LeaveMultiCompanyIsolationTest extends TestCase
{
    protected Company $company1; // VE
    protected Company $company2; // VRE
    protected User $hrUserCompany1;
    protected Employee $employeeCompany2;
    protected LeaveType $leaveTypeCompany2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company1 = Company::where('code', 'VE')->first() ?? Company::first();
        $this->company2 = Company::where('code', 'VRE')->first() ?? Company::where('id', '!=', $this->company1->id)->first();

        $this->hrUserCompany1 = User::where('company_id', $this->company1->id)
            ->whereHas('role', fn($q) => $q->where('name', 'hr_executive'))
            ->first() ?? User::where('company_id', $this->company1->id)->first();

        $this->employeeCompany2 = Employee::withoutGlobalScopes()->where('company_id', $this->company2->id)->first();
        $this->leaveTypeCompany2 = LeaveType::where('company_id', $this->company2->id)->first();
    }

    public function test_cross_company_leave_type_edit_is_blocked(): void
    {
        // HR from Company 1 attempts to access Company 2 leave type
        $response = $this->actingAs($this->hrUserCompany1)
            ->withSession(['active_company_id' => $this->company1->id])
            ->get(route('leaves.types.edit', $this->leaveTypeCompany2->id));

        $response->assertStatus(404);
    }

    public function test_cross_company_application_submission_is_blocked(): void
    {
        // HR of Company 1 submits application for Employee of Company 2
        $response = $this->actingAs($this->hrUserCompany1)
            ->withSession(['active_company_id' => $this->company1->id])
            ->post(route('leaves.applications.store'), [
                'employee_id' => $this->employeeCompany2->id,
                'leave_type_id' => $this->leaveTypeCompany2->id,
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-20',
                'reason' => 'Cross company attack attempt',
            ]);

        $response->assertStatus(404);
    }

    public function test_leave_balances_screen_only_renders_active_company_records(): void
    {
        $response = $this->actingAs($this->hrUserCompany1)
            ->withSession(['active_company_id' => $this->company1->id])
            ->get(route('leaves.balances.index'));

        $response->assertStatus(200);
        
        // Assert that Company 2 employee code is not rendered on Company 1 balance screen
        $response->assertDontSee($this->employeeCompany2->employee_code);
    }
}

