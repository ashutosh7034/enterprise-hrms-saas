<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PlatformAuthenticationTest extends TestCase
{
    protected PlatformUser $activeAdmin;
    protected PlatformUser $inactiveAdmin;
    protected User $hrmsUser;
    protected string $adminPassword = 'SecretPlatformPassword@2026';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test_superadmin@advaititsolutions.com|127.0.0.1');

        $this->activeAdmin = PlatformUser::updateOrCreate(
            ['email' => 'test_superadmin@advaititsolutions.com'],
            [
                'name' => 'Advait Platform Super Admin',
                'password' => Hash::make($this->adminPassword),
                'status' => 'active',
            ]
        );

        $this->inactiveAdmin = PlatformUser::updateOrCreate(
            ['email' => 'test_inactive@advaititsolutions.com'],
            [
                'name' => 'Deactivated Platform Admin',
                'password' => Hash::make($this->adminPassword),
                'status' => 'inactive',
            ]
        );

        $role = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $company = Company::firstOrCreate(['code' => 'VE'], ['name' => 'Vishal Enterprise', 'status' => 'Active']);

        $this->hrmsUser = User::firstOrCreate(
            ['email' => 'test_hrms_user@enterprise.com'],
            [
                'name' => 'HRMS Admin User',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );
    }

    /** 1. Platform login page loads */
    public function test_platform_login_page_renders_successfully(): void
    {
        $response = $this->get(route('platform.login'));

        $response->assertStatus(200);
        $response->assertSee('Platform Sign In');
        $response->assertSee('KANVTECH');
    }

    /** 2. Valid credentials authenticate successfully */
    public function test_valid_platform_credentials_authenticate_successfully(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->activeAdmin->email,
            'password' => $this->adminPassword,
        ]);

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertEquals($this->activeAdmin->id, Auth::guard('platform')->id());
    }

    /** 3. Invalid credentials are rejected */
    public function test_invalid_platform_credentials_are_rejected(): void
    {
        $response = $this->from(route('platform.login'))->post(route('platform.login.post'), [
            'email' => $this->activeAdmin->email,
            'password' => 'WrongPassword#123',
        ]);

        $response->assertRedirect(route('platform.login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** 4. Inactive platform account cannot authenticate */
    public function test_inactive_platform_account_cannot_authenticate(): void
    {
        $response = $this->from(route('platform.login'))->post(route('platform.login.post'), [
            'email' => $this->inactiveAdmin->email,
            'password' => $this->adminPassword,
        ]);

        $response->assertRedirect(route('platform.login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** 5. Unauthenticated users cannot access dashboard */
    public function test_unauthenticated_user_cannot_access_platform_dashboard(): void
    {
        $response = $this->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.login'));
    }

    /** 6. Authenticated platform user can access dashboard */
    public function test_authenticated_platform_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'platform')
            ->get(route('platform.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Kanvtech HRMS SaaS Control Tower');
        $response->assertSee($this->activeAdmin->name);
        $response->assertSee($this->activeAdmin->email);
    }

    /** 7. Logout invalidates authentication */
    public function test_platform_logout_invalidates_session_and_guard(): void
    {
        $this->actingAs($this->activeAdmin, 'platform');
        $this->assertTrue(Auth::guard('platform')->check());

        $response = $this->post(route('platform.logout'));

        $response->assertRedirect(route('platform.login'));
        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** 8. Session regeneration occurs on login */
    public function test_session_is_regenerated_on_platform_login(): void
    {
        $initialSessionId = session()->getId();

        $this->post(route('platform.login.post'), [
            'email' => $this->activeAdmin->email,
            'password' => $this->adminPassword,
        ]);

        $this->assertNotEquals($initialSessionId, session()->getId());
    }

    /** 9. Rate limiting is enforced */
    public function test_rate_limiting_throttles_excessive_failed_login_attempts(): void
    {
        $ip = '127.0.0.1';
        $email = 'brute_force_target@advaititsolutions.com';

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('platform.login'))->post(route('platform.login.post'), [
                'email' => $email,
                'password' => 'WrongPassword',
            ], ['REMOTE_ADDR' => $ip]);
        }

        // 6th attempt must trigger rate limiter validation exception
        $response = $this->from(route('platform.login'))->post(route('platform.login.post'), [
            'email' => $email,
            'password' => 'WrongPassword',
        ], ['REMOTE_ADDR' => $ip]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertTrue(str_contains($errors[0], 'Too many login attempts'));
    }

    /** 10. HRMS users cannot access platform dashboard */
    public function test_hrms_user_cannot_access_platform_dashboard(): void
    {
        // Authenticate strictly under web guard
        $response = $this->actingAs($this->hrmsUser, 'web')
            ->get(route('platform.dashboard'));

        // Must be rejected by auth:platform and redirected to platform login
        $response->assertRedirect(route('platform.login'));
        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** 11. Platform users do not gain access to HRMS functionality */
    public function test_platform_user_cannot_access_hrms_protected_routes(): void
    {
        // Authenticate strictly under platform guard
        $response = $this->actingAs($this->activeAdmin, 'platform')
            ->get(route('dashboard'));

        // Default auth middleware expects web guard; redirects to tenant /login
        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('web')->check());
    }

    /** 12. Authenticated platform user is redirected away from login */
    public function test_authenticated_platform_user_is_redirected_away_from_login(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'platform')
            ->get(route('platform.login'));

        $response->assertRedirect(route('platform.dashboard'));
    }

    /** 13. Platform user deactivated mid-session is logged out */
    public function test_platform_user_deactivated_mid_session_is_logged_out(): void
    {
        $response = $this->actingAs($this->inactiveAdmin, 'platform')
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(Auth::guard('platform')->check());
    }

    /** 14. 1-Click Fast Login authenticates active platform admin */
    public function test_platform_fast_login_authenticates_active_admin(): void
    {
        $response = $this->get(route('platform.fast-login'));

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertEquals('active', Auth::guard('platform')->user()->status);
    }

    /** 15. Platform credentials submitted to tenant login form seamlessly authenticates to platform */
    public function test_platform_credentials_submitted_to_tenant_login_seamlessly_authenticates_to_platform(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => $this->activeAdmin->email,
            'password' => $this->adminPassword,
        ]);

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertFalse(Auth::guard('web')->check());
        $this->assertEquals($this->activeAdmin->id, Auth::guard('platform')->id());
    }

    /** 16. Platform navigation bar renders all six enterprise modules cleanly */
    public function test_platform_navigation_bar_renders_all_six_modules_without_broken_classes(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'platform')
            ->get(route('platform.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Customer Tenants');
        $response->assertSee('Subscriptions');
        $response->assertSee('Commercial Plans');
        $response->assertSee('Invoices & Billing', false);
        $response->assertSee('Audit Logs');
        $response->assertSee('sidebar-nav-link');
        $response->assertDontSee('btn-outline-secondary text-white');
    }

    /** 17. All platform navigation screens render successfully with HTTP 200 */
    public function test_all_platform_navigation_routes_render_cleanly(): void
    {
        $routes = [
            route('platform.dashboard'),
            route('platform.tenants.index'),
            route('platform.subscriptions.index'),
            route('platform.plans.index'),
            route('platform.invoices.index'),
            route('platform.audit-logs.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->activeAdmin, 'platform')->get($url);
            $response->assertStatus(200);
            $response->assertDontSee('btn-outline-secondary text-white');
        }
    }
}

