<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Seed the initial Vishal Group customer tenant and map existing legal entities.
     * Idempotent: Does not create duplicates or overwrite existing assignments.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['tenant_code' => 'TNT-VISHAL'],
            [
                'name' => 'Vishal Group',
                'slug' => 'vishal-group',
                'status' => 'active',
                'contact_name' => 'Vikram Singhania',
                'contact_email' => 'contact@vishalenterprise.com',
                'contact_phone' => '+91 22 2490 0000',
                'address' => 'Vishal Corporate Towers, Worli, Mumbai, Maharashtra 400018',
                'notes' => 'Initial group company customer organization containing Vishal Enterprise and Vrishal Eng. Pvt. Ltd.',
            ]
        );

        // Map Company ID 1 (Vishal Enterprise)
        $company1 = Company::where('id', 1)->orWhere('code', 'VE')->first();
        if ($company1) {
            if ($company1->tenant_id !== null && $company1->tenant_id !== $tenant->id) {
                throw new \RuntimeException("Conflict: Company ID 1 (VE) is already assigned to a different tenant ID {$company1->tenant_id}.");
            }
            $company1->update(['tenant_id' => $tenant->id]);
        }

        // Map Company ID 2 (Vrishal Eng. Pvt. Ltd.)
        $company2 = Company::where('id', 2)->orWhere('code', 'VRE')->first();
        if ($company2) {
            if ($company2->tenant_id !== null && $company2->tenant_id !== $tenant->id) {
                throw new \RuntimeException("Conflict: Company ID 2 (VRE) is already assigned to a different tenant ID {$company2->tenant_id}.");
            }
            $company2->update(['tenant_id' => $tenant->id]);
        }
    }
}

