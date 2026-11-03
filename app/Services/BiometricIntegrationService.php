<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricDeviceSyncLog;
use App\Models\BiometricPunchLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BiometricIntegrationService
{
    /**
     * Ingest a batch of raw punch logs from a device, webhook, or CLI.
     *
     * @param int $companyId
     * @param array $punches Array of punch items:
     *      [
     *          'user_id' => '...',
     *          'punch_time' => '2026-11-02 09:05:00',
     *          'direction' => 'IN'|'OUT'|'UNKNOWN',
     *          'device_id' => 1 (optional),
     *          'raw' => [...] (optional)
     *      ]
     * @param string $source 'Webhook', 'CLI', 'Direct Poll', 'Manual CSV'
     * @param BiometricDevice|null $device
     * @return array Ingestion summary [received, processed, duplicate, failed]
     */
    public function ingestPunches(
        int $companyId,
        array $punches,
        string $source = 'Webhook',
        ?BiometricDevice $device = null
    ): array {
        $syncStart = now();
        $received = count($punches);
        $processed = 0;
        $duplicates = 0;
        $failed = 0;
        $syncTime = now();

        $syncLog = null;
        if ($device) {
            $syncLog = BiometricDeviceSyncLog::create([
                'biometric_device_id' => $device->id,
                'sync_start_time' => $syncStart,
                'records_received' => $received,
                'status' => 'Success',
            ]);
        }

        foreach ($punches as $item) {
            try {
                $userId = trim($item['user_id'] ?? $item['biometric_user_id'] ?? '');
                $rawPunchTime = $item['punch_time'] ?? null;
                $direction = strtoupper(trim($item['direction'] ?? 'UNKNOWN'));

                if (!in_array($direction, ['IN', 'OUT', 'BREAK_IN', 'BREAK_OUT', 'UNKNOWN'])) {
                    $direction = 'UNKNOWN';
                }

                if (empty($userId) || empty($rawPunchTime)) {
                    $failed++;
                    continue;
                }

                $punchDateTime = Carbon::parse($rawPunchTime)->toDateTimeString();
                $fingerprint = BiometricPunchLog::generateFingerprint($companyId, $userId, $punchDateTime, $direction);

                // Check for existing punch with same fingerprint
                $existing = BiometricPunchLog::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('punch_fingerprint', $fingerprint)
                    ->first();

                if ($existing) {
                    $duplicates++;
                    continue; // Skip exact duplicate without crashing
                }

                // Resolve Employee
                $employee = $this->resolveEmployee($companyId, $userId);

                BiometricPunchLog::create([
                    'company_id' => $companyId,
                    'location_id' => $device?->location_id ?? $employee?->location_id,
                    'biometric_device_id' => $device?->id ?? ($item['device_id'] ?? null),
                    'employee_id' => $employee?->id,
                    'biometric_user_id' => $userId,
                    'punch_time' => $punchDateTime,
                    'sync_time' => $syncTime,
                    'direction' => $direction,
                    'punch_fingerprint' => $fingerprint,
                    'raw_payload' => isset($item['raw']) ? json_encode($item['raw']) : null,
                    'source' => $source,
                    'processing_status' => $employee ? 'Processed' : 'Error',
                    'is_duplicate' => false,
                    'error_message' => $employee ? null : "Employee not resolved for biometric ID: {$userId}",
                    'processed_at' => $employee ? now() : null,
                ]);

                if ($employee) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error("Biometric ingestion exception: " . $e->getMessage(), ['item' => $item]);
            }
        }

        if ($device) {
            $device->update([
                'last_sync_at' => now(),
                'last_heartbeat_at' => now(),
                'health_status' => $failed > 0 ? 'Warning' : 'Healthy',
            ]);

            $syncLog?->update([
                'sync_end_time' => now(),
                'records_processed' => $processed,
                'records_duplicate' => $duplicates,
                'records_failed' => $failed,
                'status' => $failed === 0 ? 'Success' : ($processed > 0 ? 'Partial' : 'Failed'),
            ]);
        }

        return [
            'records_received' => $received,
            'records_processed' => $processed,
            'records_duplicate' => $duplicates,
            'records_failed' => $failed,
        ];
    }

    /**
     * Resolve Employee by Employee Code, Gate Pass Number, or Phone.
     */
    public function resolveEmployee(int $companyId, string $identifier): ?Employee
    {
        $clean = trim($identifier);

        return Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($clean) {
                $q->where('employee_code', $clean)
                  ->orWhere('gate_pass_number', $clean)
                  ->orWhere('phone', $clean);
            })
            ->first();
    }

    /**
     * Reprocess previously unmapped or failed punch logs.
     */
    public function reprocessUnmappedPunches(int $companyId): int
    {
        $unmapped = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('processing_status', 'Error')
            ->whereNull('employee_id')
            ->get();

        $reprocessedCount = 0;
        foreach ($unmapped as $log) {
            $employee = $this->resolveEmployee($companyId, $log->biometric_user_id);
            if ($employee) {
                $log->update([
                    'employee_id' => $employee->id,
                    'location_id' => $log->location_id ?? $employee->location_id,
                    'processing_status' => 'Reprocessed',
                    'error_message' => null,
                    'processed_at' => now(),
                ]);
                $reprocessedCount++;
            }
        }

        return $reprocessedCount;
    }
}

