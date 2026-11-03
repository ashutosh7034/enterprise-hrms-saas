<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\BiometricPunchLog;
use App\Models\Employee;
use App\Models\PlantShutdown;
use App\Models\Shift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceCalculationService
{
    public function __construct(
        protected ShiftManagementService $shiftService,
        protected OvertimeService $overtimeService
    ) {}

    /**
     * Calculate and record daily attendance for a single employee on a given date.
     * Guaranteed deterministic and idempotent.
     */
    public function calculateDailyAttendance(Employee $employee, string $date): AttendanceRecord
    {
        $companyId = $employee->company_id;
        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();

        // 1. Resolve Scheduled Shift
        $shift = $this->shiftService->getEffectiveShiftForDate($employee, $dateStr);
        $isOvernight = $shift ? $shift->is_overnight : false;

        // 2. Check for Approved Plant Shutdown
        $approvedShutdown = PlantShutdown::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('location_id', $employee->location_id)
            ->where('status', 'Approved')
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->where(function ($q) use ($employee) {
                $q->whereNull('department_id')->orWhere('department_id', $employee->department_id);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('category_id')->orWhere('category_id', $employee->category_id);
            })
            ->first();

        // 3. Check for Approved Regularization
        $approvedRegularization = AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $dateStr)
            ->where('status', 'Approved')
            ->first();

        // 4. Check for Weekly Off
        $isWeeklyOff = false;
        if ($shift && is_array($shift->weekly_off_days)) {
            $dayName = $dateObj->format('l');
            $isWeeklyOff = in_array($dayName, $shift->weekly_off_days);
        }

        // 5. Query Raw Punches for this Employee in the Shift Window
        $punches = $this->getPunchesForShiftWindow($employee, $dateStr, $shift, $isOvernight);

        $punchCount = $punches->count();
        $firstPunch = $punches->first();
        $lastPunch = $punches->last();

        $firstIn = $firstPunch ? Carbon::parse($firstPunch->punch_time) : null;
        $lastOut = $lastPunch ? Carbon::parse($lastPunch->punch_time) : null;

        $totalWorkMinutes = 0;
        $breakMinutes = 0;
        $actualWorkedMinutes = 0;
        $lateArrivalMinutes = 0;
        $earlyDepartureMinutes = 0;
        $status = 'Absent';

        $punchTimestamps = $punches->map(fn($p) => [
            'id' => $p->id,
            'time' => Carbon::parse($p->punch_time)->toDateTimeString(),
            'direction' => $p->direction,
        ])->values()->all();

        // Scenario A: Approved Regularization takes high priority
        if ($approvedRegularization) {
            $status = ($approvedRegularization->regularization_type === 'Business Travel / On Duty')
                ? 'On Duty'
                : 'Regularized';

            $firstIn = $approvedRegularization->requested_in_time ?? $firstIn ?? Carbon::parse("{$dateStr} 09:00:00");
            $lastOut = $approvedRegularization->requested_out_time ?? $lastOut ?? Carbon::parse("{$dateStr} 18:00:00");

            $totalWorkMinutes = $firstIn->diffInMinutes($lastOut);
            $breakMinutes = $shift?->break_duration_minutes ?? 45;
            $actualWorkedMinutes = max(0, $totalWorkMinutes - $breakMinutes);
        }
        // Scenario B: Approved Plant Shutdown applies
        elseif ($approvedShutdown) {
            if ($punchCount >= 2) {
                $totalWorkMinutes = $firstIn->diffInMinutes($lastOut);
                $breakMinutes = $shift?->break_duration_minutes ?? 45;
                $actualWorkedMinutes = max(0, $totalWorkMinutes - $breakMinutes);
                $status = ($actualWorkedMinutes >= 60) ? 'Worked During Shutdown' : 'Site Shutdown';
            } else {
                $status = 'Site Shutdown';
            }
        }
        // Scenario C: No Punches
        elseif ($punchCount === 0) {
            if ($isWeeklyOff) {
                $status = 'Weekly Off';
            } else {
                $status = 'Absent';
            }
        }
        // Scenario D: Single Punch (Missing IN or Missing OUT)
        elseif ($punchCount === 1) {
            $status = 'Missing Punch';
            $totalWorkMinutes = 0;
            $actualWorkedMinutes = 0;
        }
        // Scenario E: Normal Multiple Punches
        else {
            $totalWorkMinutes = abs((int) $firstIn->diffInMinutes($lastOut));
            $breakMinutes = $shift?->break_duration_minutes ?? 45;
            $actualWorkedMinutes = max(0, $totalWorkMinutes - $breakMinutes);

            // Compute late arrival and early departure if shift is defined
            if ($shift) {
                $shiftStart = Carbon::parse("{$dateStr} {$shift->start_time}");
                $graceDeadline = (clone $shiftStart)->addMinutes($shift->grace_period_minutes);

                if ($firstIn->greaterThan($graceDeadline)) {
                    $lateArrivalMinutes = abs((int) $firstIn->diffInMinutes($shiftStart));
                }

                $shiftEnd = Carbon::parse("{$dateStr} {$shift->end_time}");
                if ($isOvernight || $shiftEnd->lessThanOrEqualTo($shiftStart)) {
                    $shiftEnd->addDay();
                }

                if ($lastOut->lessThan($shiftEnd)) {
                    $earlyDepartureMinutes = abs((int) $shiftEnd->diffInMinutes($lastOut));
                }

                $fullDayThreshold = $shift->full_day_threshold_minutes;
                $halfDayThreshold = $shift->half_day_threshold_minutes;

                if ($actualWorkedMinutes >= $fullDayThreshold) {
                    $status = 'Present';
                } elseif ($actualWorkedMinutes >= $halfDayThreshold) {
                    $status = 'Half Day';
                } else {
                    $status = 'Absent';
                }
            } else {
                // Default threshold: 8 hours full day, 4 hours half day
                if ($actualWorkedMinutes >= 480) {
                    $status = 'Present';
                } elseif ($actualWorkedMinutes >= 240) {
                    $status = 'Half Day';
                } else {
                    $status = 'Absent';
                }
            }

            if ($isWeeklyOff && in_array($status, ['Present', 'Half Day'])) {
                // Working on weekly off: qualify as present on weekly off
                $status = 'Present';
            }
        }

        // Idempotent upsert
        $attendance = AttendanceRecord::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'attendance_date' => $dateStr,
            ],
            [
                'shift_id' => $shift?->id,
                'first_in' => $firstIn,
                'last_out' => $lastOut,
                'total_punches' => $punchCount,
                'punch_records_json' => $punchTimestamps,
                'total_work_minutes' => $totalWorkMinutes,
                'break_minutes' => $breakMinutes,
                'actual_worked_minutes' => $actualWorkedMinutes,
                'late_arrival_minutes' => $lateArrivalMinutes,
                'early_departure_minutes' => $earlyDepartureMinutes,
                'status' => $status,
                'regularization_status' => $approvedRegularization ? 'Approved' : 'None',
                'regularization_id' => $approvedRegularization?->id,
                'is_overnight' => $isOvernight,
                'calculated_at' => now(),
            ]
        );

        // 6. Overtime Evaluation Interface
        $dayType = 'Normal';
        if ($status === 'Worked During Shutdown') {
            $dayType = 'Shutdown';
        } elseif ($isWeeklyOff) {
            $dayType = 'Weekly Off';
        }

        $this->overtimeService->evaluateOvertime($attendance, $shift, $dayType);

        return $attendance;
    }

    /**
     * Batch calculate attendance for a company on a given date.
     */
    public function calculateCompanyAttendance(int $companyId, string $date): int
    {
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();

        $processed = 0;
        foreach ($employees as $employee) {
            $this->calculateDailyAttendance($employee, $date);
            $processed++;
        }

        return $processed;
    }

    /**
     * Fetch relevant punches for an employee within their shift window.
     */
    protected function getPunchesForShiftWindow(
        Employee $employee,
        string $dateStr,
        ?Shift $shift,
        bool $isOvernight
    ): Collection {
        if ($shift && $isOvernight) {
            // Window spans from Date 16:00 to Date+1 14:00
            $windowStart = Carbon::parse("{$dateStr} 16:00:00");
            $windowEnd = Carbon::parse("{$dateStr} 14:00:00")->addDay();
        } else {
            // Window spans full calendar day plus 4 hours grace buffer
            $windowStart = Carbon::parse("{$dateStr} 04:00:00");
            $windowEnd = Carbon::parse("{$dateStr} 23:59:59");
        }

        return BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereBetween('punch_time', [$windowStart->toDateTimeString(), $windowEnd->toDateTimeString()])
            ->orderBy('punch_time', 'asc')
            ->get();
    }
}
