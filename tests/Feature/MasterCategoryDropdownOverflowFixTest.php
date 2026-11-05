<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\OnboardingDropdownMaster;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class MasterCategoryDropdownOverflowFixTest extends TestCase
{
    protected User $hoAdmin;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();
        $this->company = Company::where('code', 'VE')->firstOrFail();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_add_master_value_modal_renders_contained_custom_category_dropdown(): void
    {
        $response = $this->actingAs($this->hoAdmin)->get(route('admin.masters.index'));

        $response->assertOk();

        // Modal container and custom select
        $response->assertSee('id="createMasterModal"', false);
        $response->assertSee('custom-master-select');
        $response->assertSee('id="createMasterCategoryInput"', false);
        $response->assertSee('id="createMasterCategoryBtn"', false);
        $response->assertSee('master-category-menu');
        $response->assertSee('id="categorySearchInput"', false);
        $response->assertSee('master-category-options-list');

        // Check categories are rendered inside the dropdown
        $response->assertSee('Titles / Salutations');
        $response->assertSee('Blood Groups');
        $response->assertSee('Qualification Levels');
        $response->assertSee('Employee Grades / Bands');
        $response->assertSee('Approved Commercial Banks');
        $response->assertSee('Bank Account Types');
    }

    public function test_save_master_value_works_with_custom_category_selection(): void
    {
        $testCode = 'TEST_CAT_' . rand(1000, 9999);
        $response = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
            'category' => 'blood_group',
            'name' => 'Bombay Phenotype ' . $testCode,
            'code' => $testCode,
            'description' => 'Rare blood group test',
            'display_order' => 15,
            'scope' => 'company',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.masters.index', ['category' => 'blood_group']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'category' => 'blood_group',
            'name' => 'Bombay Phenotype ' . $testCode,
            'code' => $testCode,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_save_master_value_across_various_categories(): void
    {
        $categories = [
            'bank_name' => 'State Bank of Mysore',
            'bank_account_type' => 'NRE Savings',
            'qualification_level' => 'Post-Doctoral Fellowship',
            'visa_type' => 'Business Visa B-1',
            'employee_grade' => 'Senior Director E8',
        ];

        foreach ($categories as $cat => $name) {
            $code = 'TST_' . strtoupper(substr($cat, 0, 4)) . '_' . rand(100, 999);
            $res = $this->actingAs($this->hoAdmin)->post(route('admin.masters.store'), [
                'category' => $cat,
                'name' => $name . ' ' . $code,
                'code' => $code,
                'display_order' => 1,
                'scope' => 'company',
            ]);

            $res->assertRedirect(route('admin.masters.index', ['category' => $cat]));
            $this->assertDatabaseHas('onboarding_dropdown_masters', [
                'category' => $cat,
                'code' => $code,
            ]);
        }
    }

    public function test_edit_master_value_functionality_remains_intact(): void
    {
        $master = OnboardingDropdownMaster::create([
            'category' => 'title',
            'name' => 'Original Name ' . rand(100, 999),
            'code' => 'ORIG_' . rand(100, 999),
            'company_id' => $this->company->id,
            'tenant_id' => 1,
            'is_active' => true,
            'display_order' => 1,
        ]);

        $updatedName = 'Updated Name Master ' . rand(1000, 9999);
        $response = $this->actingAs($this->hoAdmin)->put(route('admin.masters.update', $master->id), [
            'name' => $updatedName,
            'code' => $master->code,
            'display_order' => 5,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.masters.index', ['category' => 'title']));
        $this->assertDatabaseHas('onboarding_dropdown_masters', [
            'id' => $master->id,
            'name' => $updatedName,
            'display_order' => 5,
        ]);
    }
}
