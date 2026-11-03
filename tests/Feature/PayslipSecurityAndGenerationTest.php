<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeSalaryAssignment;
use App\Models\Location;
use App\Models\PayrollComponentTransaction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\SalaryStructure;
use App\Models\User;
use App\Services\PayrollConfigurationService;
use Tests\TestCase;

class PayslipSecurityAndGenerationTest extends TestCase
{
    protected Company $veCompany;
    protected Company $vreCompany;
    protected User $hrUser;
    protected User $essUserA;
    protected User $essUserB;
    protected Employee $employeeA;
    protected Employee $employeeB;
    protected PayrollRunEmployee $payslipA;
    protected PayrollRunEmployee $payslipB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->veCompany = Company::where('code', 'VE')->first() ?? Company::first();
        $this->vreCompany = Company::where('code', 'VRE')->first() ?? Company::skip(1)->first();

        app(PayrollConfigurationService::class)->ensureDefaultPtSlabs($this->veCompany->id);

        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::where('email', 'hr@vishal.com')->first();

        $location = Location::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        $department = Department::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        $designation = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->veCompany->id))->first();
        $category = Category::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();

        $randA = rand(10000, 99999);
        $this->employeeA = Employee::create([
            'company_id' => $this->veCompany->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'category_id' => $category->id,
            'employee_code' => 'EMP-PAY-A-' . $randA,
            'first_name' => 'Aditya',
            'last_name' => 'Joshi',
            'gender' => 'Male',
            'phone' => '94' . $randA . '444',
            'date_of_birth' => '1992-07-19',
            'date_of_joining' => '2023-01-15',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'aditya.' . $randA . '@vishal.com',
        ]);

        $randB = rand(10000, 99999);
        $this->employeeB = Employee::create([
            'company_id' => $this->veCompany->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'category_id' => $category->id,
            'employee_code' => 'EMP-PAY-B-' . $randB,
            'first_name' => 'Bhavna',
            'last_name' => 'Mehta',
            'gender' => 'Female',
            'phone' => '94' . $randB . '555',
            'date_of_birth' => '1994-08-22',
            'date_of_joining' => '2023-03-01',
            'status' => 'Active',
            'is_salary_activated' => true,
            'email' => 'bhavna.' . $randB . '@vishal.com',
        ]);

        $essRole = \App\Models\Role::where('name', 'employee_ess')->first();

        $this->essUserA = User::create([
            'company_id' => $this->veCompany->id,
            'employee_code' => $this->employeeA->employee_code,
            'role_id' => $essRole?->id,
            'name' => 'Aditya Joshi',
            'email' => 'aditya.ess.' . $randA . '@vishal.com',
            'password' => bcrypt('Password@123'),
            'is_active' => true,
        ]);

        $this->essUserB = User::create([
            'company_id' => $this->veCompany->id,
            'employee_code' => $this->employeeB->employee_code,
            'role_id' => $essRole?->id,
            'name' => 'Bhavna Mehta',
            'email' => 'bhavna.ess.' . $randB . '@vishal.com',
            'password' => bcrypt('Password@123'),
            'is_active' => true,
        ]);

        $structure = SalaryStructure::firstOrCreate(
            ['company_id' => $this->veCompany->id, 'code' => 'STD-PAYSLIP-TEST'],
            ['name' => 'Standard Payslip Test Structure', 'currency' => 'INR', 'is_active' => true]
        );

        $assignmentA = EmployeeSalaryAssignment::create([
            'company_id' => $this->veCompany->id,
            'employee_id' => $this->employeeA->id,
            'salary_structure_id' => $structure->id,
            'ctc_annual' => 600000.00,
            'gross_monthly' => 50000.00,
            'effective_from_date' => '2026-01-01',
            'status' => 'Approved',
        ]);

        $period = PayrollPeriod::firstOrCreate(
            ['company_id' => $this->veCompany->id, 'year' => 2026, 'month' => 4],
            ['start_date' => '2026-04-01', 'end_date' => '2026-04-30', 'status' => 'Open']
        );

        $run = PayrollRun::create([
            'company_id' => $this->veCompany->id,
            'payroll_period_id' => $period->id,
            'run_batch_number' => 'PAY-VE-202604-TEST' . rand(100, 999),
            'year' => 2026,
            'month' => 4,
            'total_eligible_employees' => 2,
            'total_processed_employees' => 2,
            'total_gross_earnings' => 100000.00,
            'total_net_payout' => 96000.00,
            'status' => 'Approved',
        ]);

        $this->payslipA = PayrollRunEmployee::create([
            'company_id' => $this->veCompany->id,
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employeeA->id,
            'employee_salary_assignment_id' => $assignmentA->id,
            'year' => 2026,
            'month' => 4,
            'calendar_days' => 30,
            'worked_days' => 30,
            'paid_days' => 30,
            'basic_salary' => 25000.00,
            'hra' => 12500.00,
            'da' => 5000.00,
            'allowances_amount' => 7500.00,
            'gross_earnings' => 50000.00,
            'pf_employee' => 1800.00,
            'professional_tax' => 200.00,
            'total_deductions' => 2000.00,
            'net_salary' => 48000.00,
            'payslip_number' => 'PS-VE-202604-' . $this->employeeA->employee_code,
            'payment_status' => 'Pending',
        ]);

        PayrollComponentTransaction::create([
            'payroll_run_employee_id' => $this->payslipA->id,
            'component_type' => 'Earning',
            'component_code' => 'BASIC',
            'component_name' => 'Basic Salary',
            'standard_amount' => 25000.00,
            'earned_or_deducted_amount' => 25000.00,
        ]);

        PayrollComponentTransaction::create([
            'payroll_run_employee_id' => $this->payslipA->id,
            'component_type' => 'Deduction',
            'component_code' => 'PF_EMP',
            'component_name' => 'Provident Fund',
            'standard_amount' => 0.00,
            'earned_or_deducted_amount' => 1800.00,
        ]);

        $this->payslipB = PayrollRunEmployee::create([
            'company_id' => $this->veCompany->id,
            'payroll_run_id' => $run->id,
            'employee_id' => $this->employeeB->id,
            'employee_salary_assignment_id' => $assignmentA->id,
            'year' => 2026,
            'month' => 4,
            'calendar_days' => 30,
            'worked_days' => 30,
            'paid_days' => 30,
            'basic_salary' => 25000.00,
            'gross_earnings' => 50000.00,
            'net_salary' => 48000.00,
            'payslip_number' => 'PS-VE-202604-' . $this->employeeB->employee_code,
            'payment_status' => 'Pending',
        ]);
    }

    public function test_payslip_display_and_itemized_breakdown(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('payroll.payslips.show', $this->payslipA));

        $response->assertOk();
        $response->assertViewIs('payroll.payslips.show');
        $response->assertSee($this->payslipA->payslip_number);
        $response->assertSee('25,000.00');
        $response->assertSee('48,000.00');
        $response->assertSee('Aditya Joshi');
    }

    public function test_payslip_pdf_generation_binary_output(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('payroll.payslips.download', $this->payslipA));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Payslip-' . $this->payslipA->payslip_number . '.pdf', $response->headers->get('Content-Disposition'));

        // Check PDF Magic Header
        $binary = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $binary);
    }

    public function test_ess_user_can_view_own_payslip(): void
    {
        $response = $this->actingAs($this->essUserA)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('payroll.payslips.show', $this->payslipA));

        $response->assertOk();
        $response->assertSee($this->payslipA->payslip_number);

        $pdfResponse = $this->actingAs($this->essUserA)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('payroll.payslips.download', $this->payslipA));

        $pdfResponse->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());
    }

    public function test_ess_user_forbidden_from_viewing_another_employee_payslip(): void
    {
        // ESS User A tries to view Payslip B
        $response = $this->actingAs($this->essUserA)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('payroll.payslips.show', $this->payslipB));

        $response->assertStatus(403);

        // ESS User A tries to download Payslip B PDF
        $pdfResponse = $this->actingAs($this->essUserA)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('payroll.payslips.download', $this->payslipB));

        $pdfResponse->assertStatus(403);
    }

    public function test_cross_company_payslip_access_blocked(): void
    {
        $vreUser = User::where('company_id', $this->vreCompany->id)->first();
        if (!$vreUser) {
            $vreUser = User::create([
                'company_id' => $this->vreCompany->id,
                'name' => 'VRE Cross User',
                'email' => 'vre.cross.' . rand(1000, 9999) . '@vrishal.com',
                'password' => bcrypt('Password@123'),
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($vreUser)
            ->withSession(['active_company_id' => $this->vreCompany->id])
            ->get(route('payroll.payslips.show', $this->payslipA));

        // Multi-tenant scope prevents model binding across company boundaries (404) or authorization guard throws 403
        $this->assertContains($response->status(), [403, 404]);
    }
}
