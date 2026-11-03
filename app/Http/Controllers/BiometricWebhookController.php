<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\Company;
use App\Services\BiometricIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiometricWebhookController extends Controller
{
    public function __construct(
        protected BiometricIntegrationService $biometricService
    ) {}

    /**
     * Public REST Webhook for biometric device push / punch ingest.
     * Authenticated via device secret token in header or payload.
     */
    public function ingest(Request $request): JsonResponse
    {
        $token = $request->header('X-Device-Token') ?: $request->input('token');
        $deviceIdentifier = $request->header('X-Device-Id') ?: $request->input('device_id');

        if (!$token && !$deviceIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Missing device authentication credentials.',
            ], 401);
        }

        $device = null;
        if ($token) {
            $device = BiometricDevice::withoutGlobalScopes()
                ->where('secret_token', $token)
                ->first();
        }

        if (!$device && $deviceIdentifier) {
            $device = BiometricDevice::withoutGlobalScopes()
                ->where('device_identifier', $deviceIdentifier)
                ->first();
        }

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device credentials.',
            ], 403);
        }

        $companyId = $device->company_id;
        $rawPunches = $request->input('punches');

        if (!is_array($rawPunches)) {
            // Single punch format
            $rawPunches = [[
                'user_id' => $request->input('user_id') ?: $request->input('pin'),
                'punch_time' => $request->input('punch_time') ?: $request->input('timestamp'),
                'direction' => $request->input('direction', 'UNKNOWN'),
                'raw' => $request->all(),
            ]];
        }

        $summary = $this->biometricService->ingestPunches(
            companyId: $companyId,
            punches: $rawPunches,
            source: 'REST Webhook',
            device: $device
        );

        return response()->json([
            'success' => true,
            'message' => 'Punches ingested successfully.',
            'summary' => $summary,
        ]);
    }
}

