<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OnboardingDropdownMaster;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class OnboardingDropdownMasterTest extends TestCase
{
    protected User $hoAdmin;
    protected User $employeeUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();
        $this->employeeUser = User::where('email', 'employee@vishal.com')->firstOrFail();
        $this->company = Company::where('code', 'VE')->firstOrFail();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_ho_admin_can_view_masters_index(): void
    {
        $response = $this->actingAs($this->hoAdmin)->get(route('admin.masters.index'));

        $response->assertOk();
        $response->assertSee('Dropdown Master Management');
        $response->assertSee('Titles / Salutations');
        $response->assertSee('Blood Groups');
    }

    public function test_non_ho_admin_cannot_access_masters_index(): void
    {
        $response = $this->actingAs($this->employeeUser)->get(route('admin.masters.index'));

        $response->assertForbidden();
    }

    public function test_ho_admin_can_create_new_master_entry(): void
    {
        $testCode = 'TEST_TITL_' . rand(1000, 9999);
        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'title',
            'name' => 'Prof. Dr. ' . $testCode,
            'code' => $testCode,
            'description' => 'Academic Title Test',
            'display_order' => 99,
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'category' => 'title',
            'code' => $testCode,
        ]);
    }

    public function test_ho_admin_can_toggle_master_status(): void
    {
        $master = OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'Status Toggle Test ' . uniqid(),
            'code' => 'ST_TOGGLE_' . rand(1000, 9999),
            'is_active' => true,
            'display_order' => 10,
        ]);

        $response = $this->actingAs($this->hoAdmin)
            ->patch(route('admin.masters.toggle-status', $master));

        $response->assertRedirect();
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $master->id,
            'is_active' => false,
        ]);
    }

    public function test_ho_admin_can_update_master_entry(): void
    {
        $master = OnboardingDropdownMaster::create([
            'category' => 'employee_grade',
            'name' => 'Grade X ' . uniqid(),
            'code' => 'GRD_X_' . rand(100, 999),
            'is_active' => true,
            'display_order' => 50,
        ]);

        $updatedName = 'Executive Grade Platinum ' . uniqid();
        $response = $this->actingAs($this->hoAdmin)
            ->put(route('admin.masters.update', $master), [
                'name' => $updatedName,
                'code' => $master->code,
                'description' => 'Updated Description',
                'display_order' => 60,
                'is_active' => '1',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $master->id,
            'name' => $updatedName,
            'display_order' => 60,
        ]);
    }

    public function test_dependent_options_endpoint_returns_children(): void
    {
        $parent = OnboardingDropdownMaster::create([
            'category' => 'qualification_level',
            'name' => 'Doctoral Degree Tier ' . uniqid(),
            'code' => 'DOC_TIER_' . rand(100, 999),
            'is_active' => true,
        ]);

        $child = OnboardingDropdownMaster::create([
            'category' => 'degree_name',
            'name' => 'Ph.D. in Industrial Metallurgy ' . uniqid(),
            'code' => 'PHD_MET_' . rand(100, 999),
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hoAdmin)
            ->get(route('admin.masters.dependent-options', ['parent_id' => $parent->id]));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $child->id,
            'name' => $child->name,
        ]);
    }

    public function test_onboarding_create_renders_dynamic_masters(): void
    {
        $testCode = 'SPEC_TITL_' . rand(1000, 9999);
        OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'SpecialTitle ' . $testCode,
            'code' => $testCode,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hoAdmin)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('SpecialTitle ' . $testCode);
        $response->assertSee('Dropdown Masters');
    }
}
