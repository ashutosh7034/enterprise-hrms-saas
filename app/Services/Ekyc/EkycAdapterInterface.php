<?php

namespace App\Services\Ekyc;

use App\Models\Employee;

interface EkycAdapterInterface
{
    /**
     * Perform an eKYC verification attempt.
     *
     * @param Employee $employee
     * @param array $payload Additional verification parameters (e.g. simulation scenario, documents, operator remarks)
     * @return array Standardized result payload:
     *               [
     *                  'success' => bool,
     *                  'status' => 'Success'|'Failed'|'Exception Approved'|'Rejected',
     *                  'mode' => 'Manual Verification'|'Simulated eKYC'|'External API',
     *                  'failure_reason' => string|null,
     *                  'remarks' => string|null,
     *                  'metadata' => array,
     *               ]
     */
    public function verify(Employee $employee, array $payload = []): array;
}

