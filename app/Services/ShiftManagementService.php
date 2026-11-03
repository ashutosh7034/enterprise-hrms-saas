<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\EmployeeShiftRoster;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShiftManagementService
{
    /**
     * Create or update a Shift definition with validation.
     */
    public function saveShift(int $companyId, array $data, ?Shift $shift = null): Shift
    {
        $code = strtoupper(trim($data['code']));
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        // Determine overnight shift
        $start = Carbon::createFromTimeString($startTime);
        $end = Carbon::createFromTimeString($endTime);
        $isOvernight = !empty($data['is_overnight']) || $end->lessThanOrEqualTo($start);

        $payload = [
            'company_id' => $companyId,
            'location_id' => !empty($data['location_id']) ? $data['location_id'] : null,
            'department_id' => !empty($data['department_id']) ? $data['department_id'] : null,
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'code' => $code,
            'name' => trim($data['name']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_duration_minutes' => $data['break_duration_minutes'] ?? 45,
            'grace_period_minutes' => $data['grace_period_minutes'] ?? 15,
            'half_day_threshold_minutes' => $data['half_day_threshold_minutes'] ?? 240,
            'full_day_threshold_minutes' => $data['full_day_threshold_minutes'] ?? 480,
            'is_overnight' => $isOvernight,
            'weekly_off_days' => $data['weekly_off_days'] ?? ['Sunday'],
            'status' => $data['status'] ?? 'Active',
            'effective_from' => $data['effective_from'] ?? now()->toDateString(),
            'effective_to' => !empty($data['effective_to']) ? $data['effective_to'] : null,
        ];

        if ($shift) {
            $shift->update($payload);
            return $shift;
        }

        return Shift::create($payload);
    }

    /**
     * Assign a recurring/default shift to an employee.
     */
    public function assignShiftToEmployee(
        int $companyId,
        int $employeeId,
        int $shiftId,
        string $fromDate,
        ?string $toDate = null,
        ?User $assigner = null
    ): EmployeeShiftAssignment {
        return DB::transaction(function () use ($companyId, $employeeId, $shiftId, $fromDate, $toDate, $assigner) {
            // Supersede existing active assignments that overlap
            EmployeeShiftAssignment::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->where('status', 'Active')
                ->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereNull('to_date')
                      ->orWhere('to_date', '>=', $fromDate);
                })
                ->update(['status' => 'Superseded']);

            $assignment = EmployeeShiftAssignment::create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'shift_id' => $shiftId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'status' => 'Active',
                'assigned_by' => $assigner?->id,
            ]);

            \App\Events\ShiftAssignedEvent::dispatch($assignment);

            return $assignment;
        });
    }

    /**
     * Assign date-wise shift roster to an employee (or bulk dates).
     */
    public function assignRoster(
        int $companyId,
        int $employeeId,
        string $startDate,
        string $endDate,
        ?int $shiftId,
        bool $isWeeklyOff = false,
        bool $isHoliday = false,
        ?string $notes = null,
        ?User $assigner = null
    ): array {
        $period = CarbonPeriod::create($startDate, $endDate);
        $records = [];

        DB::transaction(function () use ($period, $companyId, $employeeId, $shiftId, $isWeeklyOff, $isHoliday, $notes, $assigner, &$records) {
            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                $roster = EmployeeShiftRoster::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'employee_id' => $employeeId,
                        'date' => $dateStr,
                    ],
                    [
                        'shift_id' => $shiftId,
                        'is_weekly_off' => $isWeeklyOff,
                        'is_holiday' => $isHoliday,
                        'notes' => $notes,
                        'assigned_by' => $assigner?->id,
                    ]
                );

                $records[] = $roster;
            }
        });

        return $records;
    }

    /**
     * Resolve effective scheduled shift for an employee on a given date.
     * Priority:
     * 1. Date-specific EmployeeShiftRoster
     * 2. Active EmployeeShiftAssignment
     * 3. Default company shift (earliest active shift)
     */
    public function getEffectiveShiftForDate(Employee $employee, string $date): ?Shift
    {
        $roster = EmployeeShiftRoster::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($roster) {
            if ($roster->is_weekly_off || $roster->is_holiday) {
                return null; // Roster explicitly marked as Off/Holiday
            }
            if ($roster->shift_id) {
                return Shift::withoutGlobalScopes()->find($roster->shift_id);
            }
        }

        // Check active shift assignment
        $assignment = EmployeeShiftAssignment::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'Active')
            ->where('from_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('to_date')
                  ->orWhere('to_date', '>=', $date);
            })
            ->latest('id')
            ->first();

        if ($assignment) {
            $shift = Shift::withoutGlobalScopes()->find($assignment->shift_id);
            if ($shift) {
                // Check if date day-of-week is weekly off
                $dayName = Carbon::parse($date)->format('l');
                if (is_array($shift->weekly_off_days) && in_array($dayName, $shift->weekly_off_days)) {
                    return null; // Weekly off day
                }
                return $shift;
            }
        }

        // Fallback default active shift for company
        $defaultShift = Shift::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('status', 'Active')
            ->first();

        if ($defaultShift) {
            $dayName = Carbon::parse($date)->format('l');
            if (is_array($defaultShift->weekly_off_days) && in_array($dayName, $defaultShift->weekly_off_days)) {
                return null;
            }
        }

        return $defaultShift;
    }
}

