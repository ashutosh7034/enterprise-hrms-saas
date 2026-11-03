<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UiScreensVerificationTest extends TestCase
{
    public function test_all_master_screens_render_successfully_with_design_system(): void
    {
        $hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();

        $screens = [
            route('dashboard.admin') => 'HR & Head Office Admin Hub',
            route('companies.index') => 'Company Master',
            route('locations.index') => 'Locations & Plant Sites',
            route('departments.index') => 'Department Master',
            route('designations.index') => 'Designation Master',
            route('categories.index') => 'Employee Categories',
            route('contractors.index') => 'Contractor Master',
            route('audit-logs.index') => 'Audit Trail & Governance',
        ];

        foreach ($screens as $url => $expectedTitle) {
            $response = $this->actingAs($hoAdmin)->get($url);
            $response->assertStatus(200);
            $response->assertSee($expectedTitle, false);
            $response->assertSee('ENTERPRISE HRMS');
            $response->assertSee('Vishal Enterprise');
        }
    }

    public function test_login_and_logout_flow(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'ho_admin@enterprise.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.admin'));
        $this->assertAuthenticated();

        $logoutResp = $this->post(route('logout'));
        $logoutResp->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
