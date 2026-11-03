<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OvertimeService
{
    /**
     * Evaluate and record overtime for an attendance record.
     *
     * @param AttendanceRecord $attendance
     * @param Shift|null $shift
     * @param string $dayType 'Normal', 'Holiday', 'Weekly Off', 'Shutdown'
     * @param int $roundingIncrementMinutes Configurable rounding e.g. 15 or 30 minutes
     * @return OvertimeRecord|null
     */
    public function evaluateOvertime(
        AttendanceRecord $attendance,
        ?Shift $shift,
        string $dayType = 'Normal',
        int $roundingIncrementMinutes = 15
    ): ?OvertimeRecord {
        $actualWorked = $attendance->actual_worked_minutes;
        $rawOt = 0;

        if (in_array($dayType, ['Holiday', 'Weekly Off', 'Shutdown'])) {
            $rawOt = $actualWorked;
        } else {
            $threshold = $shift?->full_day_threshold_minutes ?? 480;
            if ($actualWorked > $threshold) {
                $rawOt = $actualWorked - $threshold;
            }
        }

        if ($rawOt <= 0) {
            // No OT earned
            return null;
        }

        // Apply configurable rounding increment (floor to closest completed block e.g., 15 mins)
        $roundedOt = $roundingIncrementMinutes > 0
            ? intdiv($rawOt, $roundingIncrementMinutes) * $roundingIncrementMinutes
            : $rawOt;

        // Check category eligibility (optional check, defaulting to eligible if ground staff or worker)
        return OvertimeRecord::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $attendance->company_id,
                'employee_id' => $attendance->employee_id,
                'attendance_record_id' => $attendance->id,
            ],
            [
                'ot_date' => $attendance->attendance_date,
                'shift_id' => $shift?->id,
                'day_type' => $dayType,
                'raw_calculated_minutes' => $rawOt,
                'rounded_ot_minutes' => $roundedOt,
                'requested_minutes' => $roundedOt,
                'status' => 'Pending',
            ]
        );
    }

    /**
     * Approve Overtime with optional adjusted minutes.
     */
    public function approve(OvertimeRecord $ot, User $approver, ?int $approvedMinutes = null, ?string $remarks = null): OvertimeRecord
    {
        $minutes = $approvedMinutes !== null ? $approvedMinutes : $ot->rounded_ot_minutes;

        $ot->update([
            'status' => 'Approved',
            'approved_minutes' => $minutes,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'remarks' => $remarks,
        ]);

        // Reflect approved OT minutes back into daily attendance record
        $ot->attendanceRecord?->update([
            'overtime_minutes' => $minutes,
        ]);

        return $ot;
    }

    /**
     * Reject Overtime with mandatory reason.
     */
    public function reject(OvertimeRecord $ot, User $rejecter, string $reason): OvertimeRecord
    {
        $ot->update([
            'status' => 'Rejected',
            'approved_minutes' => 0,
            'approved_by' => $rejecter->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $ot->attendanceRecord?->update([
            'overtime_minutes' => 0,
        ]);

        return $ot;
    }
}

