<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class RoleBasedDashboardTest extends TestCase
{
    protected Company $company;
    protected User $adminUser;
    protected User $hrUser;
    protected User $managerUser;
    protected User $essUser;
    protected Employee $managerEmployee;
    protected Employee $subordinateEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();

        $adminRole = Role::where('name', 'ho_admin')->first();
        $hrRole = Role::where('name', 'hr_executive')->first();
        $managerRole = Role::where('name', 'manager_mss')->first();
        $essRole = Role::where('name', 'employee_ess')->first();

        $this->adminUser = User::where('role_id', $adminRole->id)->first();
        $this->hrUser = User::where('role_id', $hrRole->id)->first();
        $this->managerUser = User::where('role_id', $managerRole->id)->first();
        $this->essUser = User::where('role_id', $essRole->id)->first();

        $employees = Employee::where('company_id', $this->company->id)->take(2)->get();
        $this->managerEmployee = $employees[0];
        $this->subordinateEmployee = $employees[1] ?? $employees[0];

        $this->managerUser->employee_code = $this->managerEmployee->employee_code;
        $this->managerUser->save();

        $this->subordinateEmployee->reporting_manager_id = $this->managerEmployee->id;
        $this->subordinateEmployee->save();

        $this->essUser->employee_code = $this->subordinateEmployee->employee_code;
        $this->essUser->save();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_ho_admin_dashboard_loads_with_enterprise_metrics_and_charts(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard/admin');

        $response->assertStatus(200);
        $response->assertSee('Head Office Admin Hub');
        $response->assertSee('Headcount by Department');
        $response->assertSee('adminDeptChart');
        $response->assertSee('adminTrendChart');
        $response->assertSee('HO Admin Hub');
    }

    public function test_hr_executive_dashboard_loads_with_operational_queues(): void
    {
        $response = $this->actingAs($this->hrUser)->get('/dashboard/hr');

        $response->assertStatus(200);
        $response->assertSee('HR Executive Operations Hub');
        $response->assertSee('eKYC Verification Pipeline');
        $response->assertSee('Probation Evaluations Due');
        $response->assertSee('deptHeadcountChart');
        $response->assertSee('HR Operations Hub');
        // Crucial: HR Executive must not see HO Admin Hub
        $response->assertDontSee('HO Admin Hub');
        $response->assertDontSee('Head Office Admin Hub');
    }

    public function test_hr_executive_cannot_access_ho_admin_dashboard_forbidden(): void
    {
        $response = $this->actingAs($this->hrUser)->get('/dashboard/admin');
        $response->assertStatus(403);
    }

    public function test_manager_and_employee_cannot_access_admin_or_hr_dashboards(): void
    {
        $this->actingAs($this->managerUser)->get('/dashboard/admin')->assertStatus(403);
        $this->actingAs($this->managerUser)->get('/dashboard/hr')->assertStatus(403);

        $this->actingAs($this->essUser)->get('/dashboard/admin')->assertStatus(403);
        $this->actingAs($this->essUser)->get('/dashboard/hr')->assertStatus(403);
        $this->actingAs($this->essUser)->get('/dashboard/manager')->assertStatus(403);
    }

    public function test_manager_mss_dashboard_adheres_to_page_94_segregation(): void
    {
        $response = $this->actingAs($this->managerUser)->get('/dashboard/manager');

        $response->assertStatus(200);
        $response->assertSee('Manager Self-Service (MSS)');
        $response->assertSee('Direct Reports');
        $response->assertSee('Reporting Team Members');
        $response->assertSee($this->subordinateEmployee->full_name);

        // Crucial Page 94 segregation: Zero compensation or payroll data
        $response->assertDontSee('Total Gross');
        $response->assertDontSee('Net Payout');
        $response->assertDontSee('Salary Slip');
        $response->assertDontSee('PF Deduction');
    }

    public function test_employee_ess_dashboard_loads_with_personal_records(): void
    {
        $response = $this->actingAs($this->essUser)->get('/dashboard/employee');

        $response->assertStatus(200);
        $response->assertSee('Employee Self-Service (ESS)');
        $response->assertSee('Available Leaves');
        $response->assertSee('Attendance Status');
    }

    public function test_dashboard_root_redirects_based_on_user_role(): void
    {
        // 1. Employee redirect
        $essResp = $this->actingAs($this->essUser)->get('/dashboard');
        $essResp->assertRedirect(route('dashboard.employee'));

        // 2. Manager redirect
        $mgrResp = $this->actingAs($this->managerUser)->get('/dashboard');
        $mgrResp->assertRedirect(route('dashboard.manager'));

        // 3. HR Executive redirect
        $hrResp = $this->actingAs($this->hrUser)->get('/dashboard');
        $hrResp->assertRedirect(route('dashboard.hr'));

        // 4. Admin redirect
        $adminResp = $this->actingAs($this->adminUser)->get('/dashboard');
        $adminResp->assertRedirect(route('dashboard.admin'));
    }

    public function test_auth_login_redirects_hr_executive_strictly_to_hr_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->hrUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.hr'));
    }

    public function test_companies_master_is_forbidden_to_hr_executive(): void
    {
        $response = $this->actingAs($this->hrUser)->get('/masters/companies');
        $response->assertStatus(403);
    }

    public function test_onboard_candidate_email_defaults_to_blank_and_never_prefills_admin_email(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/employees/create');

        $response->assertStatus(200);
        $response->assertSee('Candidate Dossier Registration');
        $response->assertSee('autocomplete="off"', false);
        // Ensure email input value is not prefilled with admin email
        $response->assertDontSee('value="' . $this->adminUser->email . '"', false);
        $response->assertDontSee('value="' . $this->hrUser->email . '"', false);
        $response->assertSee('id="candidate_email"', false);
    }

    public function test_candidate_creation_stores_correct_candidate_email(): void
    {
        $location = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $department = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $designation = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->company->id))->first() ?? Designation::first();
        $category = Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $uniqueCandidateEmail = 'candidate.' . time() . '@testdomain.com';
        $uniquePhone = '98' . rand(10000000, 99999999);
        $uniqueAadhaar = '99' . mt_rand(1000000000, 9999999999);
        $uniquePan = 'ABCDE' . mt_rand(1000, 9999) . 'Z';

        $payload = [
            'first_name' => 'Rahul',
            'last_name' => 'Kulkarni',
            'gender' => 'Male',
            'date_of_birth' => '1995-05-15',
            'phone' => $uniquePhone,
            'email' => $uniqueCandidateEmail,
            'joining_date' => now()->toDateString(),
            'location_id' => $location->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employee_category_id' => $category->id,
            'onboarding_channel' => 'Direct',
            'aadhaar_number' => $uniqueAadhaar,
            'pan_number' => $uniquePan,
        ];

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post('/employees', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $savedEmployee = Employee::withoutGlobalScopes()->where('email', $uniqueCandidateEmail)->first();
        $this->assertNotNull($savedEmployee);
        $this->assertEquals($uniqueCandidateEmail, $savedEmployee->email);
        $this->assertNotEquals($this->adminUser->email, $savedEmployee->email);
        $this->assertNotEquals($this->hrUser->email, $savedEmployee->email);
    }

    public function test_candidate_edit_form_loads_saved_candidate_email(): void
    {
        $employee = Employee::where('company_id', $this->company->id)
            ->whereNotNull('email')
            ->first();

        $this->assertNotNull($employee);

        $response = $this->actingAs($this->hrUser)->get("/employees/{$employee->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('value="' . $employee->email . '"', false);
        $response->assertDontSee('value="' . $this->hrUser->email . '"', false);
    }
}
