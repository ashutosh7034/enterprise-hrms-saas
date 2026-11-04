<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CompanyContextService;
use Database\Seeders\TenantSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    protected PlatformUser $platformAdmin;
    protected Tenant $vishalTenant;
    protected Company $companyVE;
    protected Company $companyVRE;
    protected User $hoAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure Tenant Seeder has run
        $this->seed(TenantSeeder::class);

        $this->vishalTenant = Tenant::where('tenant_code', 'TNT-VISHAL')->firstOrFail();
        $this->companyVE = Company::where('code', 'VE')->firstOrFail();
        $this->companyVRE = Company::where('code', 'VRE')->firstOrFail();

        // 2. Setup Platform Super Admin
        $this->platformAdmin = PlatformUser::updateOrCreate(
            ['email' => 'test_tenant_superadmin@advaititsolutions.com'],
            [
                'name' => 'Advait Platform Super Admin',
                'password' => Hash::make('SuperAdminPass@2026#Secure'),
                'status' => 'active',
            ]
        );

        // 3. Setup HO Admin User
        $hoRole = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $this->hoAdminUser = User::firstOrCreate(
            ['email' => 'test_ho_admin_isolation@vishalenterprise.com'],
            [
                'name' => 'Vikram Singhania HO Test',
                'password' => Hash::make('password123'),
                'company_id' => $this->companyVE->id,
                'role_id' => $hoRole->id,
                'is_active' => true,
            ]
        );
    }

    /** 1. Tenant seeder maps VE and VRE to Vishal Group */
    public function test_tenant_seeder_maps_vishal_enterprise_and_vrishal_eng_to_vishal_group(): void
    {
        $this->assertNotNull($this->vishalTenant);
        $this->assertEquals('Vishal Group', $this->vishalTenant->name);
        $this->assertEquals('vishal-group', $this->vishalTenant->slug);
        $this->assertEquals('active', $this->vishalTenant->status);

        $this->companyVE->refresh();
        $this->companyVRE->refresh();

        $this->assertEquals($this->vishalTenant->id, $this->companyVE->tenant_id);
        $this->assertEquals($this->vishalTenant->id, $this->companyVRE->tenant_id);
    }

    /** 2. Tenant seeder is idempotent */
    public function test_tenant_seeder_is_idempotent(): void
    {
        $initialCount = Tenant::where('tenant_code', 'TNT-VISHAL')->count();
        $this->assertEquals(1, $initialCount);

        // Run seeder again
        $this->seed(TenantSeeder::class);

        $this->assertEquals(1, Tenant::where('tenant_code', 'TNT-VISHAL')->count());
        $this->assertEquals($this->vishalTenant->id, Company::where('code', 'VE')->value('tenant_id'));
        $this->assertEquals($this->vishalTenant->id, Company::where('code', 'VRE')->value('tenant_id'));
    }

    /** 3. Tenant hasMany companies relationship */
    public function test_tenant_has_many_companies_relationship(): void
    {
        $companies = $this->vishalTenant->companies;
        $this->assertGreaterThanOrEqual(2, $companies->count());
        $this->assertTrue($companies->contains('id', $this->companyVE->id));
        $this->assertTrue($companies->contains('id', $this->companyVRE->id));
    }

    /** 4. Company belongsTo tenant relationship */
    public function test_company_belongs_to_tenant_relationship(): void
    {
        $this->assertNotNull($this->companyVE->tenant);
        $this->assertEquals($this->vishalTenant->id, $this->companyVE->tenant->id);
        $this->assertEquals('TNT-VISHAL', $this->companyVE->tenant->tenant_code);

        $this->assertNotNull($this->companyVRE->tenant);
        $this->assertEquals($this->vishalTenant->id, $this->companyVRE->tenant->id);
    }

    /** 5. User tenant helper returns assigned tenant */
    public function test_user_tenant_helper_returns_assigned_tenant(): void
    {
        $tenant = $this->hoAdminUser->tenant();
        $this->assertNotNull($tenant);
        $this->assertEquals($this->vishalTenant->id, $tenant->id);
        $this->assertEquals('TNT-VISHAL', $tenant->tenant_code);
    }

    /** 6. Platform super admin can view customer tenants list */
    public function test_platform_super_admin_can_view_tenants_list(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->get(route('platform.tenants.index', ['search' => 'TNT-VISHAL']));

        $response->assertStatus(200);
        $response->assertSee('Customer Tenant Organizations');
        $response->assertSee('TNT-VISHAL');
        $response->assertSee('Vishal Group');
    }

    /** 7. Platform super admin can create new customer tenant */
    public function test_platform_super_admin_can_create_new_tenant(): void
    {
        // Cleanup if prior run left it
        Tenant::where('tenant_code', 'TNT-ACMETEST')->delete();

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.store'), [
                'tenant_code' => 'tnt-acmetest', // Should be normalized to TNT-ACMETEST
                'name' => 'Acme Test Corp',
                'slug' => 'ACME-TEST-CORP',      // Should be normalized to acme-test-corp
                'status' => 'active',
                'contact_name' => 'Alice Test',
                'contact_email' => 'alice@acmetest.com',
                'contact_phone' => '+91 9988776655',
                'address' => 'Industrial Area, Phase 1',
                'notes' => 'Test customer organization for Phase 2',
            ]);

        $newTenant = Tenant::where('tenant_code', 'TNT-ACMETEST')->first();
        $this->assertNotNull($newTenant);
        $this->assertEquals('TNT-ACMETEST', $newTenant->tenant_code);
        $this->assertEquals('acme-test-corp', $newTenant->slug);
        $this->assertEquals('active', $newTenant->status);

        $response->assertRedirect(route('platform.tenants.show', $newTenant));

        // Verify Platform Audit Log was generated
        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'tenant_id' => $newTenant->id,
            'action' => 'TENANT_CREATED',
        ]);
    }

    /** 8. Tenant creation validation enforces unique code and slug */
    public function test_tenant_creation_validation_rules(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.store'), [
                'tenant_code' => 'TNT-VISHAL', // Duplicate
                'name' => 'Duplicate Tenant',
                'slug' => 'vishal-group',      // Duplicate
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors(['tenant_code', 'slug']);
    }

    /** 9. Platform super admin can view tenant details without exposing compensation data */
    public function test_platform_super_admin_can_view_tenant_details_without_employee_compensation_exposure(): void
    {
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->get(route('platform.tenants.show', $this->vishalTenant));

        $response->assertStatus(200);
        $response->assertSee('TNT-VISHAL');
        $response->assertSee('Vishal Enterprise');
        $response->assertSee('Vrishal Eng. Pvt. Ltd.');

        // Verify privacy boundaries: no salary, CTC, or employee payroll leaks
        $response->assertDontSee('basic_salary');
        $response->assertDontSee('salary_slips');
        $response->assertDontSee('cost_to_company');
        $response->assertSee('Zero Operational Exposure');
    }

    /** 10. Platform super admin can update tenant metadata */
    public function test_platform_super_admin_can_update_tenant_metadata(): void
    {
        $testTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-UPDATETEST'],
            [
                'name' => 'Original Name Corp',
                'slug' => 'original-name-corp',
                'status' => 'active',
                'contact_name' => 'Bob',
            ]
        );

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->put(route('platform.tenants.update', $testTenant), [
                'tenant_code' => 'TNT-UPDATETEST',
                'name' => 'Updated Name Corp',
                'slug' => 'updated-name-corp',
                'status' => 'active',
                'contact_name' => 'Robert Updated',
                'contact_email' => 'robert@updated.com',
                'contact_phone' => '+91 99999 88888',
                'address' => 'Updated Street 42',
                'notes' => 'Updated metadata note',
            ]);

        $response->assertRedirect(route('platform.tenants.show', $testTenant));

        $testTenant->refresh();
        $this->assertEquals('Updated Name Corp', $testTenant->name);
        $this->assertEquals('updated-name-corp', $testTenant->slug);
        $this->assertEquals('Robert Updated', $testTenant->contact_name);

        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'tenant_id' => $testTenant->id,
            'action' => 'TENANT_UPDATED',
        ]);
    }

    /** 11. Platform super admin can update tenant operational status */
    public function test_platform_super_admin_can_update_tenant_status(): void
    {
        $testTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-STATUSTEST'],
            [
                'name' => 'Status Test Tenant',
                'slug' => 'status-test-tenant',
                'status' => 'active',
            ]
        );

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.status', $testTenant), [
                'status' => 'suspended',
            ]);

        $response->assertRedirect();
        $testTenant->refresh();
        $this->assertEquals('suspended', $testTenant->status);

        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->platformAdmin->id,
            'tenant_id' => $testTenant->id,
            'action' => 'TENANT_STATUS_CHANGED',
        ]);
    }

    /** 12. HRMS users cannot access platform tenants endpoints */
    public function test_hrms_users_cannot_access_platform_tenants_endpoints(): void
    {
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('platform.tenants.index'));

        // Should be redirected to platform login
        $response->assertRedirect(route('platform.login'));
    }

    /** 13. Unauthenticated users cannot access platform tenants */
    public function test_unauthenticated_users_cannot_access_platform_tenants(): void
    {
        $response = $this->get(route('platform.tenants.index'));
        $response->assertRedirect(route('platform.login'));
    }

    /** 14. CompanyContextService scopes accessible companies to user tenant */
    public function test_company_context_service_scopes_accessible_companies_to_user_tenant(): void
    {
        // Create an isolated Tenant B with Company B
        $tenantB = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-ISOB'],
            ['name' => 'Isolated Tenant B', 'slug' => 'isolated-tenant-b', 'status' => 'active']
        );

        $companyB = Company::firstOrCreate(
            ['code' => 'CO-ISOB'],
            ['name' => 'Isolated Company B', 'status' => 'Active', 'tenant_id' => $tenantB->id]
        );

        // Authenticate as Vishal Group HO Admin
        Auth::guard('web')->login($this->hoAdminUser);

        $service = app(CompanyContextService::class);
        $accessibleCompanies = $service->getAccessibleCompanies();

        // Must include Vishal Group companies
        $this->assertTrue($accessibleCompanies->contains('id', $this->companyVE->id));
        $this->assertTrue($accessibleCompanies->contains('id', $this->companyVRE->id));

        // Must NEVER include Tenant B company
        $this->assertFalse($accessibleCompanies->contains('id', $companyB->id));
    }

    /** 15. HO Admin cannot switch to company in a different tenant */
    public function test_ho_admin_cannot_switch_to_company_in_different_tenant(): void
    {
        $tenantB = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-CROSSFAIL'],
            ['name' => 'Cross Fail Tenant', 'slug' => 'cross-fail-tenant', 'status' => 'active']
        );

        $companyB = Company::firstOrCreate(
            ['code' => 'CO-CROSSFAIL'],
            ['name' => 'Cross Fail Company', 'status' => 'Active', 'tenant_id' => $tenantB->id]
        );

        Auth::guard('web')->login($this->hoAdminUser);
        Session::put('active_company_id', $this->companyVE->id);

        $service = app(CompanyContextService::class);
        $switched = $service->setActiveCompanyId($companyB->id);

        $this->assertFalse($switched, 'HO Admin should not be allowed to switch to a company in another tenant');
        $this->assertNotEquals($companyB->id, $service->getActiveCompanyId());
        $this->assertEquals($this->companyVE->id, $service->getActiveCompanyId());
    }

    /** 16. HO Admin can switch between companies within the same tenant */
    public function test_ho_admin_can_switch_between_companies_within_same_tenant(): void
    {
        Auth::guard('web')->login($this->hoAdminUser);
        Session::put('active_company_id', $this->companyVE->id);

        $service = app(CompanyContextService::class);
        $switched = $service->setActiveCompanyId($this->companyVRE->id);

        $this->assertTrue($switched, 'HO Admin should be permitted to switch between companies within the same tenant');
        $this->assertEquals($this->companyVRE->id, $service->getActiveCompanyId());
    }

    /** 17. Suspended tenant user is blocked by EnsureTenantIsActive middleware */
    public function test_suspended_tenant_user_is_blocked_by_middleware(): void
    {
        $suspendedTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-SUSPENDED'],
            ['name' => 'Suspended Corp', 'slug' => 'suspended-corp', 'status' => 'suspended']
        );

        $suspendedCompany = Company::firstOrCreate(
            ['code' => 'CO-SUSP'],
            ['name' => 'Suspended Company', 'status' => 'Active', 'tenant_id' => $suspendedTenant->id]
        );

        $employeeRole = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee ESS']);
        $suspendedUser = User::firstOrCreate(
            ['email' => 'test_user_suspended@example.com'],
            [
                'name' => 'Suspended User',
                'password' => Hash::make('password'),
                'company_id' => $suspendedCompany->id,
                'role_id' => $employeeRole->id,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($suspendedUser, 'web')
            ->get(route('dashboard'));

        // Should be logged out and redirected to login with error
        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());
        $response->assertSessionHasErrors('email');
    }

    /** 18. Tenant reactivation restores user access */
    public function test_tenant_reactivation_restores_user_access(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-REACTIVATE'],
            ['name' => 'Reactivate Corp', 'slug' => 'reactivate-corp', 'status' => 'suspended']
        );
        $tenant->update(['status' => 'suspended']);

        $plan = \App\Models\SubscriptionPlan::where('plan_code', 'PLAN-ENTERPRISE')->first();
        if ($plan) {
            \App\Models\TenantSubscription::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'subscription_code' => 'SUB-REACTIVATE-2026',
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addYear(),
                    'grace_period_ends_at' => now()->addYear()->addDays(14),
                ]
            );
        }

        $company = Company::firstOrCreate(
            ['code' => 'CO-REACT'],
            ['name' => 'Reactivate Company', 'status' => 'Active', 'tenant_id' => $tenant->id]
        );

        $employeeRole = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee ESS']);
        $user = User::firstOrCreate(
            ['email' => 'test_user_reactivate@example.com'],
            [
                'name' => 'Reactivated User',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'role_id' => $employeeRole->id,
                'is_active' => true,
            ]
        );

        // 1. While suspended, access is denied
        $response1 = $this->actingAs($user, 'web')->get(route('dashboard'));
        $response1->assertRedirect(route('login'));

        // 2. Platform Super Admin reactivates tenant
        $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.status', $tenant), ['status' => 'active']);

        $tenant->refresh();
        $this->assertEquals('active', $tenant->status);

        // 3. User can now access application
        $user->unsetRelations();
        $response2 = $this->actingAs($user, 'web')->get(route('dashboard'));
        $this->assertTrue(in_array($response2->status(), [200, 302]));
        $this->assertTrue(Auth::guard('web')->check());
    }

    /** 19. Active tenant user can access protected application routes */
    public function test_active_tenant_user_can_access_application(): void
    {
        $this->vishalTenant->update(['status' => 'active']);

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('dashboard'));

        // HO Admin dashboard should be accessible (status 200 or redirect to specific role dashboard)
        $this->assertTrue(in_array($response->status(), [200, 302]));
        $this->assertTrue(Auth::guard('web')->check());
    }

    /** 19. Platform super admin is not affected by tenant suspension */
    public function test_platform_super_admin_is_not_affected_by_tenant_suspension(): void
    {
        $suspendedTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-SUSP-ADM'],
            ['name' => 'Suspended Admin Corp', 'slug' => 'suspended-admin-corp', 'status' => 'suspended']
        );

        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->get(route('platform.tenants.show', $suspendedTenant));

        $response->assertStatus(200);
        $response->assertSee('Suspended Admin Corp');
        $response->assertSee('Suspended');
    }

    /** 20. Inactive tenant user is blocked by EnsureTenantIsActive middleware */
    public function test_inactive_tenant_user_is_blocked_by_middleware(): void
    {
        $inactiveTenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-INACTIVE-TEST'],
            ['name' => 'Inactive Corp', 'slug' => 'inactive-corp', 'status' => 'inactive']
        );

        $inactiveCompany = Company::firstOrCreate(
            ['code' => 'CO-INACT'],
            ['name' => 'Inactive Company', 'status' => 'Active', 'tenant_id' => $inactiveTenant->id]
        );

        $employeeRole = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee ESS']);
        $inactiveUser = User::firstOrCreate(
            ['email' => 'test_user_inactive_tenant@example.com'],
            [
                'name' => 'Inactive Tenant User',
                'password' => Hash::make('password'),
                'company_id' => $inactiveCompany->id,
                'role_id' => $employeeRole->id,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($inactiveUser, 'web')
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());
        $response->assertSessionHasErrors('email');
    }

    /** 21. Null/Unassigned tenant user is blocked by EnsureTenantIsActive middleware */
    public function test_null_tenant_user_is_blocked_by_middleware(): void
    {
        $unassignedCompany = Company::firstOrCreate(
            ['code' => 'CO-UNASSIGNED'],
            ['name' => 'Unassigned Company', 'status' => 'Active', 'tenant_id' => null]
        );

        $employeeRole = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee ESS']);
        $unassignedUser = User::firstOrCreate(
            ['email' => 'test_user_null_tenant@example.com'],
            [
                'name' => 'Null Tenant User',
                'password' => Hash::make('password'),
                'company_id' => $unassignedCompany->id,
                'role_id' => $employeeRole->id,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($unassignedUser, 'web')
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());
        $response->assertSessionHasErrors('email');
    }

    /** 22. Unassigned tenant user cannot switch to any tenant company */
    public function test_unassigned_tenant_user_cannot_switch_to_tenant_company(): void
    {
        $unassignedCompany = Company::firstOrCreate(
            ['code' => 'CO-UNASSIGNED-2'],
            ['name' => 'Unassigned Company 2', 'status' => 'Active', 'tenant_id' => null]
        );

        $hoRole = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $unassignedAdmin = User::firstOrCreate(
            ['email' => 'test_admin_null_tenant@example.com'],
            [
                'name' => 'Null Tenant Admin',
                'password' => Hash::make('password'),
                'company_id' => $unassignedCompany->id,
                'role_id' => $hoRole->id,
                'is_active' => true,
            ]
        );

        Auth::guard('web')->login($unassignedAdmin);

        $service = app(CompanyContextService::class);
        $switched = $service->setActiveCompanyId($this->companyVE->id);

        $this->assertFalse($switched, 'User with null tenant must not switch to tenant companies');
    }

    /** 23. Employee IDOR cross-tenant access is blocked */
    public function test_employee_direct_access_idor_blocked_across_tenants(): void
    {
        // Tenant B with Company B and Employee B
        $tenantB = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-EMPB'],
            ['name' => 'Tenant B Corp', 'slug' => 'tenant-b-corp', 'status' => 'active']
        );

        $companyB = Company::firstOrCreate(
            ['code' => 'CO-EMPB'],
            ['name' => 'Company B Corp', 'status' => 'Active', 'tenant_id' => $tenantB->id]
        );

        $locationB = \App\Models\Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyB->id, 'name' => 'Plant B'],
            ['code' => 'PL-B', 'status' => 'Active']
        );
        $departmentB = \App\Models\Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyB->id, 'name' => 'Engineering B'],
            ['code' => 'ENG-B', 'status' => 'Active']
        );
        $designation = \App\Models\Designation::withoutGlobalScopes()->first();
        $categoryB = \App\Models\Category::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $companyB->id, 'name' => 'Staff B'],
            ['code' => 'STF-B', 'status' => 'Active']
        );

        $employeeB = \App\Models\Employee::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyB->id, 'employee_code' => 'EMP-B-001'],
            [
                'first_name' => 'Bob',
                'last_name' => 'TenantB',
                'email' => 'bob@tenantb.com',
                'phone' => '+91 9123456789',
                'gender' => 'Male',
                'date_of_birth' => '1995-05-15',
                'date_of_joining' => '2024-01-01',
                'employment_channel' => 'Direct',
                'employee_type' => 'Permanent',
                'location_id' => $locationB->id,
                'department_id' => $departmentB->id,
                'designation_id' => $designation->id,
                'category_id' => $categoryB->id,
                'status' => 'Active',
            ]
        );

        // Attempt to access Employee B as Vishal Group HO Admin
        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('employees.show', $employeeB->id));

        // Must be rejected (either 404 via BelongsToCompany global scope or 403)
        $this->assertTrue(in_array($response->status(), [404, 403]));
    }

    /** 24. Employee Edit IDOR cross-tenant access is blocked */
    public function test_employee_edit_idor_blocked_across_tenants(): void
    {
        $companyB = Company::where('code', 'CO-EMPB')->firstOrFail();
        $employeeB = \App\Models\Employee::withoutGlobalScopes()->where('company_id', $companyB->id)->firstOrFail();

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->get(route('employees.edit', $employeeB->id));

        $this->assertTrue(in_array($response->status(), [404, 403]));
    }

    /** 25. Platform admin credentials cannot authenticate via HRMS web guard */
    public function test_platform_admin_cannot_authenticate_as_hrms_user(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => $this->platformAdmin->email,
            'password' => 'SuperAdminPass@2026#Secure',
        ]);

        $this->assertFalse(Auth::guard('web')->check());
    }

    /** 26. HRMS user credentials cannot authenticate via platform guard */
    public function test_hrms_user_cannot_authenticate_as_platform_user(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->hoAdminUser->email,
            'password' => 'password123',
        ]);

        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** 27. Employee Update IDOR cross-tenant modification is blocked */
    public function test_employee_update_idor_blocked_across_tenants(): void
    {
        $companyB = Company::where('code', 'CO-EMPB')->firstOrFail();
        $employeeB = \App\Models\Employee::withoutGlobalScopes()->where('company_id', $companyB->id)->firstOrFail();

        $response = $this->actingAs($this->hoAdminUser, 'web')
            ->put(route('employees.update', $employeeB->id), [
                'first_name' => 'HackedName',
            ]);

        $this->assertTrue(in_array($response->status(), [404, 403]));
        $employeeB->refresh();
        $this->assertEquals('Bob', $employeeB->first_name);
    }

    /** 28. Platform Audit Log records actor, action, tenant, IP and metadata */
    public function test_platform_audit_log_records_ip_and_metadata_for_tenant_actions(): void
    {
        $tenantCode = 'TNT-AUDIT-TEST';
        Tenant::where('tenant_code', $tenantCode)->delete();
        $response = $this->actingAs($this->platformAdmin, 'platform')
            ->post(route('platform.tenants.store'), [
                'tenant_code' => $tenantCode,
                'name' => 'Audit Verify Corp',
                'slug' => strtolower($tenantCode),
                'status' => 'active',
                'contact_email' => 'audit@verify.com',
            ]);

        $createdTenant = Tenant::where('tenant_code', $tenantCode)->firstOrFail();
        $log = PlatformAuditLog::where('tenant_id', $createdTenant->id)
            ->where('action', 'TENANT_CREATED')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->platformAdmin->id, $log->platform_user_id);
        $this->assertNotEmpty($log->ip_address);
        $this->assertIsArray($log->metadata);
        $this->assertEquals($tenantCode, $log->metadata['tenant_code']);
    }
}

