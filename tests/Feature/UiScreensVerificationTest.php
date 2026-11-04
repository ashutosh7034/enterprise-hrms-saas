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

    public function test_pagination_renders_enterprise_design_system_across_screens(): void
    {
        $hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();

        // Test Blacklist, Audit Logs, and Damage Assessments pagination
        $paginatedRoutes = [
            route('blacklist.index'),
            route('audit-logs.index'),
            route('damage-assessments.index'),
            route('employees.index'),
            route('attendance.index'),
            route('assets.index'),
        ];

        foreach ($paginatedRoutes as $url) {
            $response = $this->actingAs($hoAdmin)->get($url);
            $response->assertStatus(200);

            // Assert no unstyled Tailwind SVGs exist
            $response->assertDontSee('w-5 h-5');
            $response->assertDontSee('sm:hidden');
        }

        // Test standalone Paginator rendering with 50 items (5 pages)
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator([1, 2, 3], 50, 10, 1);
        $html = (string) $paginator->links();

        $this->assertStringContainsString('pagination-enterprise', $html);
        $this->assertStringContainsString('Showing', $html);
        $this->assertStringContainsString('page-item active', $html);
        $this->assertStringContainsString('bi-chevron-left', $html);
        $this->assertStringContainsString('bi-chevron-right', $html);
        $this->assertStringNotContainsString('w-5 h-5', $html);
    }
}
