<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayCalendarService
{
    /**
     * Get all applicable holidays for an employee between two dates.
     */
    public function getHolidaysForEmployeeInRange(Employee $employee, Carbon|string $startDate, Carbon|string $endDate): Collection
    {
        $startStr = is_string($startDate) ? $startDate : $startDate->toDateString();
        $endStr = is_string($endDate) ? $endDate : $endDate->toDateString();

        $holidays = Holiday::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('status', 'Active')
            ->whereBetween('date', [$startStr, $endStr])
            ->get();

        return $holidays->filter(fn(Holiday $h) => $h->isApplicableToEmployee($employee));
    }

    /**
     * Check if a specific date is an applicable holiday for an employee.
     */
    public function isHolidayForEmployee(Employee $employee, Carbon|string $date): bool
    {
        $dateStr = is_string($date) ? $date : $date->toDateString();

        $holidays = Holiday::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('status', 'Active')
            ->where('date', $dateStr)
            ->get();

        foreach ($holidays as $h) {
            if ($h->isApplicableToEmployee($employee)) {
                return true;
            }
        }

        return false;
    }
}

