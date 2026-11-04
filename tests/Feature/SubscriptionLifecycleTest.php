<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\LicenseEntitlementService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Database\Seeders\SubscriptionPlanSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantSubscriptionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    protected PlatformUser $platformAdmin;
    protected Tenant $vishalTenant;
    protected Company $companyVE;
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
        $this->enterprisePlan = SubscriptionPlan::where('plan_code', 'PLAN-ENTERPRISE')->firstOrFail();

        $this->platformAdmin = PlatformUser::updateOrCreate(
            ['email' => 'test_sub_admin@advaititsolutions.com'],
            [
                'name' => 'Advait Platform Licensing Admin',
                'password' => Hash::make('SuperAdminPass@2026#Secure'),
                'status' => 'active',
            ]
        );

        $hoRole = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $this->hoAdminUser = User::firstOrCreate(
            ['email' => 'test_sub_ho_admin@vishalenterprise.com'],
            [
                'name' => 'Vikram Singhania HO Licensing Test',
                'password' => Hash::make('password123'),
                'company_id' => $this->companyVE->id,
                'role_id' => $hoRole->id,
                'is_active' => true,
            ]
        );
    }

    /** 1. Platform Super Admin can view subscription plans */
    public function test_platform_super_admin_can_view_subscription_plans(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->get(route('platform.plans.index'));

        $response->assertOk();
        $response->assertSee('PLAN-STARTER');
        $response->assertSee('PLAN-PRO');
        $response->assertSee('PLAN-ENTERPRISE');
    }

    /** 2. Platform Super Admin can create a new commercial subscription plan */
    public function test_platform_super_admin_can_create_new_subscription_plan(): void
    {
        SubscriptionPlan::where('plan_code', 'PLAN-CUSTOM-TEST')->delete();

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.plans.store'), [
                'plan_code' => 'PLAN-CUSTOM-TEST',
                'name' => 'Custom Growth Tier',
                'description' => 'Tailored tier for emerging enterprises',
                'billing_interval' => 'yearly',
                'price' => 75000,
                'currency' => 'INR',
                'employee_limit' => 250,
                'company_limit' => 3,
                'status' => 'active',
                'features' => ['employee_management', 'attendance_management', 'leave_management', 'payroll_management'],
            ]);

        $response->assertRedirect(route('platform.plans.index'));
        $this->assertDatabaseHas('subscription_plans', [
            'plan_code' => 'PLAN-CUSTOM-TEST',
            'name' => 'Custom Growth Tier',
            'price' => 75000,
            'employee_limit' => 250,
        ]);

        $this->assertDatabaseHas('subscription_features', [
            'feature_code' => 'payroll_management',
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'action' => 'SUBSCRIPTION_PLAN_CREATED',
        ]);
    }

    /** 3. Platform Super Admin can update an existing subscription plan */
    public function test_platform_super_admin_can_update_subscription_plan(): void
    {
        $plan = SubscriptionPlan::where('plan_code', 'PLAN-STARTER')->firstOrFail();

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->put(route('platform.plans.update', $plan), [
                'name' => 'Starter Tier Updated',
                'description' => 'Updated description for starter tier',
                'billing_interval' => 'monthly',
                'price' => 6000,
                'currency' => 'INR',
                'employee_limit' => 75,
                'company_limit' => 1,
                'status' => 'active',
                'features' => ['employee_management', 'attendance_management'],
            ]);

        $response->assertRedirect(route('platform.plans.index'));
        $plan->refresh();
        $this->assertEquals('Starter Tier Updated', $plan->name);
        $this->assertEquals(75, $plan->employee_limit);
        $this->assertEquals(6000, $plan->price);

        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'action' => 'SUBSCRIPTION_PLAN_UPDATED',
        ]);
    }

    /** 4. Platform Super Admin can assign a subscription plan to a tenant */
    public function test_platform_super_admin_can_assign_subscription_plan_to_tenant(): void
    {
        $testTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-SUBTEST'],
            ['name' => 'Sub Test Corp', 'slug' => 'sub-test-corp', 'status' => 'active']
        );

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.subscriptions.store', $testTenant), [
                'subscription_plan_id' => $this->enterprisePlan->id,
                'status' => 'active',
                'starts_at' => Carbon::today()->toDateString(),
                'renewal_type' => 'manual',
                'is_paid' => 1,
                'payment_provider' => 'manual_bank_transfer',
                'notes' => 'Test assignment note',
            ]);

        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $testTenant->id,
            'subscription_plan_id' => $this->enterprisePlan->id,
            'status' => 'active',
        ]);

        $subscription = TenantSubscription::where('tenant_id', $testTenant->id)->latest('id')->first();
        $response->assertRedirect(route('platform.subscriptions.show', $subscription));

        // Invoice and payment records created
        $this->assertDatabaseHas('subscription_invoices', [
            'tenant_id' => $testTenant->id,
            'tenant_subscription_id' => $subscription->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $testTenant->id,
            'tenant_subscription_id' => $subscription->id,
            'status' => 'completed',
        ]);
    }

    /** 5. Active subscription allows HRMS tenant user access */
    public function test_active_subscription_allows_tenant_user_access(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard'));

        $this->assertTrue(in_array($response->status(), [200, 302]));
        $this->assertTrue(Auth::guard('web')->check());
        $dashResponse = $this->get(route('dashboard.admin'));
        $dashResponse->assertOk();
        $this->assertNull(session('subscription_grace_warning'));
    }

    /** 6. Grace period subscription allows access and flashes warning banner */
    public function test_grace_period_subscription_allows_access_with_warning_banner(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $this->assertNotNull($sub);

        // Put subscription in grace period (expired 2 days ago, grace period ends in 12 days)
        $sub->update([
            'status' => 'grace_period',
            'ends_at' => Carbon::now()->subDays(2),
            'grace_period_ends_at' => Carbon::now()->addDays(12),
        ]);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard.admin'));

        $response->assertOk();
        $response->assertSessionHas('subscription_grace_warning');

        // Restore active state for subsequent tests
        $sub->update([
            'status' => 'active',
            'ends_at' => Carbon::now()->addYear(),
            'grace_period_ends_at' => Carbon::now()->addYear()->addDays(14),
        ]);
    }

    /** 7. Expired subscription blocks access and redirects to login */
    public function test_expired_subscription_blocks_tenant_user_access(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $this->assertNotNull($sub);

        $sub->update([
            'status' => 'expired',
            'ends_at' => Carbon::now()->subDays(20),
            'grace_period_ends_at' => Carbon::now()->subDays(6),
        ]);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());
        $response->assertSessionHasErrors('email');

        // Restore active state
        $sub->update([
            'status' => 'active',
            'ends_at' => Carbon::now()->addYear(),
            'grace_period_ends_at' => Carbon::now()->addYear()->addDays(14),
        ]);
    }

    /** 8. Suspended subscription blocks tenant user access */
    public function test_suspended_subscription_blocks_tenant_user_access(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $this->assertNotNull($sub);

        $sub->update(['status' => 'suspended']);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());

        // Restore active state
        $sub->update(['status' => 'active']);
    }

    /** 9. Platform Super Admin can extend subscription validity */
    public function test_platform_super_admin_can_extend_subscription_validity(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $originalEndsAt = $sub->ends_at;

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.subscriptions.extend', $sub), [
                'days' => 45,
            ]);

        $response->assertRedirect();
        $sub->refresh();
        $this->assertTrue($sub->ends_at->greaterThan($originalEndsAt));
        $this->assertEquals('active', $sub->status);

        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'tenant_id' => $this->vishalTenant->id,
            'action' => 'TENANT_SUBSCRIPTION_EXTENDED',
        ]);
    }

    /** 10. Customer tenant can view their subscription profile at /subscription */
    public function test_customer_tenant_can_view_subscription_profile(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('subscription.show'));

        $response->assertOk();
        $response->assertSee('Organization Subscription');
        $response->assertSee('PLAN-ENTERPRISE');
        $response->assertSee('Vishal Group');
    }

    /** 11. HRMS users cannot access platform subscription management endpoints */
    public function test_hrms_users_cannot_access_platform_subscription_endpoints(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('platform.subscriptions.index'));

        $response->assertRedirect(route('platform.login'));
    }

    /** 12. Tenant Employee Quota Enforcement blocks onboarding when limit is reached */
    public function test_employee_quota_enforcement_blocks_onboarding_when_limit_reached(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $plan = $sub->plan;

        // Temporarily set employee limit to 1
        $originalLimit = $plan->employee_limit;
        $plan->update(['employee_limit' => 1]);

        $entitlementService = app(LicenseEntitlementService::class);
        $quotaCheck = $entitlementService->canAddEmployee($this->vishalTenant, $this->companyVE->id);

        $this->assertFalse($quotaCheck['allowed']);
        $this->assertStringContainsString('quota limit reached', $quotaCheck['message']);

        // Restore original limit
        $plan->update(['employee_limit' => $originalLimit]);
    }

    /** 13. Tenant Company Quota Enforcement blocks registering company when limit is reached */
    public function test_company_quota_enforcement_blocks_company_creation_when_limit_reached(): void
    {
        $sub = $this->vishalTenant->activeSubscription();
        $plan = $sub->plan;

        // Currently Vishal Group has 2 companies (VE, VRE). Temporarily set company limit to 2.
        $originalLimit = $plan->company_limit;
        $plan->update(['company_limit' => 2]);

        $entitlementService = app(LicenseEntitlementService::class);
        $quotaCheck = $entitlementService->canAddCompany($this->vishalTenant);

        $this->assertFalse($quotaCheck['allowed']);
        $this->assertStringContainsString('company quota limit reached', $quotaCheck['message']);

        // Try storing a new company via CompanyController
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->post(route('companies.store'), [
                'name' => 'Third Entity Pvt Ltd',
                'code' => 'TEPL',
                'status' => 'Active',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('companies', ['code' => 'TEPL']);

        // Restore original limit
        $plan->update(['company_limit' => $originalLimit]);
    }
}
