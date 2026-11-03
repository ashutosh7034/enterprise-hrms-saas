<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\Location;
use App\Models\User;
use App\Services\LoanManagementService;
use Tests\TestCase;

class EmployeeLoanManagementTest extends TestCase
{
    protected Company $company;
    protected User $hrUser;
    protected User $managerUser;
    protected User $essUser;
    protected Employee $employee;
    protected LoanManagementService $loanService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();

        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::where('email', 'hr@vishal.com')->first();
        $this->managerUser = User::whereHas('role', fn($q) => $q->where('name', 'manager_mss'))->first()
            ?? User::where('email', 'manager@vishal.com')->first();

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
            'employee_code' => 'EMP-LN-' . $rand,
            'first_name' => 'Kiran',
            'last_name' => 'Deshmukh',
            'gender' => 'Male',
            'phone' => '95' . $rand . '333',
            'date_of_birth' => '1993-09-14',
            'date_of_joining' => '2023-05-10',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'kiran.' . $rand . '@vishal.com',
        ]);

        $essRole = \App\Models\Role::where('name', 'employee_ess')->first();
        $this->essUser = User::create([
            'company_id' => $this->company->id,
            'employee_code' => $this->employee->employee_code,
            'role_id' => $essRole?->id,
            'name' => 'Kiran Deshmukh',
            'email' => 'kiran.user.' . $rand . '@vishal.com',
            'password' => bcrypt('Password@123'),
            'is_active' => true,
        ]);

        $this->loanService = app(LoanManagementService::class);
    }

    public function test_employee_can_apply_for_loan_via_ess(): void
    {
        $response = $this->actingAs($this->essUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.store'), [
                'loan_type' => 'Salary Advance',
                'principal_amount' => 24000.00,
                'tenure_months' => 6,
                'interest_rate_percent' => 0.00,
                'reason' => 'Home renovation advance',
            ]);

        $response->assertRedirect(route('payroll.loans.index'));

        $loan = EmployeeLoan::where('employee_id', $this->employee->id)->latest('id')->first();
        $this->assertNotNull($loan);
        $this->assertEquals(24000.00, (float) $loan->principal_amount);
        $this->assertEquals(6, $loan->tenure_months);
        $this->assertEquals(4000.00, (float) $loan->monthly_emi);
        $this->assertEquals('Applied', $loan->status);
        $this->assertStringStartsWith('LN-VE-', $loan->loan_application_number);
    }

    public function test_multitier_loan_approval(): void
    {
        $loan = $this->loanService->applyLoan($this->employee, [
            'loan_type' => 'Emergency Medical Advance',
            'principal_amount' => 15000.00,
            'tenure_months' => 3,
            'interest_rate_percent' => 0.00,
            'reason' => 'Emergency medical advance',
        ]);

        // 1. Manager Approval
        $mgrResponse = $this->actingAs($this->managerUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.approve', $loan));

        $mgrResponse->assertRedirect();
        $loan->refresh();
        $this->assertEquals('Manager Approved', $loan->status);

        // 2. HR Approval
        $hrResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.approve', $loan));

        $hrResponse->assertRedirect();
        $loan->refresh();
        $this->assertEquals('HR Approved', $loan->status);
        $this->assertEquals($this->hrUser->id, $loan->approved_by);
        $this->assertNotNull($loan->approved_at);
    }

    public function test_loan_disbursement_generates_exact_amortization_schedule(): void
    {
        $loan = $this->loanService->applyLoan($this->employee, [
            'loan_type' => 'Festival Advance',
            'principal_amount' => 20000.00,
            'tenure_months' => 4,
            'interest_rate_percent' => 0.00,
            'reason' => 'Diwali advance',
        ]);
        $this->loanService->approveLoan($loan, $this->hrUser, 'HR');

        $disburseResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.disburse', $loan), [
                'disbursed_date' => '2026-04-10',
            ]);

        $disburseResponse->assertRedirect();
        $loan->refresh();

        $this->assertEquals('Active', $loan->status);
        $this->assertEquals(20000.00, (float) $loan->disbursed_amount);
        $this->assertEquals('2026-04-10', $loan->disbursed_date ? $loan->disbursed_date->format('Y-m-d') : null);

        // Verify exact 4 monthly installments
        $schedules = EmployeeLoanEmiSchedule::where('employee_loan_id', $loan->id)
            ->orderBy('installment_number')
            ->get();

        $this->assertCount(4, $schedules);
        $this->assertEquals(5000.00, (float) $schedules[0]->emi_amount);
        $this->assertEquals(5, $schedules[0]->due_month); // May 2026
        $this->assertEquals(2026, $schedules[0]->due_year);
        $this->assertEquals('Pending', $schedules[0]->status);

        // Sum of all principal components must equal 20,000.00
        $sumPrincipal = $schedules->sum('principal_component');
        $this->assertEquals(20000.00, (float) $sumPrincipal);
    }

    public function test_loan_early_foreclosure(): void
    {
        $loan = $this->loanService->applyLoan($this->employee, [
            'loan_type' => 'Salary Advance',
            'principal_amount' => 10000.00,
            'tenure_months' => 2,
            'interest_rate_percent' => 0.00,
        ]);
        $this->loanService->approveLoan($loan, $this->hrUser, 'HR');
        $this->loanService->disburseLoan($loan, '2026-04-01');

        $forecloseResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.foreclose', $loan), [
                'remarks' => 'Direct cash settlement received at accounts desk',
            ]);

        $forecloseResponse->assertRedirect();
        $loan->refresh();

        $this->assertEquals('Closed', $loan->status);
        $this->assertEquals(0.00, (float) $loan->remaining_balance);

        // Pending EMI schedules must be resolved
        $pendingCount = EmployeeLoanEmiSchedule::where('employee_loan_id', $loan->id)
            ->where('status', 'Pending')
            ->count();
        $this->assertEquals(0, $pendingCount);

        $settledCount = EmployeeLoanEmiSchedule::where('employee_loan_id', $loan->id)
            ->where('status', 'Paid via FNF')
            ->count();
        $this->assertEquals(2, $settledCount);
    }

    public function test_loan_rejection_requires_mandatory_reason(): void
    {
        $loan = $this->loanService->applyLoan($this->employee, [
            'loan_type' => 'Personal Loan',
            'principal_amount' => 50000.00,
            'tenure_months' => 12,
        ]);

        // 1. Without reason fails
        $failResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.reject', $loan), [
                'rejection_reason' => '',
            ]);
        $failResponse->assertSessionHasErrors(['rejection_reason']);

        // 2. With reason succeeds
        $successResponse = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('payroll.loans.reject', $loan), [
                'rejection_reason' => 'Existing loan already active and tenure limit reached',
            ]);
        $successResponse->assertRedirect();
        $loan->refresh();

        $this->assertEquals('Rejected', $loan->status);
        $this->assertEquals('Existing loan already active and tenure limit reached', $loan->rejection_reason);
    }
}
