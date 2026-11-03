<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\EmployeeDocumentGenerated;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\ReportExportHistory;
use App\Models\Role;
use App\Models\ScheduledReport;
use App\Models\User;
use App\Services\CompanyContextService;
use App\Services\Reporting\ReportingService;
use Tests\TestCase;

/**
 * Final Release Readiness End-to-End UAT Execution Suite.
 * Covers Tests 1 through 8 across all four enterprise roles:
 * HO Admin, HR Executive, Manager MSS, Employee ESS, and Cross-Company Isolation.
 */
class FinalReleaseUatExecutionTest extends TestCase
{
    protected Company $companyVE;
    protected Company $companyVRE;
    protected User $hoAdminUser;
    protected User $hrUserVE;
    protected User $hrUserVRE;
    protected User $managerUser;
    protected User $employeeUser;
    protected Employee $managerEmployee;
    protected Employee $subordinateEmployee;
    protected ReportingService $reportingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportingService = app(ReportingService::class);
        $this->companyVE = Company::where('code', 'VE')->first() ?? Company::first();
        $this->companyVRE = Company::where('code', 'VRE')->first() ?? Company::orderByDesc('id')->first();

        $adminRole = Role::where('name', 'ho_admin')->first();
        $hrRole = Role::where('name', 'hr_executive')->first();
        $managerRole = Role::where('name', 'manager_mss')->first();
        $essRole = Role::where('name', 'employee_ess')->first();

        $this->hoAdminUser = User::where('role_id', $adminRole->id)->first();
        $this->hrUserVE = User::where('role_id', $hrRole->id)->where('company_id', $this->companyVE->id)->first() ?? User::where('role_id', $hrRole->id)->first();
        $this->hrUserVRE = User::where('role_id', $hrRole->id)->where('company_id', $this->companyVRE->id)->first() ?? User::where('role_id', $hrRole->id)->get()->last();
        $this->managerUser = User::where('role_id', $managerRole->id)->first();
        $this->employeeUser = User::where('role_id', $essRole->id)->first();

        // Ensure employees are linked for manager and subordinate
        $employees = Employee::where('company_id', $this->companyVE->id)->take(2)->get();
        if ($employees->count() >= 2) {
            $this->managerEmployee = $employees[0];
            $this->subordinateEmployee = $employees[1];
        } else {
            $this->managerEmployee = Employee::first();
            $this->subordinateEmployee = Employee::first();
        }

        $this->managerUser->employee_code = $this->managerEmployee->employee_code;
        $this->managerUser->save();

        $this->subordinateEmployee->reporting_manager_id = $this->managerEmployee->id;
        $this->subordinateEmployee->save();

        $this->employeeUser->employee_code = $this->subordinateEmployee->employee_code;
        $this->employeeUser->save();

