<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineRecoveryRecord;
use App\Models\Location;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\LoanManagementService;
use App\Services\PayrollConfigurationService;
use App\Services\PayrollPreValidationService;
use App\Services\PayrollProcessingService;
use Tests\TestCase;

class PayrollProcessingAndWorkflowTest extends TestCase
{
    protected Company $company;
    protected User $hrUser;
    protected User $hoAdmin;
    protected Employee $employee;
    protected EmployeeSalaryAssignment $assignment;
    protected PayrollProcessingService $processingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        app(PayrollConfigurationService::class)->ensureDefaultPtSlabs($this->company->id);

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
            'employee_code' => 'EMP-PROC-' . $rand,
            'first_name' => 'Sanjay',
            'last_name' => 'Verma',
            'gender' => 'Male',
            'phone' => '97' . $rand . '222',
            'date_of_birth' => '1988-06-20',
            'date_of_joining' => '2021-08-01',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'sanjay.' . $rand . '@vishal.com',
        ]);

        $structure = SalaryStructure::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'STD-PROC-TEST'],
            ['name' => 'Standard Proc Test Structure', 'currency' => 'INR', 'is_active' => true]
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
            'is_pf_eligible' => true,
            'is_esi_eligible' => false,
        ]);

        $this->processingService = app(PayrollProcessingService::class);
    }

    public function test_pre_validation_gate_identifies_eligibility(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.pre-validate'), [
                'year' => 2026,
                'month' => 4,
            ]);

        $response->assertOk();
        $response->assertViewIs('payroll.runs.pre_validate');
        $response->assertViewHas('results');

        $results = $response->viewData('results');
        $this->assertArrayHasKey('eligible_employees', $results);
        $this->assertArrayHasKey('ineligible_employees', $results);
        $this->assertGreaterThanOrEqual(1, $results['eligible_count']);
    }

    public function test_payroll_run_execution_and_itemized_transactions(): void
    {
        $year = 2026;
        $month = 4;

        PayrollRun::where('company_id', $this->company->id)->where('year', $year)->where('month', $month)->delete();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.execute'), [
                'year' => $year,
                'month' => $month,
            ]);

        $response->assertRedirect();

        $run = PayrollRun::where('company_id', $this->company->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $this->assertNotNull($run);
        $this->assertStringStartsWith('PAY-VE-202604-', $run->run_batch_number);
        $this->assertEquals('Pre-Validation Passed', $run->status);
        $this->assertGreaterThan(0, $run->total_processed_employees);
        $this->assertGreaterThan(0.00, (float) $run->total_gross_earnings);
        $this->assertGreaterThan(0.00, (float) $run->total_net_payout);

        // Verify run employee and transactions
        $runEmployee = PayrollRunEmployee::where('payroll_run_id', $run->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($runEmployee);
        $this->assertEquals(60000.00, (float) $runEmployee->gross_earnings);
        $this->assertNotEmpty($runEmployee->componentTransactions);
        $this->assertDatabaseHas('payroll_component_transactions', [
            'payroll_run_employee_id' => $runEmployee->id,
            'component_code' => 'BASIC',
            'earned_or_deducted_amount' => 30000.00,
        ]);
    }

    public function test_duplicate_payroll_run_prevention(): void
    {
        $year = 2026;
        $month = 5;

        // Clean up any existing runs from previous test runs
        PayrollRun::where('company_id', $this->company->id)->where('year', $year)->where('month', $month)->delete();

        // 1. First execution
        $run = $this->processingService->executePayrollRun($this->company, $year, $month, $this->hrUser);
        $this->assertNotNull($run);

        // 2. Approve run
        $this->processingService->approvePayrollRun($run, $this->hoAdmin, 'Approved for testing duplicate block');

        // 3. Second execution attempt must fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Duplicate execution is prohibited');

        $this->processingService->executePayrollRun($this->company, $year, $month, $this->hrUser);
    }

    public function test_payroll_approval_workflow_and_deduction_booking(): void
    {
        $year = 2026;
        $month = 6;

        PayrollRun::where('company_id', $this->company->id)->where('year', $year)->where('month', $month)->delete();

        // 1. Setup a Loan with EMI due in June 2026
        $loanService = app(LoanManagementService::class);
        $loan = $loanService->applyLoan($this->employee, [
            'loan_type' => 'Emergency Medical Advance',
            'principal_amount' => 18000.00,
            'tenure_months' => 3,
            'interest_rate_percent' => 0.00,
            'reason' => 'Emergency hospitalization',
        ]);
        $loanService->approveLoan($loan, $this->hoAdmin, 'HR');
        $loanService->disburseLoan($loan, '2026-05-31'); // 1st EMI due June 2026

        // 2. Setup a fine recovery queued for Phase 5
        $assessment = \App\Models\AssetDamageAssessment::first();
        $fine = FineRecoveryRecord::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'damage_assessment_id' => $assessment?->id ?? 1,
            'installment_number' => 1,
            'amount' => 1500.00,
            'payment_channel' => 'Payroll Deduction Queue',
            'status' => 'Queued for Phase 5 Payroll',
            'remarks' => 'Scratched inspection tablet screen',
        ]);

        // 3. Execute Run
        $run = $this->processingService->executePayrollRun($this->company, $year, $month, $this->hrUser);

        // 4. Submit for Approval
        $submitResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.submit', $run), [
                'remarks' => 'June payroll submitted with loan and fine deductions',
            ]);
        $submitResponse->assertRedirect();
        $run->refresh();
        $this->assertEquals('Pending Approval', $run->status);

        // 5. HO Admin Approves
        $approveResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.approve', $run), [
                'remarks' => 'Approved June payout',
            ]);
        $approveResponse->assertRedirect();
        $run->refresh();
        $this->assertEquals('Approved', $run->status);
        $this->assertEquals($this->hoAdmin->id, $run->approved_by);

        // Verify Loan EMI is deducted and loan balance updated
        $schedule = EmployeeLoanEmiSchedule::where('employee_loan_id', $loan->id)
            ->where('due_year', 2026)
            ->where('due_month', 6)
            ->first();
        $this->assertEquals('Deducted in Payroll', $schedule->status);
        $this->assertEquals($run->id, $schedule->payroll_run_id);

        $loan->refresh();
        $this->assertEquals(6000.00, (float) $loan->total_recovered);
        $this->assertEquals(12000.00, (float) $loan->remaining_balance);

        // Verify Fine is marked Paid
        $fine->refresh();
        $this->assertEquals('Paid', $fine->status);
        $this->assertStringContainsString('PAYROLL-', $fine->receipt_reference);

        // 6. Lock Payroll
        $lockResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.lock', $run));
        $lockResponse->assertRedirect();
        $run->refresh();
        $this->assertEquals('Locked', $run->status);

        // 7. Disburse Payroll
        $disburseResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.disburse', $run), [
                'payment_reference' => 'NEFT-VE-20260630-9988',
            ]);
        $disburseResponse->assertRedirect();
        $run->refresh();
        $this->assertEquals('Disbursed', $run->status);
        $empPay = $run->payrollEmployees()->first();
        $this->assertEquals('Paid', $empPay->payment_status);
        $this->assertEquals('NEFT-VE-20260630-9988', $empPay->payment_reference);
    }

    public function test_payroll_run_rejection_requires_mandatory_remarks(): void
    {
        $year = 2026;
        $month = 7;

        PayrollRun::where('company_id', $this->company->id)->where('year', $year)->where('month', $month)->delete();

        $run = $this->processingService->executePayrollRun($this->company, $year, $month, $this->hrUser);

        // 1. Rejection without remarks fails validation
        $failResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.reject', $run), [
                'remarks' => '',
            ]);
        $failResponse->assertSessionHasErrors(['remarks']);

        // 2. Rejection with remarks succeeds and updates status
        $successResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.runs.reject', $run), [
                'remarks' => 'Attendance discrepancies found in Bhosari plant site',
            ]);
        $successResponse->assertRedirect();

        $run->refresh();
        $this->assertEquals('Rejected', $run->status);
        $this->assertDatabaseHas('payroll_approval_steps', [
            'payroll_run_id' => $run->id,
            'action' => 'Rejected',
            'remarks' => 'Attendance discrepancies found in Bhosari plant site',
        ]);
    }
}
