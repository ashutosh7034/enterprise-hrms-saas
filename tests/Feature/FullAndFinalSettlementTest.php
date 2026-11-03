<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineRecoveryRecord;
use App\Models\FnfSettlement;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\FullAndFinalSettlementService;
use App\Services\LoanManagementService;
use Tests\TestCase;

class FullAndFinalSettlementTest extends TestCase
{
    protected Company $company;
    protected User $hrUser;
    protected User $hoAdmin;
    protected Employee $employee;
    protected EmployeeSalaryAssignment $assignment;
    protected FullAndFinalSettlementService $fnfService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();

        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::where('email', 'hr@vishal.com')->first();
        $this->hoAdmin = User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first()
            ?? User::where('email', 'admin@vishal.com')->first();

        $location = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $department = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $designation = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->company->id))->first();
        $category = Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $rand = rand(10000, 99999);
        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'category_id' => $category->id,
            'employee_code' => 'EMP-FNF-' . $rand,
            'first_name' => 'Vikram',
            'last_name' => 'Singhania',
            'gender' => 'Male',
            'phone' => '93' . $rand . '777',
            'date_of_birth' => '1985-05-10',
            'date_of_joining' => '2019-01-01', // 7+ years of service
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'vikram.' . $rand . '@vishal.com',
        ]);

        $structure = SalaryStructure::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'STD-FNF-TEST'],
            ['name' => 'Standard FNF Test Structure', 'currency' => 'INR', 'is_active' => true]
        );

        $this->assignment = EmployeeSalaryAssignment::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'salary_structure_id' => $structure->id,
            'ctc_annual' => 720000.00,
            'gross_monthly' => 60000.00,
            'component_values_json' => [
                'BASIC' => 30000.00,
                'HRA' => 15000.00,
                'DA' => 6000.00,
                'SPECIAL' => 9000.00,
            ],
            'effective_from_date' => '2026-01-01',
            'status' => 'Approved',
        ]);

        // Add 10 Earned Leave / PL balance
        $plType = LeaveType::where('company_id', $this->company->id)->where('code', 'PL')->first()
            ?? LeaveType::first();
        if ($plType) {
            LeaveBalance::updateOrCreate(
                [
                    'company_id' => $this->company->id,
                    'employee_id' => $this->employee->id,
                    'leave_type_id' => $plType->id,
                    'year' => 2026,
                ],
                [
                    'opening_balance' => 10.0,
                    'accrued_balance' => 0.0,
                    'consumed_balance' => 0.0,
                    'available_balance' => 10.0,
                ]
            );
        }

        $this->fnfService = app(FullAndFinalSettlementService::class);
    }

    public function test_fnf_draft_preparation_with_earnings_and_deductions(): void
    {
        // 1. Setup an active loan with outstanding balance
        $loanService = app(LoanManagementService::class);
        $loan = $loanService->applyLoan($this->employee, [
            'loan_type' => 'Emergency Medical Advance',
            'principal_amount' => 12000.00,
            'tenure_months' => 4,
            'interest_rate_percent' => 0.00,
            'reason' => 'Emergency advance',
        ]);
        $loanService->approveLoan($loan, $this->hoAdmin, 'HR');
        $loanService->disburseLoan($loan, '2026-03-31');

        // 2. Setup an asset damage fine
        $assessment = \App\Models\AssetDamageAssessment::first();
        FineRecoveryRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'damage_assessment_id' => $assessment?->id ?? 1,
            'installment_number' => 1,
            'amount' => 1500.00,
            'payment_channel' => 'Payroll Deduction Queue',
            'status' => 'Queued for Phase 5 Payroll',
            'remarks' => 'Damaged instrument screen',
        ]);

        // 3. Prepare Draft Settlement
        $settlement = $this->fnfService->prepareDraftSettlement($this->employee, [
            'resignation_date' => '2026-04-01',
            'last_working_date' => '2026-04-20',
            'notice_period_required_days' => 30,
            'notice_period_served_days' => 19,
            'unpaid_salary_days' => 20,
            'leave_encashment_days' => 10,
            'force_gratuity' => true,
        ], $this->hrUser);

        $this->assertNotNull($settlement);
        $this->assertStringStartsWith('FNF-VE-2026-', $settlement->settlement_number);
        $this->assertEquals('Draft', $settlement->status);

        // Earnings Assertions:
        // Unpaid salary 20 days: 60000 / 30 * 20 = 40,000.00
        $this->assertEquals(40000.00, (float) $settlement->unpaid_salary_amount);
        // Leave encashment 10 days: 30000 (basic) / 30 * 10 = 10,000.00
        $this->assertEquals(10000.00, (float) $settlement->leave_encashment_amount);
        // Gratuity (tenure 7 years): 15 * 30000 * 7 / 26 = 1,21,153.85
        $this->assertGreaterThan(0.00, (float) $settlement->gratuity_amount);

        // Deductions Assertions:
        // Notice shortfall 11 days (30 - 19): 11 * 2000 = 22,000.00
        $this->assertEquals(22000.00, (float) $settlement->notice_shortfall_deduction);
        // Loan deduction: 12000.00
        $this->assertEquals(12000.00, (float) $settlement->loan_outstanding_deduction);
        // Asset fine: 1500.00
        $this->assertEquals(1500.00, (float) $settlement->asset_damage_fine_deduction);

        // Verify Components
        $components = $settlement->components;
        $this->assertGreaterThanOrEqual(4, $components->count());
        $categories = $components->pluck('component_category')->unique()->all();
        $this->assertContains('Earning', $categories);
        $this->assertContains('Deduction', $categories);
    }

    public function test_fnf_multi_tier_approval_workflow(): void
    {
        // Setup loan and fine to verify closure on settlement
        $loanService = app(LoanManagementService::class);
        $loan = $loanService->applyLoan($this->employee, [
            'loan_type' => 'Salary Advance',
            'principal_amount' => 8000.00,
            'tenure_months' => 2,
            'interest_rate_percent' => 0.00,
        ]);
        $loanService->approveLoan($loan, $this->hoAdmin, 'HR');
        $loanService->disburseLoan($loan, '2026-04-01');

        $assessment = \App\Models\AssetDamageAssessment::first();
        $fine = FineRecoveryRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'damage_assessment_id' => $assessment?->id ?? 1,
            'installment_number' => 1,
            'amount' => 800.00,
            'payment_channel' => 'Payroll Deduction Queue',
            'status' => 'Queued for Phase 5 Payroll',
            'remarks' => 'Plant locker key replacement',
        ]);

        $settlement = $this->fnfService->prepareDraftSettlement($this->employee, [
            'resignation_date' => '2026-04-01',
            'last_working_date' => '2026-04-30',
            'notice_period_required_days' => 30,
            'notice_period_served_days' => 30,
            'unpaid_salary_days' => 30,
        ], $this->hrUser);

        // 1. HR Verification
        $verifyResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.fnf.verify', $settlement));
        $verifyResponse->assertRedirect();
        $settlement->refresh();
        $this->assertEquals('HR Verified', $settlement->status);

        // 2. Finance / HO Admin Approval
        $approveResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.fnf.approve', $settlement));
        $approveResponse->assertRedirect();
        $settlement->refresh();
        $this->assertEquals('Finance Approved', $settlement->status);

        // 3. Payout Settlement
        $settleResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.fnf.settle', $settlement));
        $settleResponse->assertRedirect();
        $settlement->refresh();
        $this->assertEquals('Settled', $settlement->status);
        $this->assertNotNull($settlement->settled_at);

        // Verify Employee marked Resigned
        $this->employee->refresh();
        $this->assertEquals('Resigned', $this->employee->status);

        // Verify Loan marked Closed
        $loan->refresh();
        $this->assertEquals('Closed', $loan->status);
        $this->assertEquals(0.00, (float) $loan->remaining_balance);

        // Verify Fine marked Paid
        $fine->refresh();
        $this->assertEquals('Paid', $fine->status);
        $this->assertStringStartsWith('FNF-', $fine->receipt_reference);
    }

    public function test_fnf_statement_pdf_download(): void
    {
        $settlement = $this->fnfService->prepareDraftSettlement($this->employee, [
            'resignation_date' => '2026-04-01',
            'last_working_date' => '2026-04-30',
            'unpaid_salary_days' => 30,
        ], $this->hrUser);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('payroll.fnf.download', $settlement));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('FNF-Statement-' . $settlement->settlement_number . '.pdf', $response->headers->get('Content-Disposition'));

        $binary = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $binary);
    }
}
