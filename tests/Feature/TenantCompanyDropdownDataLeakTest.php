<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Location;
use App\Models\OnboardingDropdownMaster;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class TenantCompanyDropdownDataLeakTest extends TestCase
{
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Company $companyA1;
    protected Company $companyA2;
    protected Company $companyB1;
    protected Location $locationA1;
    protected Location $locationB1;
    protected User $tenantAdminA;
    protected User $companyAdminA;
    protected User $branchAdminA;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $this->seed(\Database\Seeders\TenantSubscriptionSeeder::class);

        // 1. Setup Tenant A (Vishal Group) with 2 Companies (Multi-company tenant)
        $this->tenantA = Tenant::where('tenant_code', 'TNT-VISHAL')->firstOrFail();
        $this->companyA1 = Company::where('code', 'VE')->firstOrFail();
        $this->companyA2 = Company::where('code', 'VRE')->firstOrFail();

        $this->locationA1 = Location::firstOrCreate(
            ['code' => 'LOC-LEAK-A1', 'company_id' => $this->companyA1->id],
            ['name' => 'Plant Alpha Location', 'status' => 'Active']
        );

        // 2. Setup Foreign Tenant B (Target Tenant 4) with Company and Location
        $this->companyB1 = Company::where('code', 'CO-IDOR-B')->firstOrFail();
        $this->tenantB = $this->companyB1->tenant ?: Tenant::find($this->companyB1->tenant_id);

        $this->locationB1 = Location::firstOrCreate(
            ['code' => 'LOC-LEAK-B1', 'company_id' => $this->companyB1->id],
            ['name' => 'Foreign Location B1', 'status' => 'Active']
        );

        // 3. Setup Roles
        $hoAdminRole = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $companyAdminRole = Role::firstOrCreate(['name' => 'company_admin'], ['label' => 'Company Admin']);
        $branchAdminRole = Role::firstOrCreate(['name' => 'branch_admin'], ['label' => 'Branch Admin']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['label' => 'Super Admin']);

        // 4. Setup Users
        // Tenant Admin: HO Admin belonging to Tenant A, can switch companies
        $this->tenantAdminA = User::updateOrCreate(
            ['email' => 'tenant_admin_leak_a@enterprise.com'],
            [
                'name' => 'Tenant Admin A',
                'password' => bcrypt('password'),
                'company_id' => $this->companyA1->id,
                'role_id' => $hoAdminRole->id,
                'is_active' => true,
                'can_switch_company' => true,
            ]
        );

        // Company Admin: Restricted to Company A1 only (cannot switch)
        $this->companyAdminA = User::updateOrCreate(
            ['email' => 'company_admin_leak_a@enterprise.com'],
            [
                'name' => 'Company Admin A1',
                'password' => bcrypt('password'),
                'company_id' => $this->companyA1->id,
                'role_id' => $companyAdminRole->id,
                'is_active' => true,
                'can_switch_company' => false,
            ]
        );

        // Branch Admin: Restricted to Company A1 and Location A1
        $this->branchAdminA = User::updateOrCreate(
            ['email' => 'branch_admin_leak_a@enterprise.com'],
            [
                'name' => 'Branch Admin A1',
                'password' => bcrypt('password'),
                'company_id' => $this->companyA1->id,
                'role_id' => $branchAdminRole->id,
                'is_active' => true,
                'can_switch_company' => false,
            ]
        );

        // Super Admin
        $this->superAdmin = User::updateOrCreate(
            ['email' => 'super_admin_leak_test@enterprise.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'company_id' => $this->companyA1->id,
                'role_id' => $superAdminRole->id,
                'is_active' => true,
                'can_switch_company' => true,
            ]
        );

        app(CompanyContextService::class)->setActiveCompanyId($this->companyA1->id);
    }

    /**
     * TEST 1: Tenant Admin only sees companies from their own tenant.
     */
    public function test_tenant_admin_dropdown_does_not_leak_foreign_tenant_companies(): void
    {
        $response = $this->actingAs($this->tenantAdminA)->get(route('admin.masters.index'));

        $response->assertOk();
        // Authorized Tenant A companies must be visible
        $response->assertSee($this->companyA1->name);
        $response->assertSee($this->companyA2->name);

        // Foreign Tenant B company MUST NOT be present in HTML or dropdown data
        $response->assertDontSee($this->companyB1->name);
        $response->assertDontSee($this->companyB1->code);
    }

    /**
     * TEST 2: Company Admin only sees their assigned company.
     */
    public function test_company_admin_is_restricted_to_assigned_company(): void
    {
        $response = $this->actingAs($this->companyAdminA)->get(route('admin.masters.index'));

        $response->assertOk();
        $response->assertSee($this->companyA1->name);

        // Other companies even within the same tenant are NOT in selectable options
        $response->assertDontSee($this->companyA2->name);
        // Foreign tenant company is NOT visible
        $response->assertDontSee($this->companyB1->name);
    }

    /**
     * TEST 3: Super Admin retains access across enterprise companies.
     */
    public function test_super_admin_retains_authorized_access(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.masters.index'));

        $response->assertOk();
        $response->assertSee($this->companyA1->name);
        $response->assertSee($this->companyA2->name);
        $response->assertSee($this->companyB1->name);
    }

    /**
     * TEST 4: Direct API request trying to create a master value for foreign tenant company is rejected.
     */
    public function test_direct_api_create_with_foreign_company_id_is_strictly_rejected(): void
    {
        $uniqueName = 'Tampered Foreign Master ' . uniqid();

        $response = $this->actingAs($this->tenantAdminA)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => $uniqueName,
            'code' => 'TIT_TAMPER_' . rand(100, 999),
            'scope' => 'selected_company',
            'company_id' => $this->companyB1->id, // Unauthorized foreign company ID!
        ]);

        $response->assertSessionHasErrors('company_id');
        $this->assertDatabaseMissing('onboarding_dropdown_masters', [
            'name' => $uniqueName,
        ]);
    }

    /**
     * TEST 5: Direct API request trying to create a master value for foreign branch is rejected.
     */
    public function test_direct_api_create_with_foreign_branch_id_is_strictly_rejected(): void
    {
        $response = $this->actingAs($this->tenantAdminA)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => 'Foreign Branch Master ' . uniqid(),
            'code' => 'TIT_BR_TAMPER_' . rand(100, 999),
            'scope' => 'selected_branch',
            'company_id' => $this->companyA1->id,
            'location_id' => $this->locationB1->id, // Foreign branch!
        ]);

        $response->assertSessionHasErrors('location_id');
    }

    /**
     * TEST 6: Tenant Admin cannot update or tamper with foreign tenant's master value.
     */
    public function test_tenant_admin_cannot_edit_foreign_tenant_master_value(): void
    {
        $foreignMaster = OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'Foreign Exclusive Title ' . uniqid(),
            'code' => 'TIT_FOR_' . rand(100, 999),
            'company_id' => $this->companyB1->id,
            'tenant_id' => $this->tenantB->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdminA)->put(route('admin.masters.update', $foreignMaster), [
            'name' => 'Attempted Foreign Hack',
            'code' => $foreignMaster->code,
            'is_active' => 1,
            'scope' => 'selected_company',
            'company_id' => $this->companyA1->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $foreignMaster->id,
            'name' => $foreignMaster->name, // Unchanged!
        ]);
    }

    /**
     * TEST 7: Tenant Admin cannot toggle status of foreign tenant's master value.
     */
    public function test_tenant_admin_cannot_toggle_foreign_tenant_master_status(): void
    {
        $foreignMaster = OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'Foreign Toggle Test ' . uniqid(),
            'code' => 'TIT_TOG_' . rand(100, 999),
            'company_id' => $this->companyB1->id,
            'tenant_id' => $this->tenantB->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdminA)
            ->patch(route('admin.masters.toggle-status', $foreignMaster));

        $response->assertForbidden();
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $foreignMaster->id,
            'is_active' => true, // Still active!
        ]);
    }

    /**
     * TEST 8: Company master listing page (/masters/companies) does not leak foreign companies.
     */
    public function test_company_masters_index_does_not_leak_foreign_companies(): void
    {
        $response = $this->actingAs($this->tenantAdminA)->get(route('companies.index'));

        $response->assertOk();
        $response->assertSee($this->companyA1->name);
        $response->assertSee($this->companyA2->name);
        $response->assertDontSee($this->companyB1->name);
    }

    /**
     * TEST 9: Branch Admin is restricted to authorized company and location.
     */
    public function test_branch_admin_is_restricted_to_authorized_company_and_branches(): void
    {
        $response = $this->actingAs($this->branchAdminA)->get(route('admin.masters.index'));

        $response->assertOk();
        $response->assertSee($this->companyA1->name);
        $response->assertDontSee($this->companyB1->name);

        // Attempting to create for unauthorized company is rejected
        $res = $this->actingAs($this->branchAdminA)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => 'Branch Admin Tamper ' . uniqid(),
            'code' => 'TIT_BR_TMP_' . rand(100, 999),
            'scope' => 'selected_company',
            'company_id' => $this->companyB1->id,
        ]);
        $res->assertSessionHasErrors('company_id');
    }

    /**
     * TEST 10: Updating an existing master to point to unauthorized company is strictly rejected.
     */
    public function test_tampered_direct_update_with_unauthorized_company_fails(): void
    {
        $myMaster = OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'My Valid Title ' . uniqid(),
            'code' => 'TIT_VAL_' . rand(100, 999),
            'company_id' => $this->companyA1->id,
            'tenant_id' => $this->tenantA->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdminA)->put(route('admin.masters.update', $myMaster), [
            'name' => $myMaster->name,
            'code' => $myMaster->code,
            'is_active' => 1,
            'scope' => 'selected_company',
            'company_id' => $this->companyB1->id, // Foreign company!
        ]);

        $response->assertSessionHasErrors('company_id');
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $myMaster->id,
            'company_id' => $this->companyA1->id, // Not changed to foreign company!
        ]);
    }
}
