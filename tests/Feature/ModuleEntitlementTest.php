<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\SubscriptionFeature;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TenantSubscriptionSeeder;
use Tests\TestCase;

class ModuleEntitlementTest extends TestCase
{
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

        $hoRole = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $this->hoAdminUser = User::firstOrCreate(
            ['email' => 'test_entitlement_ho_admin@vishalenterprise.com'],
            [
                'name' => 'Entitlement Test Admin',
                'password' => bcrypt('password123'),
                'company_id' => $this->companyVE->id,
                'role_id' => $hoRole->id,
                'is_active' => true,
            ]
        );
    }

    /** 1. Tenant on Enterprise plan can access entitled modules (e.g. Asset Management) */
    public function test_tenant_with_feature_can_access_module(): void
    {
        // Ensure asset_management is enabled in plan
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'asset_management')
            ->update(['is_enabled' => true]);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('assets.index'));

        $response->assertOk();
    }

    /** 2. Disabling module feature denies access with 403 HTTP error */
    public function test_disabling_module_feature_denies_access_with_403(): void
    {
        // Temporarily disable asset_management in the Enterprise plan
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'asset_management')
            ->update(['is_enabled' => false]);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('assets.index'));

        $response->assertStatus(403);
        $response->assertSee('Access Denied');
        $response->assertSee('Asset Management');

        // Restore entitlement
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'asset_management')
            ->update(['is_enabled' => true]);
    }

    /** 3. Document Management entitlement protection */
    public function test_document_management_module_entitlement_protection(): void
    {
        // Ensure document_management is enabled
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'document_management')
            ->update(['is_enabled' => true]);

        $response1 = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('admin.documents.templates.index'));

        $response1->assertOk();

        // Disable document_management
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'document_management')
            ->update(['is_enabled' => false]);

        $response2 = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('admin.documents.templates.index'));

        $response2->assertStatus(403);
        $response2->assertSee('Access Denied');
        $response2->assertSee('Document Management');

        // Restore entitlement
        SubscriptionFeature::where('subscription_plan_id', $this->enterprisePlan->id)
            ->where('feature_code', 'document_management')
            ->update(['is_enabled' => true]);
    }
}

