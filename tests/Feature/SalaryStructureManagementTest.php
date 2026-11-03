<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\SalaryComponent;
use App\Models\SalaryRevisionHistory;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\SalaryStructureService;
use Tests\TestCase;

class SalaryStructureManagementTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected User $hrUser;
    protected User $essUser;
    protected SalaryStructureService $structureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->essUser = User::whereHas('role', fn($q) => $q->where('name', 'employee_ess'))->first()
            ?? User::first();
        $this->structureService = app(SalaryStructureService::class);
        $this->structureService->ensureStandardComponents($this->company);
    }

    public function test_can_create_salary_structure_with_components(): void
    {
        $code = 'STR-TEST-' . mt_rand(1000, 9999);
        $basic = SalaryComponent::where('company_id', $this->company->id)->where('code', 'BASIC')->first();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.structures.store'), [
                'code' => $code,
                'name' => 'Testing Executive Structure',
                'description' => 'Structure for engineering personnel',
                'is_target_ot' => 0,
                'components' => [
                    [
                        'salary_component_id' => $basic->id,
                        'calculation_type' => 'Fixed Amount',
                        'default_amount' => 25000.00,
                    ],
                ],
            ]);

        $response->assertRedirect(route('payroll.structures.index'));
        $this->assertDatabaseHas('salary_structures', [
            'company_id' => $this->company->id,
            'code' => $code,
            'name' => 'Testing Executive Structure',
        ]);
    }

    public function test_assign_salary_structure_and_verify_revision_history(): void
    {
        $structure = SalaryStructure::where('company_id', $this->company->id)->first();

        // 1. Initial Assignment
        $assignment1 = $this->structureService->assignSalaryStructure(
            $this->employee,
            $structure,
            [
                'effective_from_date' => '2026-01-01',
                'gross_monthly' => 40000.00,
                'ctc_annual' => 480000.00,
                'component_values' => ['BASIC' => 20000.00, 'HRA' => 10000.00, 'SPECIAL' => 10000.00],
                'is_pf_eligible' => true,
                'is_esi_eligible' => false,
            ],
            $this->hrUser,
            autoApprove: true
        );

        $this->assertEquals('Approved', $assignment1->status);
        $this->assertEquals(40000.00, (float) $assignment1->gross_monthly);

        // 2. Revision Assignment (Increment to 50,000)
        $assignment2 = $this->structureService->assignSalaryStructure(
            $this->employee,
            $structure,
            [
                'effective_from_date' => '2026-07-01',
                'gross_monthly' => 50000.00,
                'ctc_annual' => 600000.00,
                'component_values' => ['BASIC' => 25000.00, 'HRA' => 12500.00, 'SPECIAL' => 12500.00],
                'is_pf_eligible' => true,
                'is_esi_eligible' => false,
                'revision_notes' => 'Mid-year merit increment',
            ],
            $this->hrUser,
            autoApprove: true
        );

        // Check assignment1 is superseded
        $assignment1->refresh();
        $this->assertEquals('Superseded', $assignment1->status);
        $this->assertEquals('2026-06-30', $assignment1->effective_to_date->toDateString());

        // Check assignment2 is active
        $this->assertEquals('Approved', $assignment2->status);

        // Verify revision history record was created
        $this->assertDatabaseHas('salary_revision_histories', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'previous_assignment_id' => $assignment1->id,
            'new_assignment_id' => $assignment2->id,
            'old_ctc_annual' => 480000.00,
            'new_ctc_annual' => 600000.00,
            'increment_percentage' => 25.00,
        ]);
    }

    public function test_unauthorized_user_cannot_access_or_modify_salary_structures(): void
    {
        $response = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('payroll.structures.index'));

        $response->assertStatus(403);
    }
}

