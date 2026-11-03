<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class CompanySwitchingTest extends TestCase
{
    public function test_ho_admin_can_switch_active_company_and_logs_audit(): void
    {
        $hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();
        $vrishal = Company::where('code', 'VRE')->firstOrFail();

        $response = $this->actingAs($hoAdmin)->post(route('company.switch'), [
            'company_id' => $vrishal->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('active_company_id', $vrishal->id);

        // Verify audit log
        $lastLog = AuditLog::where('action', 'COMPANY_SWITCH')->latest('id')->first();
        $this->assertNotNull($lastLog);
        $this->assertEquals($vrishal->id, $lastLog->company_id);
    }

    public function test_unauthorized_user_cannot_switch_to_another_company(): void
    {
        $hrVishal = User::where('email', 'hr@vishal.com')->firstOrFail();
        $vrishal = Company::where('code', 'VRE')->firstOrFail();

        $response = $this->actingAs($hrVishal)->post(route('company.switch'), [
            'company_id' => $vrishal->id,
        ]);

        $response->assertStatus(403);
    }
}

