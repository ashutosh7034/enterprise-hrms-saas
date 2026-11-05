<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Location;
use App\Models\OnboardingDropdownMaster;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class MasterValueScopeDropdownTest extends TestCase
{
    protected User $hoAdmin;
    protected Company $companyA;
    protected Company $companyB;
    protected Location $branchA1;
    protected Location $branchB1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();

        $companies = Company::where('status', 'Active')->take(2)->get();
        $this->companyA = $companies[0];
        $this->companyB = $companies[1];

        $this->branchA1 = Location::firstOrCreate(
            ['code' => 'TEST_LOC_A_' . $this->companyA->id, 'company_id' => $this->companyA->id],
            ['name' => 'Test Location A1', 'status' => 'Active']
        );

        $this->branchB1 = Location::firstOrCreate(
            ['code' => 'TEST_LOC_B_' . $this->companyB->id, 'company_id' => $this->companyB->id],
            ['name' => 'Test Location B1', 'status' => 'Active']
        );

        app(CompanyContextService::class)->setActiveCompanyId($this->companyA->id);
    }

    public function test_masters_page_displays_all_scope_options_and_badges(): void
    {
        $response = $this->actingAs($this->hoAdmin)->get(route('admin.masters.index'));

        $response->assertOk();
        $response->assertSee('Global Enterprise Standard');
        $response->assertSee('Active Company Only');
        $response->assertSee('Selected Company');
        $response->assertSee('Selected Branch');
        $response->assertSee('Target Company');
        $response->assertSee('Target Branch / Location');
    }

    public function test_can_create_global_enterprise_standard_master_value(): void
    {
        $uniqueName = 'Global Standard Skill ' . uniqid();
        $uniqueCode = 'SKILL_GLB_' . rand(1000, 9999);

        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'skill_classification',
            'name' => $uniqueName,
            'code' => $uniqueCode,
            'scope' => 'global',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'category' => 'skill_classification',
            'code' => $uniqueCode,
            'company_id' => null,
            'location_id' => null,
        ]);

        $item = OnboardingDropdownMaster::where('code', $uniqueCode)->first();
        $this->assertEquals('global', $item->scope_type);
        $this->assertEquals('Global Enterprise Standard', $item->scope_badge['label']);
    }

    public function test_can_create_active_company_scoped_master_value(): void
    {
        $uniqueName = 'Active Co Dept ' . uniqid();
        $uniqueCode = 'DEPT_ACT_' . rand(1000, 9999);

        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'division',
            'name' => $uniqueName,
            'code' => $uniqueCode,
            'scope' => 'active_company',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'category' => 'division',
            'code' => $uniqueCode,
            'company_id' => $this->companyA->id,
            'location_id' => null,
        ]);

        $item = OnboardingDropdownMaster::where('code', $uniqueCode)->first();
        $this->assertEquals('selected_company', $item->scope_type);
        $this->assertStringContainsString('Company:', $item->scope_badge['label']);
    }

    public function test_can_create_selected_company_scoped_master_value(): void
    {
        $uniqueName = 'Selected Co Band ' . uniqid();
        $uniqueCode = 'BAND_SEL_' . rand(1000, 9999);

        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'employee_grade',
            'name' => $uniqueName,
            'code' => $uniqueCode,
            'scope' => 'selected_company',
            'company_id' => $this->companyB->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'category' => 'employee_grade',
            'code' => $uniqueCode,
            'company_id' => $this->companyB->id,
            'location_id' => null,
        ]);
    }

    public function test_can_create_selected_branch_scoped_master_value(): void
    {
        $uniqueName = 'Branch Plant Unit ' . uniqid();
        $uniqueCode = 'UNIT_BR_' . rand(1000, 9999);

        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'plant_unit',
            'name' => $uniqueName,
            'code' => $uniqueCode,
            'scope' => 'selected_branch',
            'company_id' => $this->companyA->id,
            'location_id' => $this->branchA1->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'category' => 'plant_unit',
            'code' => $uniqueCode,
            'company_id' => $this->companyA->id,
            'location_id' => $this->branchA1->id,
        ]);

        $item = OnboardingDropdownMaster::where('code', $uniqueCode)->first();
        $this->assertEquals('selected_branch', $item->scope_type);
        $this->assertStringContainsString('Branch:', $item->scope_badge['label']);
    }

    public function test_rejects_branch_from_mismatched_company(): void
    {
        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'plant_unit',
            'name' => 'Mismatched Branch Unit ' . uniqid(),
            'code' => 'UNIT_MISMATCH_' . rand(100, 999),
            'scope' => 'selected_branch',
            'company_id' => $this->companyA->id,
            'location_id' => $this->branchB1->id, // Belongs to company B!
        ]);

        $response->assertSessionHasErrors('location_id');
    }

    public function test_duplicate_master_value_is_prevented_within_same_scope(): void
    {
        $name = 'Duplicate Test Shift ' . uniqid();
        $code1 = 'DUP_1_' . rand(100, 999);
        $code2 = 'DUP_2_' . rand(100, 999);

        // First creation succeeds
        $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => $name,
            'code' => $code1,
            'scope' => 'global',
        ])->assertSessionHasNoErrors();

        // Second creation with same name in same global scope fails
        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => $name,
            'code' => $code2,
            'scope' => 'global',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_name_is_allowed_in_different_company_scopes(): void
    {
        $name = 'Shared Title Across Scopes ' . uniqid();

        // Scope 1: Company A
        $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => $name,
            'code' => 'TIT_CO_A_' . rand(100, 999),
            'scope' => 'selected_company',
            'company_id' => $this->companyA->id,
        ])->assertSessionHasNoErrors();

        // Scope 2: Company B (Same name, different scope)
        $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => $name,
            'code' => 'TIT_CO_B_' . rand(100, 999),
            'scope' => 'selected_company',
            'company_id' => $this->companyB->id,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(2, OnboardingDropdownMaster::where('name', $name)->count());
    }

    public function test_can_update_master_value_scope(): void
    {
        $master = OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'Scope Migration Test ' . uniqid(),
            'code' => 'SC_MIG_' . rand(100, 999),
            'company_id' => $this->companyA->id,
            'location_id' => null,
            'is_active' => true,
        ]);

        // Promote to Global
        $response = $this->actingAs($this->hoAdmin)->put(route('admin.masters.update', $master), [
            'name' => $master->name,
            'code' => $master->code,
            'is_active' => 1,
            'scope' => 'global',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $master->id,
            'company_id' => null,
            'location_id' => null,
        ]);
    }

    public function test_tenant_and_company_isolation_in_options_query(): void
    {
        $cat = 'blood_group';
        $globalName = 'Global Universal Type ' . uniqid();
        $compAName = 'Company A Exclusive Type ' . uniqid();
        $compBName = 'Company B Exclusive Type ' . uniqid();

        OnboardingDropdownMaster::create([
            'category' => $cat,
            'name' => $globalName,
            'code' => 'BG_GLB_' . rand(100, 999),
            'company_id' => null,
            'location_id' => null,
            'is_active' => true,
        ]);

        OnboardingDropdownMaster::create([
            'category' => $cat,
            'name' => $compAName,
            'code' => 'BG_CO_A_' . rand(100, 999),
            'company_id' => $this->companyA->id,
            'location_id' => null,
            'is_active' => true,
        ]);

        OnboardingDropdownMaster::create([
            'category' => $cat,
            'name' => $compBName,
            'code' => 'BG_CO_B_' . rand(100, 999),
            'company_id' => $this->companyB->id,
            'location_id' => null,
            'is_active' => true,
        ]);

        // When queried for Company A:
        $optionsA = OnboardingDropdownMaster::getOptions($cat, $this->companyA->id);
        $namesA = $optionsA->pluck('name')->all();

        $this->assertContains($globalName, $namesA, 'Global values must be visible to Company A');
        $this->assertContains($compAName, $namesA, 'Company A values must be visible to Company A');
        $this->assertNotContains($compBName, $namesA, 'Company B values MUST BE ISOLATED and NOT visible to Company A');
    }
}
