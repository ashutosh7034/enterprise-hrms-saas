<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use App\Models\User;
use App\Services\CompanyContextService;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hrUser = User::where('email', 'hr@vishal.com')->firstOrFail();
        $this->company = Company::where('code', 'VE')->firstOrFail();
        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_can_create_and_update_location(): void
    {
        $code = 'LOC-TEST-' . uniqid();
        $response = $this->actingAs($this->hrUser)->post(route('locations.store'), [
            'name' => 'Nashik Project Site',
            'code' => $code,
            'state' => 'Maharashtra',
            'address' => 'MIDC Ambad, Nashik',
            'is_site' => 1,
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'code' => $code,
            'company_id' => $this->company->id,
            'is_site' => 1,
        ]);

        $location = Location::where('code', $code)->firstOrFail();

        // Update
        $updateResp = $this->actingAs($this->hrUser)->put(route('locations.update', $location), [
            'name' => 'Nashik Solar Project Plant',
            'code' => $code,
            'state' => 'Maharashtra',
            'address' => 'Updated Address',
            'is_site' => 0,
            'status' => 'Active',
        ]);

        $updateResp->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Nashik Solar Project Plant',
            'is_site' => 0,
        ]);
    }

    public function test_can_create_and_update_category_with_notice_period(): void
    {
        $code = 'CAT-TEST-' . uniqid();
        $response = $this->actingAs($this->hrUser)->post(route('categories.store'), [
            'name' => 'Apprentice Trainee',
            'code' => $code,
            'notice_period_days' => 7,
            'description' => 'National Apprenticeship Scheme',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'code' => $code,
            'notice_period_days' => 7,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_can_create_contractor_agency(): void
    {
        $code = 'CON-TEST-' . uniqid();
        $response = $this->actingAs($this->hrUser)->post(route('contractors.store'), [
            'agency_name' => 'Reliable Manpower Pvt Ltd',
            'code' => $code,
            'contact_person' => 'Ganesh Shinde',
            'phone' => '+91 99220 33445',
            'email' => 'ganesh@reliable.com',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('contractors.index'));
        $this->assertDatabaseHas('contractors', [
            'code' => $code,
            'agency_name' => 'Reliable Manpower Pvt Ltd',
            'company_id' => $this->company->id,
        ]);
    }
}

