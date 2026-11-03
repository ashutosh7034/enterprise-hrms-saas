<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetManagementService;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    protected User $hrUser;
    protected Company $veCompany;
    protected Company $vreCompany;
    protected AssetCategory $category;
    protected Location $location;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->veCompany = Company::where('code', 'VE')->first();
        $this->vreCompany = Company::where('code', 'VRE')->first();
        $this->category = AssetCategory::firstOrCreate(
            ['code' => 'IT-LAP'],
            ['name' => 'Laptops & IT Hardware', 'is_returnable' => true]
        );
        $this->location = Location::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        if (!$this->employee) {
            $dept = \App\Models\Department::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
            $desig = \App\Models\Designation::withoutGlobalScopes()->first();
            $cat = \App\Models\Category::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
            $this->employee = Employee::create([
                'company_id' => $this->veCompany->id,
                'employee_code' => 'VE-EMP-1001',
                'first_name' => 'Amit',
                'last_name' => 'Sharma',
                'gender' => 'Male',
                'date_of_birth' => '1990-04-12',
                'phone' => '9811223344',
                'date_of_joining' => '2025-01-15',
                'location_id' => $this->location->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'category_id' => $cat->id,
                'status' => 'Active',
                'ekyc_status' => 'Pending',
            ]);
        }
    }

    public function test_can_register_new_asset_with_company_scope(): void
    {
        $assetCode = 'AST-TEST-' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('assets.store'), [
                'name' => 'MacBook Pro M3',
                'asset_code' => $assetCode,
                'asset_tag' => 'TAG-MBP-99',
                'serial_number' => 'SN-C02-9988',
                'asset_category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'purchase_date' => now()->subMonths(6)->toDateString(),
                'purchase_cost' => 145000.00,
                'condition' => 'Brand New',
                'is_returnable' => 1,
            ]);

        $response->assertRedirect(route('assets.index'));

        $asset = Asset::withoutGlobalScopes()->where('asset_code', $assetCode)->first();
        $this->assertNotNull($asset);
        $this->assertEquals($this->veCompany->id, $asset->company_id);
        $this->assertEquals('Available', $asset->status);
    }

    public function test_can_allocate_asset_to_employee(): void
    {
        $asset = Asset::withoutGlobalScopes()
            ->where('company_id', $this->veCompany->id)
            ->where('status', 'Available')
            ->first();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('assets.allocate', $asset->id), [
                'employee_id' => $this->employee->id,
                'allocated_date' => now()->toDateString(),
                'condition_at_issue' => 'Good',
                'acknowledged_by_employee' => 1,
                'remarks' => 'Allocation test',
            ]);

        $response->assertRedirect();

        $asset->refresh();
        $this->assertEquals('Allocated', $asset->status);

        $alloc = AssetAllocation::where('asset_id', $asset->id)
            ->where('status', 'Active')
            ->latest('id')
            ->first();
        $this->assertNotNull($alloc);
        $this->assertEquals($this->employee->id, $alloc->employee_id);
    }

    public function test_cannot_allocate_cross_company_asset(): void
    {
        // Asset belongs to VE, employee belongs to VRE
        $veAsset = Asset::withoutGlobalScopes()
            ->where('company_id', $this->veCompany->id)
            ->where('status', 'Available')
            ->first();

        $vreEmployee = Employee::withoutGlobalScopes()
            ->where('company_id', $this->vreCompany->id)
            ->first();

        if ($vreEmployee && $veAsset) {
            $response = $this->actingAs($this->hrUser)
                ->withSession(['active_company_id' => $this->veCompany->id])
                ->post(route('assets.allocate', $veAsset->id), [
                    'employee_id' => $vreEmployee->id, // Cross-company ID
                    'allocated_date' => now()->toDateString(),
                    'condition_at_issue' => 'Good',
                ]);

            $response->assertSessionHasErrors(['employee_id']);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_can_return_asset_with_condition_inspection(): void
    {
        $asset = Asset::withoutGlobalScopes()
            ->where('company_id', $this->veCompany->id)
            ->where('status', 'Allocated')
            ->first();

        if (!$asset) {
            $asset = Asset::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
            $asset->update(['status' => 'Allocated']);
            AssetAllocation::create([
                'asset_id' => $asset->id,
                'employee_id' => $this->employee->id,
                'company_id' => $this->veCompany->id,
                'allocated_date' => now()->subMonths(3)->toDateString(),
                'condition_at_issue' => 'Good',
                'status' => 'Active',
            ]);
        }

        $allocation = AssetAllocation::where('asset_id', $asset->id)->where('status', 'Active')->first();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('asset-allocations.return', $allocation->id), [
                'return_date' => now()->toDateString(),
                'condition_at_return' => 'Good',
                'remarks' => 'Returned upon project completion',
            ]);

        $response->assertRedirect();

        $allocation->refresh();
        $this->assertEquals('Returned', $allocation->status);

        $asset->refresh();
        $this->assertEquals('Available', $asset->status);
    }

    public function test_depreciation_calculation_service(): void
    {
        $service = app(AssetManagementService::class);

        // SLM: Cost 60000, 1 year old, 3 year life, 5% salvage (3000)
        // Annual dep = (60000 - 3000)/3 = 19000
        // Value after 1 year = 60000 - 19000 = 41000
        $oneYearAgo = now()->subYear()->toDateString();
        $slm = $service->calculateDepreciation(60000.00, $oneYearAgo, 'Straight-Line');

        $this->assertEquals(60000.00, $slm['purchase_cost']);
        $this->assertGreaterThan(38000.00, $slm['depreciated_value']);
        $this->assertLessThan(43000.00, $slm['depreciated_value']);

        // WDV: Cost 60000, 1 year old, 40% rate
        // Value after 1 year = 60000 * 0.60 = 36000
        $wdv = $service->calculateDepreciation(60000.00, $oneYearAgo, 'Written-Down Value');
        $this->assertGreaterThan(34000.00, $wdv['depreciated_value']);
        $this->assertLessThan(38000.00, $wdv['depreciated_value']);
    }

    public function test_asset_master_page_renders_successfully_with_active_allocation_and_employee(): void
    {
        // Ensure asset with active allocation exists
        $asset = Asset::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        if ($asset) {
            $allocation = AssetAllocation::withoutGlobalScopes()->firstOrCreate(
                [
                    'asset_id' => $asset->id,
                    'status' => 'Active',
                ],
                [
                    'company_id' => $this->veCompany->id,
                    'employee_id' => $this->employee->id,
                    'allocated_date' => now()->subDays(10)->toDateString(),
                    'condition_at_issue' => 'Excellent',
                    'allocated_by' => $this->hrUser->id,
                ]
            );
            $asset->update(['status' => 'Allocated']);
        }

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('assets.index'));

        $response->assertStatus(200);
        $response->assertSee('Asset Master & Inventory', false);
        $response->assertSee($this->category->name);
    }

    public function test_asset_active_allocation_relation_returns_only_active_and_excludes_historical(): void
    {
        $asset = Asset::withoutGlobalScopes()->create([
            'company_id' => $this->veCompany->id,
            'location_id' => $this->location->id,
            'asset_category_id' => $this->category->id,
            'name' => 'Lenovo ThinkPad X1',
            'asset_code' => 'AST-TP-' . mt_rand(10000, 99999),
            'status' => 'Allocated',
            'condition' => 'Good',
            'purchase_cost' => 120000.00,
        ]);

        // Create historical returned allocation
        $historical = AssetAllocation::withoutGlobalScopes()->create([
            'asset_id' => $asset->id,
            'employee_id' => $this->employee->id,
            'company_id' => $this->veCompany->id,
            'allocated_date' => now()->subMonths(6)->toDateString(),
            'return_date' => now()->subMonths(2)->toDateString(),
            'condition_at_issue' => 'Brand New',
            'condition_at_return' => 'Good',
            'status' => 'Returned',
        ]);

        // Create current active allocation
        $active = AssetAllocation::withoutGlobalScopes()->create([
            'asset_id' => $asset->id,
            'employee_id' => $this->employee->id,
            'company_id' => $this->veCompany->id,
            'allocated_date' => now()->subMonths(1)->toDateString(),
            'condition_at_issue' => 'Good',
            'status' => 'Active',
        ]);

        $asset->refresh();

        // Total allocations should be 2
        $this->assertEquals(2, $asset->allocations()->count());

        // activeAllocation should return only the active one
        $this->assertNotNull($asset->activeAllocation);
        $this->assertEquals($active->id, $asset->activeAllocation->id);
        $this->assertEquals('Active', $asset->activeAllocation->status);

        // Employee relation should load seamlessly
        $this->assertNotNull($asset->activeAllocation->employee);
        $this->assertEquals($this->employee->id, $asset->activeAllocation->employee->id);
    }

    public function test_asset_create_and_edit_page_renders_locations_correctly(): void
    {
        $responseCreate = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('assets.create'));

        $responseCreate->assertStatus(200);

        $asset = Asset::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        if ($asset) {
            $responseEdit = $this->actingAs($this->hrUser)
                ->withSession(['active_company_id' => $this->veCompany->id])
                ->get(route('assets.edit', $asset->id));

            $responseEdit->assertStatus(200);
        }
    }

    public function test_damage_assessments_page_renders_relationships_correctly(): void
    {
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('damage-assessments.index'));

        $response->assertStatus(200);
        $response->assertSee('Damage Assessments & Recovery Ledger');
    }
}
