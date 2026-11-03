<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Tests\TestCase;

class LeaveTypeManagementTest extends TestCase
{
    protected Company $company;
    protected User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
    }

    public function test_can_create_new_leave_type_with_company_scope(): void
    {
        $code = 'TEST_' . mt_rand(100, 999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('leaves.types.store'), [
                'code' => $code,
                'name' => 'Special Project Leave',
                'description' => 'Discretionary leave for special project delivery.',
                'is_paid' => 1,
                'annual_entitlement' => 6.00,
                'monthly_accrual_rate' => 0.50,
                'accrual_frequency' => 'Monthly',
                'accrual_timing' => 'Start of Month',
                'is_accrual_enabled' => 1,
                'requires_confirmation' => 0,
                'max_consecutive_days' => 5,
                'min_notice_days' => 2,
                'max_backdated_days' => 1,
                'is_carry_forward_enabled' => 1,
                'carry_forward_limit' => 3.00,
                'is_sandwich_enabled' => 1,
                'is_compoff' => 0,
                'is_applicable_in_notice_period' => 0,
                'effective_from' => '2026-01-01',
                'status' => 'Active',
            ]);

        $response->assertRedirect(route('leaves.types.index'));
        $this->assertDatabaseHas('leave_types', [
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => 'Special Project Leave',
            'is_paid' => 1,
        ]);
    }

    public function test_duplicate_leave_code_in_same_company_is_rejected(): void
    {
        $existing = LeaveType::where('company_id', $this->company->id)->first();
        $this->assertNotNull($existing);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('leaves.types.store'), [
                'code' => $existing->code,
                'name' => 'Duplicate Attempt',
                'annual_entitlement' => 10.00,
                'monthly_accrual_rate' => 1.00,
                'accrual_frequency' => 'Monthly',
                'accrual_timing' => 'Start of Month',
                'effective_from' => '2026-01-01',
                'status' => 'Active',
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_inactive_leave_type_is_not_applicable_to_employee(): void
    {
        $type = LeaveType::create([
            'company_id' => $this->company->id,
            'code' => 'INACT_' . mt_rand(100, 999),
            'name' => 'Decommissioned Leave',
            'is_paid' => true,
            'annual_entitlement' => 5.00,
            'monthly_accrual_rate' => 0.00,
            'accrual_frequency' => 'None',
            'accrual_timing' => 'Start of Month',
            'effective_from' => '2026-01-01',
            'status' => 'Inactive',
        ]);

        $employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->assertFalse($type->isApplicableToEmployee($employee));
    }
}

