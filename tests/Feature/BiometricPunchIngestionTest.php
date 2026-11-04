<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\BiometricPunchLog;
use App\Models\Company;
use App\Models\Employee;
use App\Services\BiometricIntegrationService;
use Carbon\Carbon;
use Tests\TestCase;

class BiometricPunchIngestionTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected BiometricDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->device = BiometricDevice::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
    }

    public function test_can_ingest_punches_via_rest_webhook(): void
    {
        $uniqueTime = now()->subMinutes(10)->toDateTimeString();

        $response = $this->postJson(route('api.biometric.webhook'), [
            'token' => $this->device->secret_token,
            'punches' => [
                [
                    'user_id' => $this->employee->employee_code,
                    'punch_time' => $uniqueTime,
                    'direction' => 'IN',
                ]
            ]
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.records_processed', 1);

        $punch = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('punch_time', $uniqueTime)
            ->first();

        $this->assertNotNull($punch);
        $this->assertEquals('IN', $punch->direction);
        $this->assertEquals('Processed', $punch->processing_status);
        $this->assertFalse($punch->is_duplicate);
    }

    public function test_preserves_original_punch_timestamp_distinct_from_sync_time(): void
    {
        $historicalPunchTime = '2026-07-' . mt_rand(10, 25) . ' ' . sprintf('%02d:%02d:%02d', mt_rand(8, 17), mt_rand(10, 50), mt_rand(10, 50));

        $service = app(BiometricIntegrationService::class);
        $service->ingestPunches(
            companyId: $this->company->id,
            punches: [
                [
                    'user_id' => $this->employee->employee_code,
                    'punch_time' => $historicalPunchTime,
                    'direction' => 'IN',
                ]
            ],
            source: 'Delayed Sync Test',
            device: $this->device
        );

        $punch = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('punch_time', $historicalPunchTime)
            ->first();

        $this->assertNotNull($punch);
        // Original punch timestamp preserved exactly
        $this->assertEquals($historicalPunchTime, $punch->punch_time->toDateTimeString());
        // Sync time is contemporary
        $this->assertTrue($punch->sync_time->isToday());
    }

    public function test_duplicate_punch_detection_flags_without_dropping(): void
    {
        $punchTime = '2026-08-' . mt_rand(10, 25) . ' ' . sprintf('%02d:%02d:00', mt_rand(9, 17), mt_rand(10, 50));
        $service = app(BiometricIntegrationService::class);

        // First ingestion
        $res1 = $service->ingestPunches($this->company->id, [
            [
                'user_id' => $this->employee->employee_code,
                'punch_time' => $punchTime,
                'direction' => 'IN',
            ]
        ], 'Webhook', $this->device);

        $this->assertEquals(1, $res1['records_processed']);

        // Duplicate ingestion
        $res2 = $service->ingestPunches($this->company->id, [
            [
                'user_id' => $this->employee->employee_code,
                'punch_time' => $punchTime,
                'direction' => 'IN',
            ]
        ], 'Webhook', $this->device);

        $this->assertEquals(1, $res2['records_duplicate']);
        $this->assertEquals(0, $res2['records_processed']);
    }

    public function test_unmapped_punch_logged_as_error_and_can_be_reprocessed(): void
    {
        $unknownId = 'UNKNOWN-PIN-' . mt_rand(1000, 9999);
        $punchTime = now()->subMinutes(5)->toDateTimeString();
        $service = app(BiometricIntegrationService::class);

        $res = $service->ingestPunches($this->company->id, [
            [
                'user_id' => $unknownId,
                'punch_time' => $punchTime,
                'direction' => 'IN',
            ]
        ], 'Webhook', $this->device);

        $this->assertEquals(1, $res['records_failed']);

        $errorLog = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('biometric_user_id', $unknownId)
            ->first();

        $this->assertNotNull($errorLog);
        $this->assertEquals('Error', $errorLog->processing_status);
        $this->assertNull($errorLog->employee_id);

        // Now map this identifier to employee's gate pass
        $this->employee->update(['gate_pass_number' => $unknownId]);

        // Reprocess
        $reprocessed = $service->reprocessUnmappedPunches($this->company->id);
        $this->assertGreaterThanOrEqual(1, $reprocessed);

        $errorLog->refresh();
        $this->assertEquals('Reprocessed', $errorLog->processing_status);
        $this->assertEquals($this->employee->id, $errorLog->employee_id);
    }
}
