<?php

namespace App\Services\Ekyc;

use App\Models\Employee;

class SimulatedEkycAdapter implements EkycAdapterInterface
{
    public function verify(Employee $employee, array $payload = []): array
    {
        $scenario = $payload['scenario'] ?? 'auto';

        // Check if employee has required fields
        if (empty($employee->aadhaar_masked) && empty($employee->pan_number)) {
            return [
                'success' => false,
                'status' => 'Failed',
                'mode' => 'Simulated eKYC',
                'failure_reason' => 'Neither Aadhaar nor PAN provided for verification',
                'remarks' => 'Employee identity document details missing.',
                'metadata' => [
                    'provider' => 'Simulated Sandbox Mock',
                    'timestamp' => now()->toIso8601String(),
                    'error_code' => 'MISSING_IDENTITY_DOC',
                ],
            ];
        }

        // Scenario handling for deterministic testing and manual simulations
        if ($scenario === 'fail_name_mismatch') {
            return [
                'success' => false,
                'status' => 'Failed',
                'mode' => 'Simulated eKYC',
                'failure_reason' => 'Document name does not match entered employee name',
                'remarks' => 'Identity registry returned mismatch on full legal name.',
                'metadata' => [
                    'provider' => 'Simulated Sandbox Mock',
                    'scenario' => 'fail_name_mismatch',
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }

        if ($scenario === 'fail_dob_mismatch') {
            return [
                'success' => false,
                'status' => 'Failed',
                'mode' => 'Simulated eKYC',
                'failure_reason' => 'Date of Birth does not match official identity registry',
                'remarks' => 'DOB mismatch with identity record.',
                'metadata' => [
                    'provider' => 'Simulated Sandbox Mock',
                    'scenario' => 'fail_dob_mismatch',
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }

        if ($scenario === 'fail_unclear_doc' || $scenario === 'force_failure') {
            return [
                'success' => false,
                'status' => 'Failed',
                'mode' => 'Simulated eKYC',
                'failure_reason' => 'Identity verification failed during registry lookup or OCR validation',
                'remarks' => $payload['remarks'] ?? 'Document unreadable or invalid credentials.',
                'metadata' => [
                    'provider' => 'Simulated Sandbox Mock',
                    'scenario' => $scenario,
                    'timestamp' => now()->toIso8601String(),
                ],
            ];
        }

        // Default successful simulated verification
        return [
            'success' => true,
            'status' => 'Success',
            'mode' => 'Simulated eKYC',
            'failure_reason' => null,
            'remarks' => 'Identity successfully verified against simulated sandbox registry.',
            'metadata' => [
                'provider' => 'Simulated Sandbox Mock',
                'reference_id' => 'SIM-EKYC-' . strtoupper(bin2hex(random_bytes(4))),
                'timestamp' => now()->toIso8601String(),
                'verified_attributes' => ['name', 'dob', 'gender', 'identity_number'],
            ],
        ];
    }
}

