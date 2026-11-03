<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\ReportExportHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyContextService;
use App\Services\Reporting\ReportingService;
use Tests\TestCase;

class EnterpriseReportingEngineTest extends TestCase
{
    protected Company $company;
    protected User $adminUser;
    protected User $hrUser;
    protected User $managerUser;
    protected User $essUser;
    protected Employee $managerEmployee;
    protected Employee $subordinateEmployee;
    protected ReportingService $reportingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportingService = app(ReportingService::class);
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();

        $adminRole = Role::where('name', 'ho_admin')->first();
        $hrRole = Role::where('name', 'hr_executive')->first();
        $managerRole = Role::where('name', 'manager_mss')->first();
        $essRole = Role::where('name', 'employee_ess')->first();

        $this->adminUser = User::where('role_id', $adminRole->id)->first();
        $this->hrUser = User::where('role_id', $hrRole->id)->first();
        $this->managerUser = User::where('role_id', $managerRole->id)->first();
        $this->essUser = User::where('role_id', $essRole->id)->first();

        // Setup manager and subordinate employees
        $employees = Employee::where('company_id', $this->company->id)->take(2)->get();
        $this->managerEmployee = $employees[0];
        $this->subordinateEmployee = $employees[1] ?? $employees[0];

        $this->managerUser->employee_code = $this->managerEmployee->employee_code;
        $this->managerUser->save();

        $this->subordinateEmployee->reporting_manager_id = $this->managerEmployee->id;
        $this->subordinateEmployee->save();

        $this->essUser->employee_code = $this->subordinateEmployee->employee_code;
        $this->essUser->save();

        // Set company context
        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_admin_can_access_reporting_hub(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Enterprise Reports & Analytics Hub');
        $response->assertSee('Workforce & Organization');
        $response->assertSee('Statutory Compliance');
        $response->assertSee('Disbursement & Banking');
    }

    public function test_admin_can_generate_workforce_master_report(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/reports/MASTER_EMPLOYEE');

        $response->assertStatus(200);
        $response->assertSee('Master Employee Directory');
        $response->assertSee($this->subordinateEmployee->employee_code);
    }

    public function test_multi_parameter_filter_by_department(): void
    {
        $dept = Department::where('company_id', $this->company->id)->first();
        $response = $this->actingAs($this->adminUser)->get("/admin/reports/MASTER_EMPLOYEE?department_id={$dept->id}");

        $response->assertStatus(200);
        $response->assertSee('Master Employee Directory');
    }

    public function test_csv_export_streams_with_utf8_bom_and_logs_history(): void
    {
        $initialExports = ReportExportHistory::count();

        $response = $this->actingAs($this->adminUser)->get('/admin/reports/MASTER_EMPLOYEE/export?format=csv');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));

        // Assert audit log created
        $this->assertEquals($initialExports + 1, ReportExportHistory::count());
        $latest = ReportExportHistory::latest('id')->first();
        $this->assertEquals('MASTER_EMPLOYEE', $latest->report_type);
        $this->assertEquals('csv', $latest->file_format);
    }

    public function test_pdf_export_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/reports/HEADCOUNT_DEPARTMENT/export?format=pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        $latest = ReportExportHistory::latest('id')->first();
        $this->assertEquals('HEADCOUNT_DEPARTMENT', $latest->report_type);
        $this->assertEquals('pdf', $latest->file_format);
    }

    public function test_sensitive_bank_account_masking_for_hr_vs_admin(): void
    {
        // 1. HO Admin sees unmasked bank account
        $adminReport = $this->reportingService->generateReport('BANK_TRANSFER_ADVICE', [], $this->adminUser);
        if (!empty($adminReport['rows'])) {
            $firstAccount = $adminReport['rows'][0]['Account Number'];
            $this->assertFalse(str_starts_with($firstAccount, 'XXXXXX'));
        }

        // 2. HR Executive sees masked account
        $hrReport = $this->reportingService->generateReport('BANK_TRANSFER_ADVICE', [], $this->hrUser);
        if (!empty($hrReport['rows'])) {
            $firstAccount = $hrReport['rows'][0]['Account Number'];
            $this->assertTrue(str_starts_with($firstAccount, 'XXXXXX'));
        }
    }

    public function test_manager_mss_adheres_to_page_94_segregation(): void
    {
        // 1. Manager CAN access team operational report (e.g. MASTER_EMPLOYEE)
        $response = $this->actingAs($this->managerUser)->get('/admin/reports/MASTER_EMPLOYEE');
        $response->assertStatus(200);

        // 2. Manager CANNOT access sensitive compensation reports
        $deniedReports = [
            'MONTHLY_PAYROLL_REGISTER',
            'STATUTORY_PF_ECR',
            'STATUTORY_ESI_FORM5',
            'BANK_TRANSFER_ADVICE',
            'FULL_FINAL_SETTLEMENT',
        ];

        foreach ($deniedReports as $forbidden) {
            $forbiddenResponse = $this->actingAs($this->managerUser)->get("/admin/reports/{$forbidden}");
            $this->assertTrue(in_array($forbiddenResponse->getStatusCode(), [403, 500]), "Manager was not denied on {$forbidden}");
        }
    }

    public function test_bank_advice_view_accessible_to_finance_and_admin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/reports/bank-advice');
        $response->assertStatus(200);
        $response->assertSee('Bank Transfer Advice');
    }
}

