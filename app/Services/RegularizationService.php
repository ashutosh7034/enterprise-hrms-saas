<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegularizationService
{
    public function __construct(
        protected ?AttendanceCalculationService $attendanceService = null,
        protected ?AuditLogService $auditLogService = null
    ) {}

    public function setAttendanceService(AttendanceCalculationService $service): void
    {
        $this->attendanceService = $service;
    }

    /**
     * Submit an attendance regularization request.
     */
    public function applyRegularization(int $companyId, array $data, User $applicant): AttendanceRegularization
    {
        $date = Carbon::parse($data['attendance_date'])->toDateString();
        if (Carbon::parse($date)->isFuture()) {
            throw new InvalidArgumentException("Attendance regularization cannot be requested for future dates.");
        }

        $reason = trim($data['reason'] ?? '');
        if (empty($reason)) {
            throw new InvalidArgumentException("Mandatory justification/reason must be provided for attendance regularization.");
        }

        $employee = Employee::withoutGlobalScopes()->findOrFail($data['employee_id']);

        // Check if an existing pending regularization exists for the same date
        $existing = AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->whereIn('status', ['Pending Manager', 'Pending HR'])
            ->first();

        if ($existing) {
            throw new InvalidArgumentException("A pending regularization request already exists for this date.");
        }

        $attendance = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $date)
            ->first();

        $reg = AttendanceRegularization::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'attendance_record_id' => $attendance?->id,
            'attendance_date' => $date,
            'regularization_type' => $data['regularization_type'],
            'requested_in_time' => !empty($data['requested_in_time']) ? $data['requested_in_time'] : null,
            'requested_out_time' => !empty($data['requested_out_time']) ? $data['requested_out_time'] : null,
            'reason' => $reason,
            'status' => 'Pending Manager',
            'applied_by' => $applicant->id,
            'original_status' => $attendance?->status ?? 'Absent',
        ]);

        \App\Events\AttendanceRegularizationSubmittedEvent::dispatch($reg);

        return $reg;
    }

    /**
     * Manager reviews regularization.
     */
    public function managerReview(
        AttendanceRegularization $reg,
        User $manager,
        string $decision, // 'Approve', 'Reject', 'Send Back'
        ?string $remarks = null
    ): AttendanceRegularization {
        if ($decision === 'Approve') {
            $reg->update([
                'status' => 'Pending HR',
                'manager_id' => $manager->id,
                'manager_action_at' => now(),
                'manager_remarks' => $remarks,
            ]);
        } elseif ($decision === 'Reject') {
            $reg->update([
                'status' => 'Rejected',
                'manager_id' => $manager->id,
                'manager_action_at' => now(),
                'manager_remarks' => $remarks,
            ]);
        } elseif ($decision === 'Send Back') {
            $reg->update([
                'status' => 'Sent Back',
                'manager_id' => $manager->id,
                'manager_action_at' => now(),
                'manager_remarks' => $remarks,
            ]);
        }

        return $reg;
    }

    /**
     * HR or HO Admin final approval of regularization.
     */
    public function hrApproval(
        AttendanceRegularization $reg,
        User $hrUser,
        string $decision, // 'Approve', 'Reject'
        ?string $remarks = null
    ): AttendanceRegularization {
        if ($decision === 'Reject') {
            $reg->update([
                'status' => 'Rejected',
                'hr_id' => $hrUser->id,
                'hr_action_at' => now(),
                'hr_remarks' => $remarks,
            ]);
            \App\Events\AttendanceRegularizationDecidedEvent::dispatch($reg, 'Rejected', $remarks);
            return $reg;
        }

        return DB::transaction(function () use ($reg, $hrUser, $remarks) {
            $newStatus = ($reg->regularization_type === 'Business Travel / On Duty') ? 'On Duty' : 'Regularized';

            $reg->update([
                'status' => 'Approved',
                'hr_id' => $hrUser->id,
                'hr_action_at' => now(),
                'hr_remarks' => $remarks,
                'new_status' => $newStatus,
            ]);

            // Re-calculate / update daily attendance record
            $attendance = AttendanceRecord::withoutGlobalScopes()->firstOrNew([
                'company_id' => $reg->company_id,
                'employee_id' => $reg->employee_id,
                'attendance_date' => $reg->attendance_date,
            ]);

            $in = $reg->requested_in_time ?? $attendance->first_in ?? Carbon::parse($reg->attendance_date->format('Y-m-d') . ' 09:00:00');
            $out = $reg->requested_out_time ?? $attendance->last_out ?? Carbon::parse($reg->attendance_date->format('Y-m-d') . ' 18:00:00');

            $totalMins = Carbon::parse($in)->diffInMinutes(Carbon::parse($out));
            $breakMins = 45;
            $actualMins = max(0, $totalMins - $breakMins);

            $attendance->first_in = $in;
            $attendance->last_out = $out;
            $attendance->total_work_minutes = $totalMins;
            $attendance->break_minutes = $breakMins;
            $attendance->actual_worked_minutes = $actualMins;
            $attendance->status = $newStatus;
            $attendance->regularization_status = 'Approved';
            $attendance->regularization_id = $reg->id;
            $attendance->calculated_at = now();
            $attendance->save();

            // Link back attendance record ID
            $reg->update(['attendance_record_id' => $attendance->id]);

            // Audit log
            $auditService = $this->auditLogService ?? app(AuditLogService::class);
            $auditService->log(
                action: 'REGULARIZATION_APPROVED',
                model: $reg,
                companyId: $reg->company_id,
                oldValues: ['status' => $reg->original_status],
                newValues: ['status' => $newStatus, 'actual_minutes' => $actualMins, 'approved_by' => $hrUser->name]
            );

            \App\Events\AttendanceRegularizationDecidedEvent::dispatch($reg, 'Approved', $remarks);

            return $reg;
        });
    }
}

