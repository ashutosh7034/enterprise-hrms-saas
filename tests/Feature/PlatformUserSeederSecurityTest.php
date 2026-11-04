<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PlatformUserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PlatformUserSeederSecurityTest extends TestCase
{
    protected string $adminEmail = 'superadmin@advaititsolutions.com';
    protected ?string $originalEnvPassword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnvPassword = getenv('PLATFORM_SUPERADMIN_PASSWORD') ?: null;
    }

    protected function tearDown(): void
    {
        if ($this->originalEnvPassword !== null) {
            putenv("PLATFORM_SUPERADMIN_PASSWORD={$this->originalEnvPassword}");
            $_ENV['PLATFORM_SUPERADMIN_PASSWORD'] = $this->originalEnvPassword;
        } else {
            putenv('PLATFORM_SUPERADMIN_PASSWORD');
            unset($_ENV['PLATFORM_SUPERADMIN_PASSWORD']);
        }

        parent::tearDown();
    }

    /** 1. Missing password configuration fails safely */
    public function test_seeder_throws_runtime_exception_when_password_environment_variable_is_missing(): void
    {
        PlatformUser::where('email', $this->adminEmail)->delete();

        putenv('PLATFORM_SUPERADMIN_PASSWORD');
        unset($_ENV['PLATFORM_SUPERADMIN_PASSWORD']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PLATFORM_SUPERADMIN_PASSWORD environment variable is required and cannot be empty');

        (new PlatformUserSeeder())->run();

        $this->assertDatabaseMissing('platform_users', [
            'email' => $this->adminEmail,
        ]);
    }

    /** 2. Empty password configuration fails safely */
    public function test_seeder_throws_runtime_exception_when_password_environment_variable_is_empty(): void
    {
        PlatformUser::where('email', $this->adminEmail)->delete();

        putenv('PLATFORM_SUPERADMIN_PASSWORD=   ');
        $_ENV['PLATFORM_SUPERADMIN_PASSWORD'] = '   ';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PLATFORM_SUPERADMIN_PASSWORD environment variable is required and cannot be empty');

        (new PlatformUserSeeder())->run();

        $this->assertDatabaseMissing('platform_users', [
            'email' => $this->adminEmail,
        ]);
    }

    /** 3. Seeder is strictly idempotent and does not create duplicate administrators */
    public function test_seeder_is_strictly_idempotent(): void
    {
        PlatformUser::where('email', $this->adminEmail)->delete();

        $testPass = 'ValidSecretPassword@2026';
        putenv("PLATFORM_SUPERADMIN_PASSWORD={$testPass}");
        $_ENV['PLATFORM_SUPERADMIN_PASSWORD'] = $testPass;

        // First run: Creates admin
        (new PlatformUserSeeder())->run();
        $this->assertEquals(1, PlatformUser::where('email', $this->adminEmail)->count());

        // Second run: No-op
        (new PlatformUserSeeder())->run();
        $this->assertEquals(1, PlatformUser::where('email', $this->adminEmail)->count());
    }

    /** 4. Existing administrator password is never overwritten during repeated seeding */
    public function test_existing_administrator_password_is_preserved_and_never_overwritten(): void
    {
        PlatformUser::where('email', $this->adminEmail)->delete();

        $originalSecret = 'OriginalAdminSecret@2026';
        PlatformUser::create([
            'name' => 'Existing Advait Admin',
            'email' => $this->adminEmail,
            'password' => Hash::make($originalSecret),
            'status' => 'active',
        ]);

        // Attempt seeding with a completely different environment password
        $differentSecret = 'DifferentPasswordAttempt@999';
        putenv("PLATFORM_SUPERADMIN_PASSWORD={$differentSecret}");
        $_ENV['PLATFORM_SUPERADMIN_PASSWORD'] = $differentSecret;

        (new PlatformUserSeeder())->run();

        $admin = PlatformUser::where('email', $this->adminEmail)->first();
        $this->assertNotNull($admin);

        // Original password must still be valid
        $this->assertTrue(Hash::check($originalSecret, $admin->password));
        // Different password must not match
        $this->assertFalse(Hash::check($differentSecret, $admin->password));
    }

    /** 5. Platform authentication continues working */
    public function test_platform_authentication_continues_working(): void
    {
        $testPass = 'VerifiedPlatformSecret@2026';
        PlatformUser::updateOrCreate(
            ['email' => $this->adminEmail],
            [
                'name' => 'Advait Platform Super Admin',
                'password' => Hash::make($testPass),
                'status' => 'active',
            ]
        );

        $response = $this->post(route('platform.login.post'), [
            'email' => $this->adminEmail,
            'password' => $testPass,
        ]);

        $response->assertRedirect(route('platform.dashboard'));
        $this->assertTrue(Auth::guard('platform')->check());
        $this->assertFalse(Auth::guard('web')->check());
    }

    /** 6. HRMS authentication remains completely unaffected */
    public function test_hrms_authentication_remains_unaffected(): void
    {
        $role = Role::firstOrCreate(['name' => 'ho_admin'], ['label' => 'HO Team Admin']);
        $company = Company::firstOrCreate(['code' => 'VE'], ['name' => 'Vishal Enterprise', 'status' => 'Active']);

        $hrmsUser = User::firstOrCreate(
            ['email' => 'security_audit_ho@enterprise.com'],
            [
                'name' => 'HRMS HO Admin',
                'password' => Hash::make('SecretHRMSPass@2026'),
                'company_id' => $company->id,
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );

        $response = $this->post(route('login.post'), [
            'email' => $hrmsUser->email,
            'password' => 'SecretHRMSPass@2026',
        ]);

        $response->assertRedirect(route('dashboard.admin'));
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertFalse(Auth::guard('platform')->check());
    }
}

