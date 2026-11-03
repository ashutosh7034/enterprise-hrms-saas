<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalStep;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveApplicationService
{
    public function __construct(
        protected SandwichLeaveCalculationService $sandwichService,
        protected LeaveBalanceService $balanceService,
        protected LeavePeriodLockService $lockService
    ) {}

    /**
     * Submit a new leave application.
     */
    public function applyLeave(
        Employee $employee,
        LeaveType $leaveType,
        array $data,
        User $applicant
    ): array {
        return DB::transaction(function () use ($employee, $leaveType, $data, $applicant) {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();
            $isHalfDay = !empty($data['is_half_day']);
            $halfDayType = $isHalfDay ? ($data['half_day_type'] ?? 'First Half') : null;

            // 1. Period Lock Check
            if ($this->lockService->isPeriodLocked($employee->company_id, $startDate)) {
                return ['status' => 'Error', 'message' => 'The requested leave period is locked for payroll processing. Applications cannot be submitted.'];
            }

            // 2. Tenancy & Applicability
            if (!$leaveType->isApplicableToEmployee($employee)) {
                return ['status' => 'Error', 'message' => 'The selected leave type is not applicable to this employee based on category, location, or confirmation status.'];
            }

            // 3. Notice Period Check
            if ($employee->isInNoticePeriod() && !$leaveType->is_applicable_in_notice_period) {
                // Check for HR / HO override
                $isOverride = !empty($data['is_notice_period_override']);
                $justification = $data['notice_period_justification'] ?? null;

                if (!$isOverride || empty(trim($justification)) || !$applicant->isHr()) {
                    return [
                        'status' => 'Notice Period Restricted',
                        'message' => 'Employee is serving notice period. ' . $leaveType->name . ' is restricted during notice period without an authorized HR override and mandatory justification.'
                    ];
                }
            }

            // 4. Consecutive days limit
            $calc = $this->sandwichService->calculateLeaveDays($employee, $leaveType, $startDate, $endDate, $isHalfDay);
            $totalDays = (float) $calc['total_payable_deduction'];

            if ($leaveType->max_consecutive_days > 0 && $totalDays > $leaveType->max_consecutive_days) {
                return [
                    'status' => 'Error',
                    'message' => "Maximum consecutive leave days allowed for {$leaveType->name} is {$leaveType->max_consecutive_days} days. Requested: {$totalDays} days."
                ];
            }

            // 5. Backdated application limit
            if ($leaveType->max_backdated_days > 0) {
                $earliestAllowed = now()->startOfDay()->subDays($leaveType->max_backdated_days);
                if ($startDate->lt($earliestAllowed)) {
                    return [
                        'status' => 'Error',
                        'message' => "Backdated leave cannot exceed {$leaveType->max_backdated_days} days for {$leaveType->name}."
                    ];
                }
            }

            // 6. Advance notice limit
            if ($leaveType->min_notice_days > 0 && $startDate->gt(now())) {
                $daysInAdvance = now()->startOfDay()->diffInDays($startDate, false);
                if ($daysInAdvance < $leaveType->min_notice_days) {
                    return [
                        'status' => 'Error',
                        'message' => "This leave requires at least {$leaveType->min_notice_days} days advance notice."
                    ];
                }
            }

            // 7. Balance Reservation
            $year = (int) $startDate->format('Y');
            $reserved = $this->balanceService->reserveBalance($employee, $leaveType, $totalDays, $year);
            if (!$reserved) {
                return [
                    'status' => 'Insufficient Balance',
                    'message' => "Insufficient available leave balance for {$leaveType->name}. Required: {$totalDays} days."
                ];
            }

            // 8. Generate Application Number
            $companyCode = $employee->company->code ?? 'CMP';
            $nextSeq = (LeaveApplication::withTrashed()->where('company_id', $employee->company_id)->max('id') ?? 0) + 1;
            $appNumber = 'LA-' . $companyCode . '-' . $year . '-' . sprintf('%05d', $nextSeq) . '-' . Str::upper(Str::random(4));

            // 9. Initial Approval Stage Routing
            $hasReportingManager = $employee->reporting_manager_id !== null;
            $initialStatus = $hasReportingManager ? 'Pending Manager' : 'Pending HR';
            $initialStage = $hasReportingManager ? 'Manager' : 'HR';

            $application = LeaveApplication::create([
                'company_id' => $employee->company_id,
                'application_number' => $appNumber,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'is_half_day' => $isHalfDay,
                'half_day_type' => $halfDayType,
                'requested_days' => $calc['requested_days'],
                'calculated_days' => $totalDays,
                'sandwich_breakdown_json' => $calc,
                'reason' => $data['reason'],
                'supporting_document_path' => $data['supporting_document_path'] ?? null,
                'status' => $initialStatus,
                'current_stage' => $initialStage,
                'is_notice_period_override' => !empty($data['is_notice_period_override']),
                'notice_period_justification' => $data['notice_period_justification'] ?? null,
            ]);

            LeaveApprovalStep::create([
                'leave_application_id' => $application->id,
                'stage' => 'Manager',
                'action' => 'Submitted',
                'approver_id' => $applicant->id,
                'remarks' => 'Leave application submitted with ' . $totalDays . ' payable days calculated.',
                'created_at' => now(),
            ]);

            \App\Events\LeaveApplicationSubmittedEvent::dispatch($application);

            return ['status' => 'Success', 'application' => $application];
        });
    }

    /**
     * Manager MSS review action (Approve, Reject, Send Back).
     */
    public function managerAction(
        LeaveApplication $application,
        string $decision, // 'Approve', 'Reject', 'Send Back'
        User $manager,
        ?string $remarks = null
    ): array {
        return DB::transaction(function () use ($application, $decision, $manager, $remarks) {
            if ($application->status !== 'Pending Manager') {
                return ['status' => 'Invalid State', 'message' => 'Application is not pending Manager approval.'];
            }

            // Self approval prevention
            if ($manager->isEmployee() && $application->employee->email === $manager->email) {
                return ['status' => 'Unauthorized', 'message' => 'Employees cannot approve their own leave applications.'];
            }

            // Tenancy check
            if ($application->company_id !== $manager->company_id && !$manager->isHoAdmin()) {
                return ['status' => 'Unauthorized', 'message' => 'Cross-company leave approval is strictly prohibited.'];
            }

            $year = (int) $application->start_date->format('Y');

            if ($decision === 'Approve') {
                $application->update([
                    'status' => 'Pending HR',
                    'current_stage' => 'HR',
                    'manager_id' => $manager->id,
                    'manager_action_at' => now(),
                    'manager_remarks' => $remarks,
                ]);

                LeaveApprovalStep::create([
                    'leave_application_id' => $application->id,
                    'stage' => 'Manager',
                    'action' => 'Approved',
                    'approver_id' => $manager->id,
                    'remarks' => $remarks ?? 'Manager approved and routed to HR Executive.',
                    'created_at' => now(),
                ]);
            } elseif ($decision === 'Reject') {
                // Release reserved balance back to available
                $this->balanceService->releaseReservedBalance(
                    employee: $application->employee,
                    leaveType: $application->leaveType,
                    days: (float) $application->calculated_days,
                    year: $year
                );

                $application->update([
                    'status' => 'Rejected',
                    'current_stage' => 'Completed',
                    'manager_id' => $manager->id,
                    'manager_action_at' => now(),
                    'manager_remarks' => $remarks,
                    'rejection_reason' => $remarks ?? 'Rejected by Manager',
                ]);

                LeaveApprovalStep::create([
                    'leave_application_id' => $application->id,
                    'stage' => 'Manager',
                    'action' => 'Rejected',
                    'approver_id' => $manager->id,
                    'remarks' => $remarks ?? 'Manager rejected application. Reserved balance released.',
                    'created_at' => now(),
                ]);
            } elseif ($decision === 'Send Back') {
                // Send back for correction
                LeaveApprovalStep::create([
                    'leave_application_id' => $application->id,
                    'stage' => 'Manager',
                    'action' => 'Sent Back',
                    'approver_id' => $manager->id,
                    'remarks' => $remarks ?? 'Sent back for employee clarification.',
                    'created_at' => now(),
                ]);
            }

            return ['status' => 'Success', 'application' => $application];
        });
    }

    /**
     * HR Executive final sanction action (Approve or Reject).
     */
    public function hrAction(
        LeaveApplication $application,
        string $decision, // 'Approve' or 'Reject'
        User $hr,
        ?string $remarks = null
    ): array {
        return DB::transaction(function () use ($application, $decision, $hr, $remarks) {
            if (!in_array($application->status, ['Pending HR', 'Pending Manager'])) {
                return ['status' => 'Invalid State', 'message' => 'Application is not pending HR sanction.'];
            }

            // Tenancy check
            if ($application->company_id !== $hr->company_id && !$hr->isHoAdmin()) {
                return ['status' => 'Unauthorized', 'message' => 'Cross-company approval prohibited.'];
            }

            // Period Lock Check
            if ($this->lockService->isPeriodLocked($application->company_id, $application->start_date)) {
                return ['status' => 'Period Locked', 'message' => 'Leave period is locked. Approval cannot be processed without HO override.'];
            }

            $year = (int) $application->start_date->format('Y');

            if ($decision === 'Approve') {
                $days = (float) $application->calculated_days;

                // Final deduction from balance & ledger update
                $this->balanceService->consumeApprovedLeave(
                    application: $application,
                    days: $days,
                    actor: $hr,
                    remarks: $remarks
                );

                $application->update([
                    'status' => 'Approved',
                    'current_stage' => 'Completed',
                    'approved_days' => $days,
                    'hr_id' => $hr->id,
                    'hr_action_at' => now(),
                    'hr_remarks' => $remarks,
                ]);

                LeaveApprovalStep::create([
                    'leave_application_id' => $application->id,
                    'stage' => 'HR',
                    'action' => 'Approved',
                    'approver_id' => $hr->id,
                    'remarks' => $remarks ?? 'HR sanctioned leave. Balance deducted in ledger.',
                    'created_at' => now(),
                ]);
            } elseif ($decision === 'Reject') {
                // Release reserved balance
                $this->balanceService->releaseReservedBalance(
                    employee: $application->employee,
                    leaveType: $application->leaveType,
                    days: (float) $application->calculated_days,
                    year: $year
                );

                $application->update([
                    'status' => 'Rejected',
                    'current_stage' => 'Completed',
                    'hr_id' => $hr->id,
                    'hr_action_at' => now(),
                    'hr_remarks' => $remarks,
                    'rejection_reason' => $remarks ?? 'Rejected by HR',
                ]);

                LeaveApprovalStep::create([
                    'leave_application_id' => $application->id,
                    'stage' => 'HR',
                    'action' => 'Rejected',
                    'approver_id' => $hr->id,
                    'remarks' => $remarks ?? 'HR rejected application. Reserved balance released.',
                    'created_at' => now(),
                ]);
            }

            \App\Events\LeaveApplicationDecidedEvent::dispatch($application, $decision, $remarks);

            return ['status' => 'Success', 'application' => $application];
        });
    }

    /**
     * Employee withdraws their pending leave application.
     */
    public function withdrawApplication(LeaveApplication $application, User $actor): array
    {
        return DB::transaction(function () use ($application, $actor) {
            if (!$application->isPending()) {
                return ['status' => 'Invalid State', 'message' => 'Only pending leave applications can be withdrawn.'];
            }

            $year = (int) $application->start_date->format('Y');

            // Release reserved balance
            $this->balanceService->releaseReservedBalance(
                employee: $application->employee,
                leaveType: $application->leaveType,
                days: (float) $application->calculated_days,
                year: $year
            );

            $stage = in_array($application->current_stage, ['Manager', 'HOD', 'HR'])
                ? $application->current_stage
                : 'Manager';

            $application->update([
                'status' => 'Withdrawn',
                'current_stage' => 'Completed',
                'cancellation_reason' => 'Withdrawn by applicant',
            ]);

            LeaveApprovalStep::create([
                'leave_application_id' => $application->id,
                'stage' => $stage,
                'action' => 'Withdrawn',
                'approver_id' => $actor->id,
                'remarks' => 'Application withdrawn by applicant. Reserved balance released.',
                'created_at' => now(),
            ]);

            return ['status' => 'Success', 'application' => $application];
        });
    }

    /**
     * Cancel an approved leave application (restoring deducted balance).
     */
    public function cancelApprovedLeave(LeaveApplication $application, User $actor, string $reason): array
    {
        return DB::transaction(function () use ($application, $actor, $reason) {
            if ($application->status !== 'Approved') {
                return ['status' => 'Invalid State', 'message' => 'Only approved leaves can be cancelled through cancellation workflow.'];
            }

            // Period Lock Check
            if ($this->lockService->isPeriodLocked($application->company_id, $application->start_date)) {
                return ['status' => 'Period Locked', 'message' => 'Cannot cancel leave in a locked period without HO override.'];
            }

            // Reverse consumed balance via ledger
            $this->balanceService->reverseApprovedLeave(
                application: $application,
                actor: $actor,
                remarks: $reason
            );

            $application->update([
                'status' => 'Cancelled',
                'cancellation_reason' => $reason,
            ]);

            LeaveApprovalStep::create([
                'leave_application_id' => $application->id,
                'stage' => 'HR',
                'action' => 'Cancelled',
                'approver_id' => $actor->id,
                'remarks' => $reason,
                'created_at' => now(),
            ]);

            return ['status' => 'Success', 'application' => $application];
        });
    }
}
