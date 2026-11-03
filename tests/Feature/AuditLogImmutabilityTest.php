<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use App\Services\CompanyContextService;
use RuntimeException;
use Tests\TestCase;

class AuditLogImmutabilityTest extends TestCase
{
    public function test_master_data_changes_generate_audit_log_records(): void
    {
        $hrUser = User::where('email', 'hr@vishal.com')->firstOrFail();
        $company = Company::where('code', 'VE')->firstOrFail();
        app(CompanyContextService::class)->setActiveCompanyId($company->id);

        $initialCount = AuditLog::count();

        $code = 'LOC-AUDIT-' . uniqid();
        $this->actingAs($hrUser)->post(route('locations.store'), [
            'name' => 'Audit Trial Plant',
            'code' => $code,
            'state' => 'Maharashtra',
            'is_site' => 0,
            'status' => 'Active',
        ]);

        $this->assertGreaterThan($initialCount, AuditLog::count());

        $latestLog = AuditLog::latest('id')->first();
        $this->assertNotNull($latestLog);
        $this->assertEquals('CREATE', $latestLog->action);
        $this->assertEquals(Location::class, $latestLog->auditable_type);
    }

    public function test_audit_logs_are_immutable_and_cannot_be_updated(): void
    {
        $log = AuditLog::latest('id')->firstOrFail();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit records are immutable and cannot be modified.');

        $log->action = 'TAMPERED';
        $log->save();
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $log = AuditLog::latest('id')->firstOrFail();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit records are permanent and cannot be deleted.');

        $log->delete();
    }
}

