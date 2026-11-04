<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformUserSeeder extends Seeder
{
    /**
     * Seed the initial Advait Platform Super Administrator.
     * Idempotent: Does not overwrite or reset password if account already exists.
     */
    public function run(): void
    {
        $email = 'superadmin@advaititsolutions.com';

        if (!PlatformUser::where('email', $email)->exists()) {
            $initialPassword = env('PLATFORM_SUPERADMIN_PASSWORD');

            if (empty($initialPassword) || !is_string($initialPassword) || trim($initialPassword) === '') {
                throw new \RuntimeException(
                    'PLATFORM_SUPERADMIN_PASSWORD environment variable is required and cannot be empty to seed the initial Platform Super Administrator.'
                );
            }

            PlatformUser::create([
                'name' => 'Advait Platform Super Admin',
                'email' => $email,
                'password' => Hash::make(trim($initialPassword)),
                'status' => 'active',
                'last_login_at' => null,
                'last_login_ip' => null,
            ]);
        }
    }
}

