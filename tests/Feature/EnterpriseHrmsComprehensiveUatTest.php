<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\Shift;
use App\Models\SubscriptionFeature;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\CompanyContextService;
use App\Services\LicenseEntitlementService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\PlatformUserSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantSubscriptionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseHrmsComprehensiveUatTest extends TestCase
{
    protected PlatformUser $platformAdmin;
    protected Tenant $vishalTenant;
    protected Company $companyVE;
    protected Company $companyVRE;
    protected User $hoAdminUser;
    protected SubscriptionPlan $enterprisePlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TenantSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(TenantSubscriptionSeeder::class);

        $this->vishalTenant = Tenant::where('tenant_code', 'TNT-VISHAL')->firstOrFail();
        $this->companyVE = Company::where('code', 'VE')->firstOrFail();
        $this->companyVRE = Company::where('code', 'VRE')->firstOrFail();
        $this->enterprisePlan = SubscriptionPlan::where('plan_code', 'PLAN-ENTERPRISE')->firstOrFail();

        $this->platformAdmin = PlatformUser::updateOrCreate(
            ['email' => 'uat_platform_admin@advaititsolutions.com'],
            [
                'name' => 'Advait Platform UAT Auditor',
                'password' => Hash::make('PlatformUat@2026#Secure'),
                'status' => 'active',
            ]
        );

        $hoRole = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $this->hoAdminUser = User::firstOrCreate(
            ['email' => 'uat_ho_admin@vishalenterprise.com'],
            [
                'name' => 'Vikram Singhania HO UAT',
                'password' => Hash::make('password123'),
                'company_id' => $this->companyVE->id,
                'role_id' => $hoRole->id,
                'is_active' => true,
            ]
        );
    }

    // =========================================================================
    // PART A: PLATFORM SUPER ADMIN AUDIT & LIFECYCLE
    // =========================================================================

    /** UAT-A01: Platform login validation requires email and password */
    public function test_uat_a01_platform_login_validation_rules(): void
    {
        $response = $this->post(route('platform.login.post'), []);
        $response->assertSessionHasErrors(['email', 'password']);
    }

    /** UAT-A02: Platform login rejects invalid credentials */
    public function test_uat_a02_platform_login_rejects_invalid_credentials(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->platformAdmin->email,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** UAT-A03: Platform login succeeds with valid credentials and sets platform guard */
    public function test_uat_a03_platform_login_successful(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->platformAdmin->email,
            'password' => 'PlatformUat@2026#Secure',
        ]);

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertEquals($this->platformAdmin->id, Auth::guard('platform')->id());
    }

    /** UAT-A04: Platform dashboard displays platform KPIs and stack info */
    public function test_uat_a04_platform_dashboard_kpis(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('SaaS Platform Control Tower');
        $response->assertSee('Customer Tenants');
        $response->assertSee('Active Subscriptions');
        $response->assertSee('Commercial Plans');
        $response->assertSee('Advait IT Solutions');
    }

    /** UAT-A05: Platform Super Admin can view customer tenants */
    public function test_uat_a05_platform_tenants_listing(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->get(route('platform.tenants.index', ['search' => 'TNT-VISHAL']));

        $response->assertOk();
        $response->assertSee('TNT-VISHAL');
        $response->assertSee('Vishal Group');
    }

    /** UAT-A06: Platform Super Admin can create a new customer tenant */
    public function test_uat_a06_platform_create_tenant(): void
    {
        Tenant::where('tenant_code', 'TNT-UAT-NEW')->delete();

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.store'), [
                'tenant_code' => 'TNT-UAT-NEW',
                'name' => 'Apex Logistics Group',
                'slug' => 'apex-logistics-group',
                'status' => 'active',
                'contact_name' => 'Rajesh Sharma',
                'contact_email' => 'rajesh@apexlogistics.com',
                'contact_phone' => '+91 98888 77777',
            ]);

        $newTenant = Tenant::where('tenant_code', 'TNT-UAT-NEW')->first();
        $this->assertNotNull($newTenant);
        $response->assertRedirect(route('platform.tenants.show', $newTenant));

        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'tenant_id' => $newTenant->id,
            'action' => 'TENANT_CREATED',
        ]);
    }

    /** UAT-A07: Platform Super Admin can suspend and reactivate tenant */
    public function test_uat_a07_platform_tenant_status_toggle(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-TOGGLE-UAT'],
            ['name' => 'Toggle Corp', 'slug' => 'toggle-corp', 'status' => 'active']
        );

        // Suspend
        $resSuspend = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.status', $tenant), ['status' => 'suspended']);
        $resSuspend->assertRedirect();
        $tenant->refresh();
        $this->assertEquals('suspended', $tenant->status);

        // Reactivate
        $resActive = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.status', $tenant), ['status' => 'active']);
        $resActive->assertRedirect();
        $tenant->refresh();
        $this->assertEquals('active', $tenant->status);
    }

    /** UAT-A08: Direct URL access to platform routes by unauthenticated user redirects to login */
    public function test_uat_a08_unauthenticated_platform_access_redirects(): void
    {
        $response = $this->get(route('platform.dashboard'));
        $response->assertRedirect(route('platform.login'));
    }

    /** UAT-A09: Customer HRMS users cannot access platform routes */
    public function test_uat_a09_customer_hrms_user_cannot_access_platform(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.login'));
    }

    /** UAT-A10: Platform Super Admin logout invalidates session */
    public function test_uat_a10_platform_logout(): void
    {
        $this->actingAs($this->platformAdmin, 'platform');
        $this->assertTrue(Auth::guard('platform')->check());

        $response = $this->post(route('platform.logout'));
        $response->assertRedirect(route('platform.login'));
        $this->assertFalse(Auth::guard('platform')->check());
    }

    // =========================================================================
    // PART B: TENANT SUPER ADMIN & GROUP COMPANY MANAGEMENT
    // =========================================================================

    /** UAT-B01: Customer login succeeds and dispatches to role dashboard */
    public function test_uat_b01_customer_login_dispatch(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => $this->hoAdminUser->email,
            'password' => 'password123',
        ]);

        $this->assertTrue(in_array($response->status(), [200, 302]));
        $this->assertTrue(Auth::guard('web')->check());
    }

    /** UAT-B02: HO Admin can switch between legal entities within same tenant */
    public function test_uat_b02_company_switching_within_tenant(): void
    {
        $companyContext = app(CompanyContextService::class);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->post(route('company.switch'), [
                'company_id' => $this->companyVRE->id,
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals($this->companyVRE->id, $companyContext->getActiveCompanyId());
    }

    /** UAT-B03: HO Admin cannot switch to company belonging to different tenant */
    public function test_uat_b03_cross_tenant_company_switch_blocked(): void
    {
        $otherTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-OTHER-UAT'],
            ['name' => 'Other Tenant Corp', 'slug' => 'other-tenant-corp', 'status' => 'active']
        );

        $otherCompany = Company::firstOrCreate(
            ['code' => 'CO-OTHER-UAT'],
            ['name' => 'Foreign Entity Ltd', 'status' => 'Active', 'tenant_id' => $otherTenant->id]
        );

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->post(route('company.switch'), [
                'company_id' => $otherCompany->id,
            ]);

        $response->assertSessionHas('error');
        $this->assertNotEquals($otherCompany->id, session('active_company_id'));
    }

    /** UAT-B04: Customer tenant subscription page renders usage quotas and plan */
    public function test_uat_b04_customer_subscription_page(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('subscription.show'));

        $response->assertOk();
        $response->assertSee('Organization Subscription');
        $response->assertSee('PLAN-ENTERPRISE');
        $response->assertSee('Active Employee Headcount');
        $response->assertSee('Registered Legal Entities');
    }

    // =========================================================================
    // PART C: MULTI-TENANT SECURITY & IDOR ENFORCEMENT
    // =========================================================================

    /** UAT-C01: Cross-tenant direct employee access via IDOR is rejected (404/403) */
    public function test_uat_c01_cross_tenant_employee_idor_blocked(): void
    {
        $tenantB = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-IDOR-B'],
            ['name' => 'IDOR Target Tenant', 'slug' => 'idor-target-tenant', 'status' => 'active']
        );

        $companyB = Company::firstOrCreate(
            ['code' => 'CO-IDOR-B'],
            ['name' => 'Target Company B', 'status' => 'Active', 'tenant_id' => $tenantB->id]
        );

        $locB = Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyB->id, 'name' => 'Loc B'],
            ['code' => 'LOC-B', 'status' => 'Active']
        );
        $deptB = Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyB->id, 'name' => 'Dept B'],
            ['code' => 'DEP-B', 'status' => 'Active']
        );
        $desigB = Designation::withoutGlobalScopes()->first();
        $catB = Category::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyB->id, 'name' => 'Cat B'],
            ['code' => 'CAT-B', 'status' => 'Active']
        );

        $foreignEmployee = Employee::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyB->id, 'employee_code' => 'EMP-FOREIGN-01'],
            [
                'first_name' => 'Secret',
                'last_name' => 'Target',
                'email' => 'secret@target.com',
                'phone' => '+91 90000 11111',
                'gender' => 'Male',
                'date_of_birth' => '1990-01-01',
                'date_of_joining' => '2023-01-01',
                'employment_channel' => 'Direct',
                'employee_type' => 'Permanent',
                'location_id' => $locB->id,
                'department_id' => $deptB->id,
                'designation_id' => $desigB->id,
                'category_id' => $catB->id,
                'status' => 'Active',
            ]
        );

        // Attempt direct access as Vishal Group user
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('employees.show', $foreignEmployee->id));

        $this->assertTrue(in_array($response->status(), [404, 403]));
    }

    // =========================================================================
    // PART D: SUBSCRIPTION & LICENSING ENGINE LIFECYCLE
    // =========================================================================

    /** UAT-D01: Trial subscription allows operational access */
    public function test_uat_d01_trial_subscription_allows_access(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $sub->update([
            'status' => 'trial',
            'ends_at' => Carbon::now()->addDays(14),
        ]);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard.admin'));

        $response->assertOk();

        // Restore
        $sub->update([
            'status' => 'active',
            'ends_at' => Carbon::now()->addYear(),
        ]);
    }

    /** UAT-D02: Expired subscription terminates session and redirects to login */
    public function test_uat_d02_expired_subscription_blocks_access(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $sub->update([
            'status' => 'expired',
            'ends_at' => Carbon::now()->subDays(30),
            'grace_period_ends_at' => Carbon::now()->subDays(15),
        ]);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard.admin'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());

        // Restore
        $sub->update([
            'status' => 'active',
            'ends_at' => Carbon::now()->addYear(),
            'grace_period_ends_at' => Carbon::now()->addYear()->addDays(14),
        ]);
    }

    /** UAT-D03: Suspended subscription terminates session and redirects to login */
    public function test_uat_d03_suspended_subscription_blocks_access(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $sub->update(['status' => 'suspended']);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard.admin'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());

        // Restore
        $sub->update(['status' => 'active']);
    }

    /** UAT-D04: Cancelled subscription terminates session and redirects to login */
    public function test_uat_d04_cancelled_subscription_blocks_access(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $sub->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard.admin'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());

        // Restore
        $sub->update(['status' => 'active']);
    }

    /** UAT-D05: Invoice settlement and duplicate payment protection */
    public function test_uat_d05_invoice_settlement_and_duplicate_prevention(): void
    {
        $sub = $this->vishalTenant->activeSubscription();

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $this->vishalTenant->id,
            'tenant_subscription_id' => $sub->id,
            'invoice_number' => 'INV-UAT-' . uniqid(),
            'amount' => 50000.00,
            'currency' => 'INR',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(15),
        ]);

        // 1. Mark as paid
        $resPay = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.invoices.mark-paid', $invoice), [
                'payment_provider' => 'manual_bank_transfer',
                'payment_reference' => 'PAY-UAT-' . uniqid(),
            ]);

        $resPay->assertRedirect();
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $initialPaymentsCount = $invoice->payments()->count();
        $this->assertEquals(1, $initialPaymentsCount);

        // 2. Attempt duplicate payment
        $resDuplicate = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.invoices.mark-paid', $invoice), [
                'payment_provider' => 'manual_bank_transfer',
            ]);

        $resDuplicate->assertSessionHas('info');
        $this->assertEquals(1, $invoice->payments()->count()); // Not duplicated
    }

    // =========================================================================
    // PART E: CORE HRMS MODULES & RESTRICTIONS
    // =========================================================================

    /** UAT-E01: Department management works cleanly */
    public function test_uat_e01_department_management(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('departments.index'));

        $response->assertOk();
    }

    /** UAT-E02: Designation management works cleanly */
    public function test_uat_e02_designation_management(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('designations.index'));

        $response->assertOk();
    }

    /** UAT-E03: Shift master works cleanly */
    public function test_uat_e03_shift_master(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('shifts.index'));

        $response->assertOk();
    }

    /** UAT-E04: Leave types index renders */
    public function test_uat_e04_leave_types_index(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('leaves.types.index'));

        $response->assertOk();
    }

    /** UAT-E05: Attendance muster index renders */
    public function test_uat_e05_attendance_muster_index(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('attendance.index'));

        $response->assertOk();
    }

    /** UAT-E06: Payroll runs index renders */
    public function test_uat_e06_payroll_runs_index(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('payroll.runs.index'));

        $response->assertOk();
    }

    /** UAT-E07: Reports hub index renders */
    public function test_uat_e07_reports_index(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('admin.reports.index'));

        $response->assertOk();
    }

    /** UAT-E08: Entitled asset module access permitted, unentitled returns 403 */
    public function test_uat_e08_entitlement_access_control(): void
    {
        // 1. Entitled
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'asset_management')
            ->update(['is_enabled' => true]);

        $res1 = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('assets.index'));
        $res1->assertOk();

        // 2. Unentitled
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'asset_management')
            ->update(['is_enabled' => false]);

        $res2 = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('assets.index'));
        $res2->assertStatus(403);
        $res2->assertSee('Access Denied');

        // Restore
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'asset_management')
            ->update(['is_enabled' => true]);
    }
}
