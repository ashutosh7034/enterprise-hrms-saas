<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class MultiCompanyIsolationTest extends TestCase
{
    public function test_locations_and_departments_are_isolated_by_active_company(): void
    {
        $vishal = Company::where('code', 'VE')->firstOrFail();
        $vrishal = Company::where('code', 'VRE')->firstOrFail();

        $companyContext = app(CompanyContextService::class);

        // 1. Set active company to Vishal Enterprise
        $companyContext->setActiveCompanyId($vishal->id);

        $veLocations = Location::all();
        $this->assertNotEmpty($veLocations);
        foreach ($veLocations as $loc) {
            $this->assertEquals($vishal->id, $loc->company_id);
            $this->assertNotEquals($vrishal->id, $loc->company_id);
        }

        $veDepartments = Department::all();
        $this->assertNotEmpty($veDepartments);
        foreach ($veDepartments as $dept) {
            $this->assertEquals($vishal->id, $dept->company_id);
            $this->assertNotEquals($vrishal->id, $dept->company_id);
        }

        // 2. Switch active company to Vrishal Eng. Pvt. Ltd.
        $companyContext->setActiveCompanyId($vrishal->id);

        $vreLocations = Location::all();
        $this->assertNotEmpty($vreLocations);
        foreach ($vreLocations as $loc) {
            $this->assertEquals($vrishal->id, $loc->company_id);
            $this->assertNotEquals($vishal->id, $loc->company_id);
        }

        $vreDepartments = Department::all();
        $this->assertNotEmpty($vreDepartments);
        foreach ($vreDepartments as $dept) {
            $this->assertEquals($vrishal->id, $dept->company_id);
            $this->assertNotEquals($vishal->id, $dept->company_id);
        }
    }

    public function test_locations_screen_only_renders_records_for_active_company(): void
    {
        $vishal = Company::where('code', 'VE')->firstOrFail();
        $hrVishal = User::where('email', 'hr@vishal.com')->firstOrFail();

        $response = $this->actingAs($hrVishal)->get(route('locations.index'));

        $response->assertStatus(200);
        $response->assertSee('Pune Bhosari Main Plant');
        $response->assertDontSee('Sanand Turbine Project Site'); // Sanand belongs to VRE
    }
}

