<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineRecoveryRecord;
use App\Models\LeaveApplication;
use App\Models\LeavePeriodLock;
use App\Models\OvertimeRecord;
use App\Models\PayrollPeriod;
use Carbon\Carbon;

class PayrollPreValidationService
{
    /**
     * Run full pre-validation audit for a company and payroll month.
     */
    public function validatePayrollEligibility(int $companyId, int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
        $totalCalendarDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $errors = [];
        $warnings = [];

        // 1. Check Leave Period Lock Status
        $leaveLocked = LeavePeriodLock::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('is_locked', true)
            ->exists();

        if (!$leaveLocked) {
            $warnings[] = "Leave period for {$year}-" . sprintf('%02d', $month) . " is not locked. It is recommended to lock leaves before executing final payroll.";
        }

        // 2. Query all employees for this company
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Active', 'Resigned'])
            ->with(['location', 'department', 'designation'])
            ->get();

        $eligible = [];
        $ineligible = [];

        foreach ($employees as $employee) {
            $empIssues = [];

            // Condition 1: Salary Activation
            if (!$employee->is_salary_activated) {
                $empIssues[] = "Salary is not activated (eKYC or onboarding pending).";
            }

            // Condition 2: Active Salary Assignment
            $assignment = EmployeeSalaryAssignment::where('employee_id', $employee->id)
                ->where('status', 'Approved')
                ->where('effective_from_date', '<=', $endDate)
                ->where(function ($q) use ($startDate) {
                    $q->whereNull('effective_to_date')
                      ->orWhere('effective_to_date', '>=', $startDate);
                })
                ->latest('effective_from_date')
                ->first();

            if (!$assignment) {
                $empIssues[] = "No approved salary structure assignment active during this period.";
            }

            // Condition 3: Pending Attendance Regularization
            $pendingRegCount = AttendanceRegularization::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->whereIn('status', ['Pending Manager', 'Pending HR'])
                ->count();

            if ($pendingRegCount > 0) {
                $empIssues[] = "{$pendingRegCount} pending attendance regularization request(s) unresolved.";
            }

            // Condition 4: Pending Leave Applications
            $pendingLeaves = LeaveApplication::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->where('status', 'Pending')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->count();

            if ($pendingLeaves > 0) {
                $empIssues[] = "{$pendingLeaves} pending leave application(s) awaiting approval.";
            }

            // Condition 5: Unapproved Overtime
            $pendingOtCount = OvertimeRecord::withoutGlobalScopes()
                ->where('employee_id', $employee->id)
                ->whereBetween('ot_date', [$startDate, $endDate])
                ->where('status', 'Pending Approval')
                ->count();

            if ($pendingOtCount > 0) {
                $empIssues[] = "{$pendingOtCount} pending overtime record(s) awaiting approval.";
            }

            // Inclusions / Exclusions categorization
            if (empty($empIssues)) {
                // Check if employee has active loan EMI or fine queued
                $hasLoanEmi = EmployeeLoanEmiSchedule::where('company_id', $companyId)
                    ->whereHas('loan', fn($q) => $q->where('employee_id', $employee->id))
                    ->where('due_year', $year)
                    ->where('due_month', $month)
                    ->where('status', 'Pending')
                    ->exists();

                $hasFineQueued = FineRecoveryRecord::where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->whereIn('status', ['Pending', 'Queued for Phase 5 Payroll'])
                    ->exists();

                $eligible[] = [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee->full_name,
                    'department' => $employee->department?->name ?? 'N/A',
                    'designation' => $employee->designation?->name ?? 'N/A',
                    'assignment_id' => $assignment->id,
                    'gross_monthly' => (float) $assignment->gross_monthly,
                    'has_loan_emi' => $hasLoanEmi,
                    'has_fine_queued' => $hasFineQueued,
                ];
            } else {
                $ineligible[] = [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee->full_name,
                    'department' => $employee->department?->name ?? 'N/A',
                    'reasons' => $empIssues,
                ];
            }
        }

        return [
            'company_id' => $companyId,
            'year' => $year,
            'month' => $month,
            'calendar_days' => $totalCalendarDays,
            'is_leave_period_locked' => $leaveLocked,
            'total_headcount' => $employees->count(),
            'eligible_count' => count($eligible),
            'ineligible_count' => count($ineligible),
            'eligible_employees' => $eligible,
            'ineligible_employees' => $ineligible,
            'warnings' => $warnings,
            'can_proceed' => count($eligible) > 0,
        ];
    }
}
