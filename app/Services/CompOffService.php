<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\CompOffRecord;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompOffService
{
    public function __construct(
        protected LeaveBalanceService $balanceService
    ) {}

    /**
     * Request or log Comp-Off credit from an attendance record.
     */
    public function requestCompOff(
        Employee $employee,
        AttendanceRecord $attendance,
        ?string $remarks = null
    ): array {
        // 1. Tenancy check
        if ($attendance->company_id !== $employee->company_id || $attendance->employee_id !== $employee->id) {
            return ['status' => 'Error', 'message' => 'Attendance record does not belong to this employee.'];
        }

        // 2. Prevent duplicate comp-off for same attendance day
        $exists = CompOffRecord::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('attendance_record_id', $attendance->id)
            ->exists();

        if ($exists) {
            return ['status' => 'Duplicate', 'message' => 'Comp-off already requested or credited for this attendance record.'];
        }

        // 3. Verify qualifying work status (Must have worked on Weekly Off, Holiday, or Plant Shutdown Overtime)
        $validStatuses = ['Weekly Off', 'Holiday', 'Worked During Shutdown', 'Present'];
        $isQualifyingType = in_array($attendance->status, $validStatuses);

        // Check if day was actually a weekly off or holiday
        $workedMinutes = (int) ($attendance->actual_worked_minutes ?? $attendance->total_work_minutes);
        if ($workedMinutes < 240) {
            return ['status' => 'Ineligible', 'message' => 'Qualifying work must be at least 240 minutes (4 hours) to claim Comp-Off.'];
        }

        $creditedDays = $workedMinutes >= 480 ? 1.00 : 0.50;
        $expiryDate = Carbon::parse($attendance->attendance_date)->addDays(60);

        $sourceType = 'Weekly Off Work';
        if ($attendance->status === 'Holiday') {
            $sourceType = 'Holiday Work';
        } elseif ($attendance->status === 'Worked During Shutdown') {
            $sourceType = 'Plant Shutdown Overtime';
        }

        $record = CompOffRecord::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'attendance_record_id' => $attendance->id,
            'qualifying_work_date' => $attendance->attendance_date,
            'source_type' => $sourceType,
            'eligible_work_minutes' => $workedMinutes,
            'credited_days' => $creditedDays,
            'availed_days' => 0.00,
            'expiry_date' => $expiryDate,
            'status' => 'Pending Approval',
            'approval_remarks' => $remarks,
        ]);

        return ['status' => 'Success', 'record' => $record];
    }

    /**
     * Approve Comp-Off request and credit employee's Comp-Off leave balance.
     */
    public function approveCompOff(
        CompOffRecord $record,
        User $approver,
        ?string $remarks = null
    ): array {
        return DB::transaction(function () use ($record, $approver, $remarks) {
            if ($record->status !== 'Pending Approval') {
                return ['status' => 'Invalid State', 'message' => 'Comp-off record is not pending approval.'];
            }

            // Find or get Comp-Off leave type for this company
            $compOffLeaveType = LeaveType::withoutGlobalScopes()
                ->where('company_id', $record->company_id)
                ->where('is_compoff', true)
                ->where('status', 'Active')
                ->first();

            if (!$compOffLeaveType) {
                // Fallback to code 'COMP_OFF' or 'CO'
                $compOffLeaveType = LeaveType::withoutGlobalScopes()
                    ->where('company_id', $record->company_id)
                    ->whereIn('code', ['COMP_OFF', 'CO'])
                    ->first();
            }

            if (!$compOffLeaveType) {
                // Create default active Comp-Off leave type if not configured yet
                $compOffLeaveType = LeaveType::create([
                    'company_id' => $record->company_id,
                    'code' => 'COMP_OFF',
                    'name' => 'Compensatory Off',
                    'description' => 'Earned compensation for working on weekly off or holiday',
                    'is_paid' => true,
                    'is_compoff' => true,
                    'is_sandwich_enabled' => false,
                    'is_accrual_enabled' => false,
                    'status' => 'Active',
                    'effective_from' => now()->startOfYear()->toDateString(),
                ]);
            }

            $year = (int) $record->qualifying_work_date->format('Y');

            // Credit balance via LeaveBalanceService
            $this->balanceService->creditCompOff(
                employee: $record->employee,
                leaveType: $compOffLeaveType,
                days: (float) $record->credited_days,
                year: $year,
                compOffRecordId: $record->id,
                actor: $approver
            );

            $record->update([
                'status' => 'Approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_remarks' => $remarks ?? 'Comp-Off approved',
            ]);

            return ['status' => 'Success', 'record' => $record];
        });
    }

    /**
     * Reject Comp-Off request.
     */
    public function rejectCompOff(CompOffRecord $record, User $approver, string $reason): array
    {
        if ($record->status !== 'Pending Approval') {
            return ['status' => 'Invalid State', 'message' => 'Record is not pending approval.'];
        }

        $record->update([
            'status' => 'Rejected',
            'approved_by' => $approver->id,
            'approval_remarks' => $reason,
        ]);

        return ['status' => 'Success', 'record' => $record];
    }
}