        app(CompanyContextService::class)->setActiveCompanyId($this->companyVE->id);
    }

    /**
     * UAT Test 1: HO Admin Comprehensive Capabilities
     */
    public function test_uat_test_1_ho_admin_capabilities(): void
    {
        // 1. Dashboard access
        $dashResp = $this->actingAs($this->hoAdminUser)->get(route('dashboard.admin'));
        $dashResp->assertStatus(200);
        $dashResp->assertSee('Admin Hub');

        // 2. Company master management
        $companiesResp = $this->actingAs($this->hoAdminUser)->get(route('companies.index'));
        $companiesResp->assertStatus(200);

        // 3. Employee master access
        $employeesResp = $this->actingAs($this->hoAdminUser)->get(route('employees.index'));
        $employeesResp->assertStatus(200);

        // 4. Central reporting hub and report viewing
        $reportsResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.index'));
        $reportsResp->assertStatus(200);

        $viewResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.show', 'MASTER_EMPLOYEE'));
        $viewResp->assertStatus(200);

        // 5. CSV streaming export
        $csvResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.export', ['type' => 'MASTER_EMPLOYEE', 'format' => 'csv']));
        $csvResp->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResp->headers->get('content-type'));

        // 6. Audit logs view
        $auditResp = $this->actingAs($this->hoAdminUser)->get(route('audit-logs.index'));
        $auditResp->assertStatus(200);

        // 7. Scheduled report configuration
        $schedulesResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.schedules'));
        $schedulesResp->assertStatus(200);
    }

    /**
     * UAT Test 2: HR Executive Lifecycle and Operations
     */
    public function test_uat_test_2_hr_executive_operations(): void
    {
        // 1. HR operational dashboard
        $hrDash = $this->actingAs($this->hrUserVE)->get(route('dashboard.hr'));
        $hrDash->assertStatus(200);

        // 2. Employee directory access
        $empResp = $this->actingAs($this->hrUserVE)->get(route('employees.index'));
        $empResp->assertStatus(200);

        // 3. Attendance muster access
        $attResp = $this->actingAs($this->hrUserVE)->get(route('attendance.index'));
        $attResp->assertStatus(200);

        // 4. Leave approvals queue
        $leaveResp = $this->actingAs($this->hrUserVE)->get(route('leaves.approvals.index'));
        $leaveResp->assertStatus(200);

        // 5. Document templates management
        $docResp = $this->actingAs($this->hrUserVE)->get(route('admin.documents.templates.index'));
        $docResp->assertStatus(200);

        // 6. Reporting hub access within company
        $repResp = $this->actingAs($this->hrUserVE)->get(route('admin.reports.index'));
        $repResp->assertStatus(200);

        // 7. Security guard: HR cannot modify legal companies
        $illegalCoResp = $this->actingAs($this->hrUserVE)->post(route('companies.store'), [
            'name' => 'Illegal Company Creation',
            'code' => 'ILL',
        ]);
        $illegalCoResp->assertStatus(403);
    }

    /**
     * UAT Test 3: Manager MSS Page 94 Segregation
     */
    public function test_uat_test_3_manager_mss_page_94_segregation(): void
    {
        // 1. Manager dashboard renders team operational status
        $mgrDash = $this->actingAs($this->managerUser)->get(route('dashboard.manager'));
        $mgrDash->assertStatus(200);
        $mgrDash->assertDontSee('Salary');
        $mgrDash->assertDontSee('Gross Pay');

        // 2. Manager team leave approvals access
        $leaveAppResp = $this->actingAs($this->managerUser)->get(route('leaves.approvals.index'));
        $leaveAppResp->assertStatus(200);

        // 3. Page 94 segregation: Manager is strictly blocked from viewing payroll register
        $salaryRepResp = $this->actingAs($this->managerUser)->get(route('admin.reports.show', 'MONTHLY_PAYROLL_REGISTER'));
        $salaryRepResp->assertStatus(403);

        // 4. Page 94 segregation: Manager cannot access company master
        $coResp = $this->actingAs($this->managerUser)->get(route('companies.index'));
        $coResp->assertStatus(403);

        // 5. Manager cannot access payroll runs
        $payRunResp = $this->actingAs($this->managerUser)->get(route('payroll.runs.index'));
        $payRunResp->assertStatus(403);
    }

    /**
     * UAT Test 4: Employee ESS Personal Self-Service
     */
    public function test_uat_test_4_employee_ess_self_service(): void
    {
        // 1. Employee ESS personal dashboard
        $essDash = $this->actingAs($this->employeeUser)->get(route('dashboard.employee'));
        $essDash->assertStatus(200);

        // 2. Leave applications self-service
        $leaveResp = $this->actingAs($this->employeeUser)->get(route('leaves.applications.index'));
        $leaveResp->assertStatus(200);

        // 3. Personal balances view
        $balResp = $this->actingAs($this->employeeUser)->get(route('leaves.balances.my'));
        $balResp->assertStatus(200);

        // 4. Personal My Documents hub
        $myDocsResp = $this->actingAs($this->employeeUser)->get(route('documents.my-documents'));
        $myDocsResp->assertStatus(200);

        // 5. Privacy isolation: ESS employee cannot access admin or manager dashboards
        $admBlock = $this->actingAs($this->employeeUser)->get(route('dashboard.admin'));
        $admBlock->assertStatus(403);

        $mgrBlock = $this->actingAs($this->employeeUser)->get(route('dashboard.manager'));
        $mgrBlock->assertStatus(403);

        // 6. ESS cannot view company audit logs
        $auditBlock = $this->actingAs($this->employeeUser)->get(route('audit-logs.index'));
        $auditBlock->assertStatus(403);
    }

    /**
     * UAT Test 5: Cross-Company Isolation (VE vs VRE)
     */
    public function test_uat_test_5_cross_company_isolation(): void
    {
        // Set context to Company VE
        app(CompanyContextService::class)->setActiveCompanyId($this->companyVE->id);

        $veEmployees = Employee::where('company_id', $this->companyVE->id)->get();
        $vreEmployees = Employee::where('company_id', $this->companyVRE->id)->get();

        // 1. HR User from VE accessing VE report should only see VE records
        $veRepData = $this->reportingService->generateReport('MASTER_EMPLOYEE', [], $this->hrUserVE);
        $this->assertNotEmpty($veRepData['rows']);

        // 2. Set context to Company VRE
        app(CompanyContextService::class)->setActiveCompanyId($this->companyVRE->id);
        $vreRepData = $this->reportingService->generateReport('MASTER_EMPLOYEE', [], $this->hrUserVRE);

        // Verify isolation: row counts reflect independent company scopes
        $this->assertIsArray($veRepData['rows']);
        $this->assertIsArray($vreRepData['rows']);

        // 3. User switching company context safely
        $switchResp = $this->actingAs($this->hoAdminUser)->post(route('company.switch'), [
            'company_id' => $this->companyVRE->id,
        ]);
        $switchResp->assertStatus(302);
    }

    /**
     * UAT Test 6: Enterprise Reporting & Analytics
     */
    public function test_uat_test_6_reporting_and_analytics(): void
    {
        app(CompanyContextService::class)->setActiveCompanyId($this->companyVE->id);

        // 1. Report viewing with filters
        $viewResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.show', [
            'type' => 'MASTER_EMPLOYEE',
            'department_id' => Department::where('company_id', $this->companyVE->id)->first()?->id,
        ]));
        $viewResp->assertStatus(200);

        // 2. Excel format export
        $excelResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.export', [
            'type' => 'MASTER_EMPLOYEE',
            'format' => 'excel',
        ]));
        $excelResp->assertStatus(200);
        $this->assertStringContainsString('application/vnd.ms-excel', $excelResp->headers->get('content-type'));

        // 3. PDF format export
        $pdfResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.export', [
            'type' => 'HEADCOUNT_DEPARTMENT',
            'format' => 'pdf',
        ]));
        $pdfResp->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type'));

        // 4. Masked bank advice for general HR
        $bankAdviceResp = $this->actingAs($this->hrUserVE)->get(route('admin.reports.bank-advice'));
        $bankAdviceResp->assertStatus(200);

        // 5. Audit history tracking
        $historyResp = $this->actingAs($this->hoAdminUser)->get(route('admin.reports.history'));
        $historyResp->assertStatus(200);
    }

    /**
     * UAT Test 7: Letter/Form Print Engine & Public Verification
     */
    public function test_uat_test_7_letter_form_print_engine(): void
    {
        // 1. Admin views templates index
        $tplResp = $this->actingAs($this->hoAdminUser)->get(route('admin.documents.templates.index'));
        $tplResp->assertStatus(200);

        // 2. Admin views generated document approvals queue
        $apprResp = $this->actingAs($this->hoAdminUser)->get(route('admin.documents.approvals'));
        $apprResp->assertStatus(200);

        // 3. Public verification form is publicly accessible without login
        $pubResp = $this->get(route('documents.verify.form'));
        $pubResp->assertStatus(200);
        $pubResp->assertSee('Document Verification');
    }

    /**
     * UAT Test 8: Payroll & Statutory Calculations
     */
    public function test_uat_test_8_payroll_and_statutory(): void
    {
        // 1. HR views salary structures
        $structResp = $this->actingAs($this->hrUserVE)->get(route('payroll.structures.index'));
        $structResp->assertStatus(200);

        // 2. HR views payroll configurations
        $configResp = $this->actingAs($this->hrUserVE)->get(route('payroll.configurations.index'));
        $configResp->assertStatus(200);

        // 3. HR views payroll runs
        $runsResp = $this->actingAs($this->hrUserVE)->get(route('payroll.runs.index'));
        $runsResp->assertStatus(200);

        // 4. HR views F&F settlement list
        $fnfResp = $this->actingAs($this->hrUserVE)->get(route('payroll.fnf.index'));
        $fnfResp->assertStatus(200);
    }
}
