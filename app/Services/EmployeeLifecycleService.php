<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeBranchTransfer;
use App\Models\EmployeeConfirmationRecord;
use App\Models\EmployeeConversion;
use App\Models\EmployeeLifecycleEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeLifecycleService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Convert employment type (e.g. Intern to Permanent).
     */
    public function convertEmploymentType(Employee $employee, array $data, User $actor): EmployeeConversion
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $effectiveDate = $data['conversion_effective_date'] ?? now()->toDateString();
            $newType = $data['new_employment_type'] ?? 'Permanent';
            $isImmediate = ($effectiveDate <= now()->toDateString());

            $prevSnapshot = [
                'employment_type' => $employee->employee_type,
                'designation_id' => $employee->designation_id,
                'designation_title' => $employee->designation?->title,
                'department_id' => $employee->department_id,
                'department_name' => $employee->department?->name,
                'location_id' => $employee->location_id,
                'branch_name' => $employee->location?->name,
                'status' => $employee->status,
            ];

            $newSnapshot = [
                'employment_type' => $newType,
                'designation_id' => $data['to_designation_id'] ?? $employee->designation_id,
                'department_id' => $data['to_department_id'] ?? $employee->department_id,
                'location_id' => $data['to_location_id'] ?? $employee->location_id,
                'salary_structure' => $data['new_salary_structure'] ?? null,
                'confirmation_required' => !empty($data['confirmation_required']),
            ];

            $status = $isImmediate ? 'Completed' : 'Approved';

            $conversion = EmployeeConversion::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'from_employee_type' => $employee->employee_type ?? 'Intern',
                'to_employee_type' => $newType,
                'from_designation_id' => $employee->designation_id,
                'to_designation_id' => $data['to_designation_id'] ?? null,
                'from_department_id' => $employee->department_id,
                'to_department_id' => $data['to_department_id'] ?? null,
                'from_location_id' => $employee->location_id,
                'to_location_id' => $data['to_location_id'] ?? null,
                'conversion_effective_date' => $effectiveDate,
                'new_salary_structure' => $data['new_salary_structure'] ?? null,
                'confirmation_required' => !empty($data['confirmation_required']),
                'remarks' => $data['remarks'] ?? 'Employment type conversion approved by HR.',
                'status' => $status,
                'initiated_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            if ($isImmediate) {
                $employeeUpdates = [
                    'employee_type' => $newType,
                ];

                if (!empty($data['to_designation_id'])) {
                    $employeeUpdates['designation_id'] = $data['to_designation_id'];
                }
                if (!empty($data['to_department_id'])) {
                    $employeeUpdates['department_id'] = $data['to_department_id'];
                }
                if (!empty($data['to_location_id'])) {
                    $employeeUpdates['location_id'] = $data['to_location_id'];
                }

                if (!empty($data['confirmation_required'])) {
                    $employeeUpdates['confirmation_status'] = 'On Probation';
                    if (!empty($data['probation_end_date'])) {
                        $employeeUpdates['probation_end_date'] = $data['probation_end_date'];
                    }
                } else {
                    $employeeUpdates['confirmation_status'] = 'Confirmed';
                }

                $employee->update($employeeUpdates);
            }

            // Record immutable lifecycle event
            $this->recordLifecycleEvent(
                employee: $employee,
                eventType: 'Intern Converted to Permanent',
                previousValue: $prevSnapshot,
                newValue: $newSnapshot,
                effectiveDate: $effectiveDate,
                reason: 'Intern-to-Permanent Conversion',
                remarks: $data['remarks'] ?? null,
                actor: $actor
            );

            $this->auditLogService->log(
                action: 'Intern to Permanent Conversion',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: $prevSnapshot,
                newValues: $newSnapshot,
                companyId: $employee->company_id,
                actor: $actor
            );

            return $conversion;
        });
    }

    /**
     * Confirm employee from probation.
     */
    public function confirmEmployee(Employee $employee, array $data, User $actor, $confirmationFile = null): EmployeeConfirmationRecord
    {
        return DB::transaction(function () use ($employee, $data, $actor, $confirmationFile) {
            $effectiveDate = $data['confirmation_effective_date'] ?? now()->toDateString();

            $prevStatus = $employee->confirmation_status;

            $letterPath = null;
            if ($confirmationFile && $confirmationFile->isValid()) {
                $companyId = $employee->company_id;
                $letterPath = $confirmationFile->store("private/confirmation_letters/{$companyId}/{$employee->id}", 'local');
            }

            $record = EmployeeConfirmationRecord::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'action_type' => 'Confirmation',
                'previous_probation_end_date' => $employee->probation_end_date,
                'new_probation_end_date' => null,
                'confirmation_effective_date' => $effectiveDate,
                'confirmation_letter_path' => $letterPath,
                'remarks' => $data['remarks'] ?? 'Employee successfully confirmed by HR.',
                'status' => 'Confirmed',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $employee->update([
                'confirmation_status' => 'Confirmed',
                'confirmation_date' => $effectiveDate,
                'confirmation_remarks' => $data['remarks'] ?? null,
                'confirmation_letter_path' => $letterPath ?: $employee->confirmation_letter_path,
            ]);

            $this->recordLifecycleEvent(
                employee: $employee,
                eventType: 'Employee Confirmed',
                previousValue: ['confirmation_status' => $prevStatus],
                newValue: ['confirmation_status' => 'Confirmed', 'confirmation_date' => $effectiveDate],
                effectiveDate: $effectiveDate,
                reason: 'Probation Completion Confirmation',
                remarks: $data['remarks'] ?? null,
                actor: $actor
            );

            $this->auditLogService->log(
                action: 'Employee Confirmation',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: ['confirmation_status' => $prevStatus],
                newValues: ['confirmation_status' => 'Confirmed'],
                companyId: $employee->company_id,
                actor: $actor
            );

            return $record;
        });
    }

    /**
     * Extend probation period.
     */
    public function extendProbation(Employee $employee, array $data, User $actor): EmployeeConfirmationRecord
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $prevProbationEnd = $employee->probation_end_date;
            $newProbationEnd = $data['new_probation_end_date'];

            $record = EmployeeConfirmationRecord::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'action_type' => 'Probation Extension',
                'previous_probation_end_date' => $prevProbationEnd,
                'new_probation_end_date' => $newProbationEnd,
                'confirmation_effective_date' => null,
                'confirmation_letter_path' => null,
                'remarks' => $data['remarks'] ?? 'Probation extended based on performance review.',
                'status' => 'Probation Extended',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $employee->update([
                'probation_end_date' => $newProbationEnd,
                'confirmation_status' => 'Probation Extended',
                'confirmation_due_date' => $newProbationEnd,
            ]);

            $this->recordLifecycleEvent(
                employee: $employee,
                eventType: 'Probation Extended',
                previousValue: ['probation_end_date' => $prevProbationEnd?->format('Y-m-d')],
                newValue: ['probation_end_date' => $newProbationEnd, 'confirmation_status' => 'Probation Extended'],
                effectiveDate: now()->toDateString(),
                reason: $data['reason'] ?? 'Performance Evaluation',
                remarks: $data['remarks'] ?? null,
                actor: $actor
            );

            $this->auditLogService->log(
                action: 'Employee Probation Extended',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: ['probation_end_date' => $prevProbationEnd?->format('Y-m-d')],
                newValues: ['probation_end_date' => $newProbationEnd],
                companyId: $employee->company_id,
                actor: $actor
            );

            return $record;
        });
    }

    /**
     * Initiate and process Branch Transfer.
     */
    public function branchTransfer(Employee $employee, array $data, User $actor): EmployeeBranchTransfer
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $effectiveDate = $data['transfer_effective_date'] ?? now()->toDateString();
            $isImmediate = ($effectiveDate <= now()->toDateString());

            $prevLocationId = $employee->location_id;
            $prevDeptId = $employee->department_id;
            $prevDesigId = $employee->designation_id;
            $prevManagerId = $employee->reporting_manager_id;

            $transfer = EmployeeBranchTransfer::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'from_location_id' => $prevLocationId,
                'to_location_id' => $data['to_location_id'],
                'from_department_id' => $prevDeptId,
                'to_department_id' => $data['to_department_id'] ?? $prevDeptId,
                'from_designation_id' => $prevDesigId,
                'to_designation_id' => $data['to_designation_id'] ?? $prevDesigId,
                'from_reporting_manager_id' => $prevManagerId,
                'to_reporting_manager_id' => $data['to_reporting_manager_id'] ?? $prevManagerId,
                'transfer_type' => $data['transfer_type'] ?? 'Permanent',
                'transfer_effective_date' => $effectiveDate,
                'transfer_end_date' => !empty($data['transfer_end_date']) ? $data['transfer_end_date'] : null,
                'reason' => $data['reason'],
                'remarks' => $data['remarks'] ?? null,
                'status' => $isImmediate ? 'Completed' : 'Approved',
                'initiated_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            if ($isImmediate) {
                // Ensure joining_location_id is permanently preserved if not already set
                if (!$employee->joining_location_id) {
                    $employee->joining_location_id = $prevLocationId;
                }
                if (!$employee->joining_department_id) {
                    $employee->joining_department_id = $prevDeptId;
                }
                if (!$employee->joining_designation_id) {
                    $employee->joining_designation_id = $prevDesigId;
                }

                $employee->location_id = $data['to_location_id'];
                if (!empty($data['to_department_id'])) {
                    $employee->department_id = $data['to_department_id'];
                }
                if (!empty($data['to_designation_id'])) {
                    $employee->designation_id = $data['to_designation_id'];
                }
                if (!empty($data['to_reporting_manager_id'])) {
                    $employee->reporting_manager_id = $data['to_reporting_manager_id'];
                }

                $employee->save();
            }

            $this->recordLifecycleEvent(
                employee: $employee,
                eventType: 'Branch Transferred',
                previousValue: [
                    'location_id' => $prevLocationId,
                    'department_id' => $prevDeptId,
                    'designation_id' => $prevDesigId,
                    'reporting_manager_id' => $prevManagerId,
                ],
                newValue: [
                    'location_id' => $data['to_location_id'],
                    'department_id' => $data['to_department_id'] ?? $prevDeptId,
                    'designation_id' => $data['to_designation_id'] ?? $prevDesigId,
                    'reporting_manager_id' => $data['to_reporting_manager_id'] ?? $prevManagerId,
                    'transfer_type' => $data['transfer_type'] ?? 'Permanent',
                ],
                effectiveDate: $effectiveDate,
                reason: $data['reason'],
                remarks: $data['remarks'] ?? null,
                actor: $actor
            );

            $this->auditLogService->log(
                action: 'Employee Branch Transfer',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: ['location_id' => $prevLocationId],
                newValues: ['location_id' => $data['to_location_id']],
                companyId: $employee->company_id,
                actor: $actor
            );

            return $transfer;
        });
    }

    /**
     * Admin-controlled update of Date of Joining with mandatory audit trail.
     */
    public function updateDateOfJoining(Employee $employee, string $newDate, string $reason, User $actor): bool
    {
        return DB::transaction(function () use ($employee, $newDate, $reason, $actor) {
            $prevDoj = $employee->date_of_joining?->format('Y-m-d');

            $employee->update(['date_of_joining' => $newDate]);

            $this->recordLifecycleEvent(
                employee: $employee,
                eventType: 'Date of Joining Updated',
                previousValue: ['date_of_joining' => $prevDoj],
                newValue: ['date_of_joining' => $newDate],
                effectiveDate: $newDate,
                reason: $reason,
                remarks: 'Administrative update of Date of Joining.',
                actor: $actor
            );

            $this->auditLogService->log(
                action: 'Date of Joining Corrected',
                entityType: 'Employee',
                entityId: $employee->id,
                oldValues: ['date_of_joining' => $prevDoj],
                newValues: ['date_of_joining' => $newDate, 'reason' => $reason],
                companyId: $employee->company_id,
                actor: $actor
            );

            return true;
        });
    }

    /**
     * Record an immutable lifecycle timeline event.
     */
    public function recordLifecycleEvent(
        Employee $employee,
        string $eventType,
        ?array $previousValue,
        ?array $newValue,
        ?string $effectiveDate,
        ?string $reason,
        ?string $remarks,
        ?User $actor
    ): EmployeeLifecycleEvent {
        return EmployeeLifecycleEvent::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'event_type' => $eventType,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'effective_date' => $effectiveDate ?: now()->toDateString(),
            'reason' => $reason,
            'remarks' => $remarks,
            'requested_by' => $actor?->id,
            'approved_by' => $actor?->id,
            'approved_at' => now(),
        ]);
    }
}

