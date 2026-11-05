<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class CollapsibleSidebarNavigationTest extends TestCase
{
    protected User $hoAdmin;
    protected User $hrUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->firstOrFail();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->firstOrFail();

        $this->company = Company::where('code', 'VE')->first()
            ?? Company::firstOrFail();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_sidebar_renders_collapsible_modules_for_ho_admin_on_dashboard(): void
    {
        $response = $this->actingAs($this->hoAdmin)->get(route('dashboard.admin'));

        $response->assertOk();

        // 1. All Module headers must exist
        $response->assertSee('Core Workspace');
        $response->assertSee('Workforce & Onboarding');
        $response->assertSee('Compliance & Security');
        $response->assertSee('Asset Lifecycle & Fines');
        $response->assertSee('Time, Attendance & Shifts');
        $response->assertSee('Leave & Holiday Engine');
        $response->assertSee('Payroll & Compensation');
        $response->assertSee('Organizational Masters');
        $response->assertSee('Governance & Security');
        $response->assertSee('Notification Engine');
        $response->assertSee('Letters & Print Engine');
        $response->assertSee('Enterprise Reports');

        // 2. Core Workspace is expanded on dashboard route
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/id="mod-core-workspace"[^>]*class="[^"]*show/', $content);

        // 3. Submodules are present
        $response->assertSee('HO Admin Hub');
        $response->assertSee('Workforce Directory');
        $response->assertSee('Onboard Candidate');
        $response->assertSee('Central Blacklist');
        $response->assertSee('Identity Match Queue');
        $response->assertSee('Asset Master');
        $response->assertSee('Damage & Fine Recovery');
        $response->assertSee('Fine Policies');
        $response->assertSee('Daily Muster');
        $response->assertSee('Shift Master');
        $response->assertSee('Shift Roster');
        $response->assertSee('Biometric Devices');
        $response->assertSee('Raw Punch Logs');
        $response->assertSee('Leave Applications');
        $response->assertSee('Holiday Master');
        $response->assertSee('Control Tower');
        $response->assertSee('Salary Structures');
        $response->assertSee('Companies');
        $response->assertSee('Dropdown Masters');
        $response->assertSee('Audit Trail Log');
        $response->assertSee('Reports Hub (53 Reports)');
    }

    public function test_active_module_expands_automatically_on_workforce_directory(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('employees.index'));

        $response->assertOk();

        $content = $response->getContent();

        // mod-workforce-onboarding must have 'show'
        $this->assertMatchesRegularExpression('/id="mod-workforce-onboarding"[^>]*class="[^"]*show/', $content);

        // Workforce Directory link is active
        $this->assertStringContainsString('Workforce Directory', $content);
        $this->assertMatchesRegularExpression('/href="[^"]*\/employees"[^>]*class="[^"]*active/', $content);

        // Other modules are collapsed (not having show)
        $this->assertDoesNotMatchRegularExpression('/id="mod-asset-fines"[^>]*class="[^"]*show/', $content);
        $this->assertDoesNotMatchRegularExpression('/id="mod-time-attendance"[^>]*class="[^"]*show/', $content);
    }

    public function test_active_module_expands_automatically_on_time_attendance(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('attendance.index'));

        $response->assertOk();

        $content = $response->getContent();

        // mod-time-attendance must have 'show'
        $this->assertMatchesRegularExpression('/id="mod-time-attendance"[^>]*class="[^"]*show/', $content);

        // Daily Muster link is active
        $this->assertStringContainsString('Daily Muster', $content);
        $this->assertMatchesRegularExpression('/href="[^"]*\/attendance"[^>]*class="[^"]*active/', $content);

        // mod-workforce-onboarding is collapsed
        $this->assertDoesNotMatchRegularExpression('/id="mod-workforce-onboarding"[^>]*class="[^"]*show/', $content);
    }

    public function test_active_module_expands_automatically_on_payroll(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('payroll.runs.index'));

        $response->assertOk();

        $content = $response->getContent();

        // mod-payroll-settlements must have 'show'
        $this->assertMatchesRegularExpression('/id="mod-payroll-settlements"[^>]*class="[^"]*show/', $content);

        // mod-workforce-onboarding is collapsed
        $this->assertDoesNotMatchRegularExpression('/id="mod-workforce-onboarding"[^>]*class="[^"]*show/', $content);
    }

    public function test_manager_sidebar_renders_team_modules(): void
    {
        $manager = User::whereHas('role', fn($q) => $q->where('name', 'manager_mss'))->first();
        if (!$manager) {
            $this->markTestSkipped('No manager role user available.');
        }

        $response = $this->actingAs($manager)->get(route('dashboard.manager'));
        $response->assertOk();

        $response->assertSee('Team Operations');
        $response->assertSee('Team Attendance');
        $response->assertSee('Team Leave Approvals');
        $response->assertSee('Regularizations');
        $response->assertSee('Overtime Approvals');
        $response->assertSee('My Self-Service');
        $response->assertSee('Apply / My Leaves');
    }

    public function test_employee_sidebar_renders_ess_modules(): void
    {
        $employee = User::whereHas('role', fn($q) => $q->where('name', 'employee_ess'))->first();
        if (!$employee) {
            $this->markTestSkipped('No employee role user available.');
        }

        $response = $this->actingAs($employee)->get(route('dashboard.employee'));
        $response->assertOk();

        $response->assertSee('Employee ESS');
        $response->assertSee('My Self-Service');
        $response->assertSee('Apply / My Leaves');
        $response->assertSee('My Payslips');
        $response->assertSee('My Loans & Advances');
    }
}
