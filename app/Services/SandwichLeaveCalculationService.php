<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\SandwichLeavePolicy;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SandwichLeaveCalculationService
{
    public function __construct(
        protected HolidayCalendarService $holidayService
    ) {}

    /**
     * Side-effect free calculation of leave days with sandwich policy breakdown.
     */
    public function calculateLeaveDays(
        Employee $employee,
        LeaveType $leaveType,
        Carbon|string $startDate,
        Carbon|string $endDate,
        bool $isHalfDay = false
    ): array {
        $start = is_string($startDate) ? Carbon::parse($startDate)->startOfDay() : $startDate->copy()->startOfDay();
        $end = is_string($endDate) ? Carbon::parse($endDate)->startOfDay() : $endDate->copy()->startOfDay();

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        // Half day check: always 0.5 days on single date
        if ($isHalfDay) {
            return [
                'requested_days' => 0.5,
                'intervening_weekly_offs' => 0,
                'intervening_holidays' => 0,
                'total_payable_deduction' => 0.5,
                'sandwich_applied' => false,
                'policy_name' => 'Half-Day Policy',
                'breakdown' => [
                    [
                        'date' => $start->toDateString(),
                        'day_name' => $start->format('l'),
                        'day_type' => 'Working Day',
                        'holiday_name' => null,
                        'is_leave_deducted' => true,
                        'deduction_units' => 0.5,
                        'reason' => 'Half-day requested leave',
                    ]
                ],
            ];
        }

        // 1. Resolve applicable sandwich policy
        $policy = $this->resolveSandwichPolicy($employee, $leaveType, $start);

        // Check whether sandwich is enabled on leave type itself and policy
        $isSandwichEnabled = $leaveType->is_sandwich_enabled && ($policy ? $policy->is_enabled : false);
        $includeWeeklyOffs = $isSandwichEnabled && ($policy ? $policy->include_intervening_weekly_offs : false);
        $includeHolidays = $isSandwichEnabled && ($policy ? $policy->include_intervening_holidays : false);

        // 2. Fetch employee weekly offs (from active shift or Sunday default)
        $weeklyOffDays = $this->getEmployeeWeeklyOffDays($employee);

        // 3. Fetch applicable holidays in range
        $applicableHolidays = $this->holidayService->getHolidaysForEmployeeInRange($employee, $start, $end)
            ->keyBy(fn($h) => $h->date->toDateString());

        $period = CarbonPeriod::create($start, $end);
        $breakdown = [];
        $requestedDays = 0.00;
        $interveningWeeklyOffs = 0;
        $interveningHolidays = 0;
        $totalPayable = 0.00;

        $periodArray = iterator_to_array($period);
        $totalDaysInPeriod = count($periodArray);

        foreach ($periodArray as $index => $currentDate) {
            $dateStr = $currentDate->toDateString();
            $dayName = $currentDate->format('l');
            $isWeeklyOff = in_array($dayName, $weeklyOffDays);
            $isHoliday = $applicableHolidays->has($dateStr);
            $holidayObj = $isHoliday ? $applicableHolidays->get($dateStr) : null;

            $isEdgeDay = ($index === 0 || $index === $totalDaysInPeriod - 1);
            $isIntervening = !$isEdgeDay;

            $dayType = 'Working Day';
            if ($isHoliday) {
                $dayType = 'Holiday';
            } elseif ($isWeeklyOff) {
                $dayType = 'Weekly Off';
            }

            $deduct = false;
            $deductionUnits = 0.00;
            $reason = '';

            if ($dayType === 'Working Day') {
                $deduct = true;
                $deductionUnits = 1.00;
                $reason = 'Working day within requested leave period';
                $requestedDays += 1.00;
            } elseif ($dayType === 'Weekly Off') {
                if ($isIntervening && $includeWeeklyOffs) {
                    $deduct = true;
                    $deductionUnits = 1.00;
                    $reason = 'Intervening weekly off included under Sandwich policy';
                    $interveningWeeklyOffs++;
                } else {
                    $deduct = false;
                    $deductionUnits = 0.00;
                    $reason = $isIntervening 
                        ? 'Intervening weekly off excluded (Sandwich policy disabled/off)' 
                        : 'Weekly off date excluded from leave deduction';
                }
            } elseif ($dayType === 'Holiday') {
                if ($isIntervening && $includeHolidays) {
                    $deduct = true;
                    $deductionUnits = 1.00;
                    $reason = 'Intervening holiday included under Sandwich policy (' . $holidayObj->name . ')';
                    $interveningHolidays++;
                } else {
                    $deduct = false;
                    $deductionUnits = 0.00;
                    $reason = $isIntervening 
                        ? 'Intervening holiday excluded (' . $holidayObj->name . ')' 
                        : 'Holiday excluded from leave deduction (' . $holidayObj->name . ')';
                }
            }

            if ($deduct) {
                $totalPayable += $deductionUnits;
            }

            $breakdown[] = [
                'date' => $dateStr,
                'day_name' => $dayName,
                'day_type' => $dayType,
                'holiday_name' => $holidayObj?->name,
                'is_leave_deducted' => $deduct,
                'deduction_units' => $deductionUnits,
                'reason' => $reason,
            ];
        }

        return [
            'requested_days' => $requestedDays,
            'intervening_weekly_offs' => $interveningWeeklyOffs,
            'intervening_holidays' => $interveningHolidays,
            'total_payable_deduction' => $totalPayable,
            'sandwich_applied' => $isSandwichEnabled && ($interveningWeeklyOffs > 0 || $interveningHolidays > 0),
            'policy_name' => $policy?->policy_name ?? ($isSandwichEnabled ? 'Default Sandwich Policy' : 'No Sandwich Policy'),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Resolve highest-priority matching SandwichLeavePolicy.
     */
    public function resolveSandwichPolicy(Employee $employee, LeaveType $leaveType, Carbon $date): ?SandwichLeavePolicy
    {
        $dateStr = $date->toDateString();

        $policies = SandwichLeavePolicy::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('effective_from', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $dateStr);
            })
            ->orderByDesc('priority')
            ->get();

        foreach ($policies as $policy) {
            if ($policy->leave_type_id && $policy->leave_type_id !== $leaveType->id) {
                continue;
            }
            if ($policy->location_id && $policy->location_id !== $employee->location_id) {
                continue;
            }
            if ($policy->department_id && $policy->department_id !== $employee->department_id) {
                continue;
            }
            if ($policy->category_id && $policy->category_id !== $employee->category_id) {
                continue;
            }

            return $policy;
        }

        return null;
    }

    /**
     * Resolve employee weekly off days from active shift.
     */
    protected function getEmployeeWeeklyOffDays(Employee $employee): array
    {
        $assignment = \App\Models\EmployeeShiftAssignment::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('status', 'Active')
            ->with('shift')
            ->latest('id')
            ->first();

        if ($assignment && $assignment->shift && is_array($assignment->shift->weekly_off_days)) {
            return $assignment->shift->weekly_off_days;
        }

        return ['Sunday'];
    }
}
