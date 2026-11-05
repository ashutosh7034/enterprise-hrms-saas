<?php

namespace Tests\Feature;

use App\Models\PlatformAuditLog;
use App\Models\PlatformUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SuperAdminCredentialUpdateTest extends TestCase
{
    protected string $oldEmail = 'superadmin@advaititsolutions.com';
    protected string $newEmail = 'ajay@kanvtech.com';
    protected string $tempPassword = 'Knv!RhM8yr9atZlN#26';
    protected string $permanentPassword = 'KanvtechMaster@2026#Secure';
    protected PlatformUser $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear($this->oldEmail . '|127.0.0.1');
        RateLimiter::clear($this->newEmail . '|127.0.0.1');

        $admin = PlatformUser::where('email', $this->newEmail)->first()
            ?? PlatformUser::where('email', $this->oldEmail)->first();

        if ($admin) {
            $admin->update([
                'email' => $this->newEmail,
                'password' => Hash::make($this->tempPassword),
                'must_change_password' => true,
                'status' => 'active',
            ]);
            $this->adminUser = $admin;
        } else {
            $this->adminUser = PlatformUser::create([
                'name' => 'Kanvtech Platform Super Admin',
                'email' => $this->newEmail,
                'password' => Hash::make($this->tempPassword),
                'must_change_password' => true,
                'status' => 'active',
            ]);
        }
    }

    public function test_login_with_old_email_fails(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->oldEmail,
            'password' => $this->tempPassword,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('platform')->check());
    }

    public function test_login_with_new_email_and_invalid_password_fails(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->newEmail,
            'password' => 'WrongPassword@123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('platform')->check());
    }

    public function test_login_with_new_email_and_temp_password_redirects_to_mandatory_change_screen(): void
    {
        $response = $this->post(route('platform.login.post'), [
            'email' => $this->newEmail,
            'password' => $this->tempPassword,
        ]);

        $response->assertRedirect(route('platform.password.change'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertEquals($this->adminUser->id, Auth::guard('platform')->id());
        $this->assertTrue(Auth::guard('platform')->user()->must_change_password);
    }

    public function test_cannot_access_platform_dashboard_while_must_change_password_is_true(): void
    {
        $response = $this->actingAs($this->adminUser, 'platform')
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.password.change'));
        $response->assertSessionHas('warning');
    }

    public function test_cannot_access_platform_tenants_while_must_change_password_is_true(): void
    {
        $response = $this->actingAs($this->adminUser, 'platform')
            ->get(route('platform.tenants.index'));

        $response->assertRedirect(route('platform.password.change'));
    }

    public function test_mandatory_password_change_form_validation(): void
    {
        // 1. Wrong current temporary password
        $response = $this->actingAs($this->adminUser, 'platform')
            ->post(route('platform.password.update'), [
                'current_password' => 'IncorrectTempPassword',
                'password' => $this->permanentPassword,
                'password_confirmation' => $this->permanentPassword,
            ]);

        $response->assertSessionHasErrors('current_password');

        // 2. Same password as temporary
        $response = $this->actingAs($this->adminUser, 'platform')
            ->post(route('platform.password.update'), [
                'current_password' => $this->tempPassword,
                'password' => $this->tempPassword,
                'password_confirmation' => $this->tempPassword,
            ]);

        $response->assertSessionHasErrors('password');

        // 3. Password confirmation mismatch
        $response = $this->actingAs($this->adminUser, 'platform')
            ->post(route('platform.password.update'), [
                'current_password' => $this->tempPassword,
                'password' => $this->permanentPassword,
                'password_confirmation' => 'Mismatch123!',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_successful_mandatory_password_change_clears_flag_and_logs_audit(): void
    {
        $response = $this->actingAs($this->adminUser, 'platform')
            ->post(route('platform.password.update'), [
                'current_password' => $this->tempPassword,
                'password' => $this->permanentPassword,
                'password_confirmation' => $this->permanentPassword,
            ]);

        $response->assertRedirect(route('platform.dashboard'));
        $response->assertSessionHas('success');

        $this->adminUser->refresh();

        // 1. Password must be hashed and match the new permanent password
        $this->assertTrue(Hash::check($this->permanentPassword, $this->adminUser->password));

        // 2. Temporary password must no longer match
        $this->assertFalse(Hash::check($this->tempPassword, $this->adminUser->password));

        // 3. must_change_password flag is now FALSE
        $this->assertFalse($this->adminUser->must_change_password);

        // 4. Audit log entry recorded
        $this->assertDatabaseHas('platform_audit_logs', [
            'platform_user_id' => $this->adminUser->id,
            'action' => 'superadmin.password_reset_completed',
        ]);
    }

    public function test_subsequent_login_with_permanent_password_accesses_dashboard_directly(): void
    {
        // First set password and clear flag
        $this->adminUser->update([
            'password' => Hash::make($this->permanentPassword),
            'must_change_password' => false,
        ]);

        $response = $this->post(route('platform.login.post'), [
            'email' => $this->newEmail,
            'password' => $this->permanentPassword,
        ]);

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertFalse(Auth::guard('platform')->user()->must_change_password);
    }

    public function test_unrelated_tenant_users_and_credentials_remain_unaffected(): void
    {
        $hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first();
        $this->assertNotNull($hoAdmin);
        $this->assertTrue(Hash::check('password', $hoAdmin->password));

        $hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->assertNotNull($hrUser);
        $this->assertTrue(Hash::check('password', $hrUser->password));
    }
}
