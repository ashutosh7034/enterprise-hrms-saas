<?php

namespace App\Services\Ekyc;

use App\Models\Employee;

class ManualVerificationAdapter implements EkycAdapterInterface
{
    public function verify(Employee $employee, array $payload = []): array
    {
        $approved = !empty($payload['approved']);
        $reason = $payload['failure_reason'] ?? null;
        $remarks = $payload['remarks'] ?? 'Manual physical document verification by HR Officer.';

        if ($approved) {
            return [
                'success' => true,
                'status' => 'Success',
                'mode' => 'Manual Verification',
                'failure_reason' => null,
                'remarks' => $remarks,
                'metadata' => [
                    'verification_type' => 'In-person / Physical Document Check',
                    'verified_at' => now()->toIso8601String(),
                ],
            ];
        }

        return [
            'success' => false,
            'status' => 'Failed',
            'mode' => 'Manual Verification',
            'failure_reason' => $reason ?: 'Document rejected during physical HR verification',
            'remarks' => $remarks,
            'metadata' => [
                'verification_type' => 'In-person / Physical Document Check',
                'verified_at' => now()->toIso8601String(),
            ],
        ];
    }
}

