<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_employee_ess_cannot_access_admin_dashboard_or_masters(): void
    {
        $employee = User::where('email', 'employee@vishal.com')->firstOrFail();

        // Should be forbidden from admin dashboard
        $response = $this->actingAs($employee)->get(route('dashboard.admin'));
        $response->assertStatus(403);

        // Should be forbidden from master data routes
        $responseLoc = $this->actingAs($employee)->get(route('locations.index'));
        $responseLoc->assertStatus(403);

        $responseAudit = $this->actingAs($employee)->get(route('audit-logs.index'));
        $responseAudit->assertStatus(403);

        // Should successfully access Employee ESS dashboard
        $responseEss = $this->actingAs($employee)->get(route('dashboard.employee'));
        $responseEss->assertStatus(200);
    }

    public function test_manager_mss_can_access_manager_dashboard_but_not_masters(): void
    {
        $manager = User::where('email', 'manager@vishal.com')->firstOrFail();

        $responseMss = $this->actingAs($manager)->get(route('dashboard.manager'));
        $responseMss->assertStatus(200);

        // Forbidden from master data
        $responseMasters = $this->actingAs($manager)->get(route('locations.index'));
        $responseMasters->assertStatus(403);
    }

    public function test_hr_executive_can_access_hr_hub_and_masters_but_forbidden_from_admin_hub(): void
    {
        $hr = User::where('email', 'hr@vishal.com')->firstOrFail();

        // HR is forbidden from HO Admin Hub
        $responseAdmin = $this->actingAs($hr)->get(route('dashboard.admin'));
        $responseAdmin->assertStatus(403);

        // HR accesses HR Operations Hub
        $responseHr = $this->actingAs($hr)->get(route('dashboard.hr'));
        $responseHr->assertStatus(200);

        // HR can access locations master
        $responseMasters = $this->actingAs($hr)->get(route('locations.index'));
        $responseMasters->assertStatus(200);
    }
}

