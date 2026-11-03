<?php

namespace App\Services\Ekyc;

use App\Models\Employee;
use App\Models\EmployeeEkycAttempt;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EkycService
{
    public const MAX_ATTEMPTS = 3;
    public const DEFAULT_GRACE_PERIOD_DAYS = 14;

    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Resolve the verification adapter based on mode.
     */
    public function getAdapter(string $mode = 'Simulated eKYC'): EkycAdapterInterface
    {
        return match ($mode) {
            'Manual Verification' => new ManualVerificationAdapter(),
            default => new SimulatedEkycAdapter(),
        };
    }

    /**
     * Execute an eKYC attempt for an employee.
     */
    public function processAttempt(
        Employee $employee,
        string $mode = 'Simulated eKYC',
        array $payload = [],
        ?User $verifier = null
    ): EmployeeEkycAttempt {
        if ($employee->ekyc_status === 'Verified' || $employee->ekyc_status === 'Exceptionally Verified') {
            throw new InvalidArgumentException("Employee {$employee->employee_code} identity is already verified.");
        }

        if ($employee->ekyc_attempt_count >= self::MAX_ATTEMPTS) {
            throw new InvalidArgumentException("Maximum verification attempts (" . self::MAX_ATTEMPTS . ") reached. Requires HO Admin exception override.");
        }

        $adapter = $this->getAdapter($mode);
        $result = $adapter->verify($employee, $payload);

        return DB::transaction(function () use ($employee, $mode, $payload, $verifier, $result) {
            $currentAttempts = $employee->ekyc_attempt_count + 1;
            $graceDays = (int) ($payload['grace_days'] ?? self::DEFAULT_GRACE_PERIOD_DAYS);
            if ($graceDays < 7 || $graceDays > 21) {
                $graceDays = self::DEFAULT_GRACE_PERIOD_DAYS;
            }

            $resubmissionDueDate = null;
            if (!$result['success']) {
                $resubmissionDueDate = now()->addDays($graceDays)->toDateString();
            }

            $attempt = EmployeeEkycAttempt::create([
                'employee_id' => $employee->id,
                'attempt_number' => $currentAttempts,
                'verification_mode' => $result['mode'],
                'result' => $result['status'],
                'failure_reason' => $result['failure_reason'],
                'resubmission_due_date' => $resubmissionDueDate,
                'remarks' => $result['remarks'],
                'verified_by' => $verifier?->id,
                'metadata' => $result['metadata'] ?? [],
            ]);

            // Update Employee state
            $employee->ekyc_attempt_count = $currentAttempts;

            if ($result['success']) {
                $employee->ekyc_status = 'Verified';
                $employee->ekyc_grace_period_ends_at = null;
            } else {
                if ($currentAttempts >= self::MAX_ATTEMPTS) {
                    $employee->ekyc_status = 'Verification Failed';
                    $employee->ekyc_grace_period_ends_at = null;
                } else {
                    $employee->ekyc_status = 'Grace Period Active';
                    $employee->ekyc_grace_period_ends_at = now()->addDays($graceDays);
                }
            }

            $employee->save();

            $this->auditLogService->log(
                action: 'eKYC Attempt Processed',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: ['attempt' => $currentAttempts - 1],
                newValues: [
                    'attempt' => $currentAttempts,
                    'result' => $result['status'],
                    'ekyc_status' => $employee->ekyc_status,
                    'resubmission_due_date' => $resubmissionDueDate,
                ],
                companyId: $employee->company_id,
                actor: $verifier
            );

            return $attempt;
        });
    }

    /**
     * HO Admin exception override to approve an employee's KYC.
     */
    public function approveException(
        Employee $employee,
        User $approver,
        string $remarks,
        array $evidence = []
    ): EmployeeEkycAttempt {
        if (!$approver->isHoAdmin()) {
            throw new InvalidArgumentException("Unauthorized: Only HO Admin can approve eKYC exceptions.");
        }

        if (empty(trim($remarks))) {
            throw new InvalidArgumentException("A valid justification remark is mandatory for eKYC exception approval.");
        }

        return DB::transaction(function () use ($employee, $approver, $remarks, $evidence) {
            $attemptCount = $employee->ekyc_attempt_count + 1;

            $attempt = EmployeeEkycAttempt::create([
                'employee_id' => $employee->id,
                'attempt_number' => $attemptCount,
                'verification_mode' => 'Manual Verification',
                'result' => 'Exception Approved',
                'failure_reason' => null,
                'resubmission_due_date' => null,
                'remarks' => $remarks,
                'verified_by' => $approver->id,
                'metadata' => [
                    'override_by' => $approver->name,
                    'override_role' => $approver->role,
                    'evidence' => $evidence,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            $oldStatus = $employee->ekyc_status;
            $employee->ekyc_status = 'Exceptionally Verified';
            $employee->ekyc_grace_period_ends_at = null;
            $employee->save();

            $this->auditLogService->log(
                action: 'eKYC Exception Override Approved',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: ['ekyc_status' => $oldStatus],
                newValues: [
                    'ekyc_status' => 'Exceptionally Verified',
                    'approved_by' => $approver->name,
                    'remarks' => $remarks,
                ],
                companyId: $employee->company_id,
                actor: $approver
            );

            return $attempt;
        });
    }
}
