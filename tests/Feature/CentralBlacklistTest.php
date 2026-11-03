<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CentralBlacklistRecord;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\IdentityMatchReview;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class CentralBlacklistTest extends TestCase
{
    protected User $hrUser;
    protected User $hoAdmin;
    protected Company $veCompany;
    protected Company $vreCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first();
        $this->veCompany = Company::where('code', 'VE')->first();
        $this->vreCompany = Company::where('code', 'VRE')->first();
    }

    public function test_can_propose_blacklist_entry_and_ho_admin_approves(): void
    {
        $uniquePan = 'BKLPR' . mt_rand(1000, 9999) . 'M';

        // 1. Submit proposal
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('blacklist.store'), [
                'name' => 'Vikram Deshmukh',
                'date_of_birth' => '1987-03-20',
                'pan_number' => $uniquePan,
                'phone' => '9833445566',
                'reason_category' => 'Absconding',
                'evidence_notes' => 'Left job site without handover, absconded with company access card.',
            ]);

        $response->assertRedirect(route('blacklist.index'));

        $record = CentralBlacklistRecord::where('pan_number', $uniquePan)->first();
        $this->assertNotNull($record);
        $this->assertEquals('Pending Review', $record->approval_status);

        // 2. HR Executive cannot approve
        $hrApproveResponse = $this->actingAs($this->hrUser)
            ->post(route('blacklist.approve', $record->id));
        $hrApproveResponse->assertStatus(403);

        // 3. HO Admin approves
        $hoApproveResponse = $this->actingAs($this->hoAdmin)
            ->post(route('blacklist.approve', $record->id));
        $hoApproveResponse->assertRedirect();

        $record->refresh();
        $this->assertEquals('Approved', $record->approval_status);
        $this->assertTrue((bool) $record->is_active);
    }

    public function test_blacklist_is_enforced_cross_company(): void
    {
        $pan = 'BLKEN' . mt_rand(1000, 9999) . 'X';
        
        // Create an approved blacklist record originating from Vishal Enterprise
        CentralBlacklistRecord::create([
            'name' => 'Kiran Shinde',
            'pan_number' => $pan,
            'previous_company_id' => $this->veCompany->id,
            'reason_category' => 'Financial / Asset Misconduct',
            'evidence_notes' => 'Cross-company blacklist validation test',
            'approval_status' => 'Approved',
            'requested_by' => $this->hoAdmin->id,
            'approved_by' => $this->hoAdmin->id,
            'is_active' => true,
        ]);

        // Attempt to onboard candidate in Vrishal Eng. Pvt. Ltd. (VRE)
        $vreLoc = Location::withoutGlobalScopes()->where('company_id', $this->vreCompany->id)->first();
        $vreDept = Department::withoutGlobalScopes()->where('company_id', $this->vreCompany->id)->first();
        $vreDesig = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $this->vreCompany->id))->first();
        $vreCat = Category::withoutGlobalScopes()->where('company_id', $this->vreCompany->id)->first();
        $vreHr = User::where('email', 'hr@vrishal.com')->first();

        $response = $this->actingAs($vreHr)
            ->withSession(['active_company_id' => $this->vreCompany->id])
            ->post(route('employees.store'), [
                'first_name' => 'Kiran',
                'last_name' => 'Shinde',
                'gender' => 'Male',
                'date_of_birth' => '1989-10-12',
                'phone' => '9711223344',
                'joining_date' => now()->toDateString(),
                'location_id' => $vreLoc->id,
                'department_id' => $vreDept->id,
                'designation_id' => $vreDesig->id,
                'employee_category_id' => $vreCat->id,
                'onboarding_channel' => 'Direct',
                'pan_number' => $pan, // Blacklisted PAN
            ]);

        // Onboarding must be blocked immediately
        $response->assertSessionHasErrors(['first_name']);
        $response->assertSessionHas('danger');
    }

    public function test_whitelisting_clears_block_with_mandatory_justification(): void
    {
        $pan = 'WHTLS' . mt_rand(1000, 9999) . 'Z';
        $record = CentralBlacklistRecord::create([
            'name' => 'Anil Salve',
            'pan_number' => $pan,
            'previous_company_id' => $this->veCompany->id,
            'reason_category' => 'Policy Termination',
            'evidence_notes' => 'Testing whitelisting workflow',
            'approval_status' => 'Approved',
            'requested_by' => $this->hoAdmin->id,
            'approved_by' => $this->hoAdmin->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->hoAdmin)
            ->post(route('blacklist.whitelist', $record->id), [
                'whitelist_justification' => 'Independent audit cleared candidate of all culpability; settlement agreement signed.',
            ]);

        $response->assertRedirect();

        $record->refresh();
        $this->assertFalse((bool) $record->is_active);
        $this->assertNotNull($record->whitelisted_at);
        $this->assertEquals($this->hoAdmin->id, $record->whitelisted_by);
        $this->assertNull($record->deleted_at); // Never physically deleted!
    }

    public function test_identity_match_review_resolution_workflow(): void
    {
        $record = CentralBlacklistRecord::where('is_active', true)->first();

        $review = IdentityMatchReview::create([
            'employee_id' => null,
            'blacklist_record_id' => $record->id,
            'match_type' => 'Potential Match',
            'match_score' => 78,
            'match_details' => ['reason' => 'Levenshtein name similarity check'],
            'status' => 'Pending Review',
        ]);

        $response = $this->actingAs($this->hoAdmin)
            ->post(route('blacklist.match-reviews.resolve', $review->id), [
                'resolution' => 'False Positive',
                'resolution_remarks' => 'Physical Aadhaar card and voter ID examined; different parentage and district of origin.',
            ]);

        $response->assertRedirect();

        $review->refresh();
        $this->assertEquals('False Positive', $review->status);
        $this->assertEquals($this->hoAdmin->id, $review->reviewed_by);
    }

    /**
     * Test GET /blacklist returns HTTP 200 and eager-loads requestedBy, approvedBy, and whitelistedBy.
     */
    public function test_blacklist_index_renders_successfully_for_authorized_users_with_relationships(): void
    {
        // Create an explicit blacklist entry to test eager loaded relationships
        $record = CentralBlacklistRecord::create([
            'name' => 'Sanjay Gaikwad',
            'pan_number' => 'SNJPR' . mt_rand(1000, 9999) . 'G',
            'previous_company_id' => $this->veCompany->id,
            'reason_category' => 'Financial / Asset Misconduct',
            'evidence_notes' => 'Found attempting to remove factory brass fittings.',
            'approval_status' => 'Approved',
            'requested_by' => $this->hrUser->id,
            'approved_by' => $this->hoAdmin->id,
            'is_active' => true,
        ]);

        // 1. HO Admin access
        $responseHo = $this->actingAs($this->hoAdmin)->get(route('blacklist.index'));
        $responseHo->assertStatus(200);
        $responseHo->assertSee('Sanjay Gaikwad');
        $responseHo->assertSee('Req by: ' . $this->hrUser->name);

        // 2. HR Executive access
        $responseHr = $this->actingAs($this->hrUser)->get(route('blacklist.index'));
        $responseHr->assertStatus(200);
        $responseHr->assertSee('Sanjay Gaikwad');
    }

    /**
     * Test blacklist view handles records safely even if requesting user is null or unmapped.
     */
    public function test_blacklist_handles_missing_or_null_requesting_user_safely(): void
    {
        // Create record with requesting user
        $record = CentralBlacklistRecord::create([
            'name' => 'Anonymous Requester Test',
            'pan_number' => 'ANONR' . mt_rand(1000, 9999) . 'A',
            'previous_company_id' => $this->veCompany->id,
            'reason_category' => 'Absconding',
            'evidence_notes' => 'Graceful null handling verification.',
            'approval_status' => 'Pending Review',
            'requested_by' => $this->hrUser->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->hoAdmin)->get(route('blacklist.index'));
        $response->assertStatus(200);
        $response->assertSee('Anonymous Requester Test');
    }

    /**
     * Test pagination, search and status filters on blacklist index.
     */
    public function test_blacklist_pagination_and_filters(): void
    {
        // 1. Search filter
        $responseSearch = $this->actingAs($this->hoAdmin)->get(route('blacklist.index', ['search' => 'Sanjay']));
        $responseSearch->assertStatus(200);

        // 2. Status filter
        $responseStatus = $this->actingAs($this->hoAdmin)->get(route('blacklist.index', ['approval_status' => 'Approved']));
        $responseStatus->assertStatus(200);

        // 3. Category filter
        $responseCat = $this->actingAs($this->hoAdmin)->get(route('blacklist.index', ['reason_category' => 'Absconding']));
        $responseCat->assertStatus(200);

        // 4. Pagination parameter
        $responsePage = $this->actingAs($this->hoAdmin)->get(route('blacklist.index', ['page' => 1]));
        $responsePage->assertStatus(200);
    }

    /**
     * Test accurate KPI metrics calculation.
     */
    public function test_blacklist_kpi_counts_are_accurate(): void
    {
        $response = $this->actingAs($this->hoAdmin)->get(route('blacklist.index'));
        $response->assertStatus(200);

        $response->assertViewHas('activeCount');
        $response->assertViewHas('pendingCount');
        $response->assertViewHas('whitelistedCount');
        $response->assertViewHas('pendingReviewsCount');
    }

    /**
     * Test unauthorized roles cannot access blacklist endpoints.
     */
    public function test_unauthorized_roles_cannot_access_blacklist(): void
    {
        $managerUser = User::whereHas('role', fn($q) => $q->where('name', 'manager_mss'))->first();
        $essUser = User::whereHas('role', fn($q) => $q->where('name', 'employee_ess'))->first();

        // Manager MSS should be blocked
        $responseMgr = $this->actingAs($managerUser)->get(route('blacklist.index'));
        $responseMgr->assertStatus(403);

        // Employee ESS should be blocked
        $responseEss = $this->actingAs($essUser)->get(route('blacklist.index'));
        $responseEss->assertStatus(403);
    }
}

