<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineRecoveryRecord;
use App\Models\LeaveApplication;
use App\Models\OvertimeRecord;
use App\Models\PayrollConfiguration;
use App\Models\PayrollStatutoryConfiguration;
use Carbon\Carbon;

class PayrollCalculationService
{
    public function __construct(
        protected PayrollConfigurationService $configService,
        protected StatutoryCalculationService $statutoryService
    ) {}

    /**
     * Compute comprehensive itemized payroll for an employee.
     */
    public function calculateEmployeePayroll(
        Employee $employee,
        int $year,
        int $month,
        ?EmployeeSalaryAssignment $assignment = null
    ): array {
        $companyId = $employee->company_id;
        $payrollConfig = $this->configService->getActivePayrollConfig($companyId);
        $statutoryConfig = $this->configService->getActiveStatutoryConfig($companyId);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
        $calendarDays = (float) Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // 1. Resolve Active Salary Assignment
        if (!$assignment) {
            $assignment = EmployeeSalaryAssignment::where('employee_id', $employee->id)
                ->where('status', 'Approved')
                ->where('effective_from_date', '<=', $endDate)
                ->latest('effective_from_date')
                ->first();
        }

        if (!$assignment) {
            throw new \RuntimeException("Employee {$employee->employee_code} does not have an approved salary assignment.");
        }

        // 2. Attendance & Days Evaluation
        $daysBreakdown = $this->evaluateAttendanceAndLeaves($employee, $startDate, $endDate, $calendarDays);
        $workedDays = $daysBreakdown['worked_days'];
        $paidLeaveDays = $daysBreakdown['paid_leave_days'];
        $weeklyOffDays = $daysBreakdown['weekly_off_days'];
        $holidayDays = $daysBreakdown['holiday_days'];
        $lossOfPayDays = $daysBreakdown['loss_of_pay_days'];
        $paidDays = max(0.00, $calendarDays - $lossOfPayDays);

        // 3. Pro-Rata Earnings Evaluation
        $proRataFactor = $calendarDays > 0 ? ($paidDays / $calendarDays) : 1.0;
        
        $componentValues = $assignment->component_values_json ?? [];
        $stdBasic = (float) ($componentValues['BASIC'] ?? ($assignment->gross_monthly * 0.50));
        $stdHra = (float) ($componentValues['HRA'] ?? ($assignment->gross_monthly * 0.25));
        $stdDa = (float) ($componentValues['DA'] ?? 0.00);
        $stdAllowances = max(0.00, (float) $assignment->gross_monthly - ($stdBasic + $stdHra + $stdDa));

        $basic = round($stdBasic * $proRataFactor, 2);
        $hra = round($stdHra * $proRataFactor, 2);
        $da = round($stdDa * $proRataFactor, 2);
        $allowances = round($stdAllowances * $proRataFactor, 2);

        // 4. Overtime Calculation
        $otData = $this->calculateOvertime($employee, $assignment, $startDate, $endDate, $basic, $da, $payrollConfig);
        $overtimeHours = $otData['overtime_hours'];
        $overtimeAmount = $otData['overtime_amount'];

        $grossEarnings = round($basic + $hra + $da + $allowances + $overtimeAmount, 2);

        // 5. Statutory Deductions
        $pfData = $this->statutoryService->calculatePf($assignment, $basic, $da, $statutoryConfig);
        $pfEmployee = $pfData['employee_pf'];
        $pfEmployer = $pfData['employer_pf'];

        $esiData = $this->statutoryService->calculateEsi($assignment, $grossEarnings, $overtimeAmount, $statutoryConfig);
        $esiEmployee = $esiData['employee_esi'];
        $esiEmployer = $esiData['employer_esi'];

        $professionalTax = $this->statutoryService->calculateProfessionalTax($employee, $grossEarnings, $month);
        
        $lwfData = $this->statutoryService->calculateLwf($month, $statutoryConfig);
        $lwfEmployee = $lwfData['employee_lwf'];
        $lwfEmployer = $lwfData['employer_lwf'];

        $tds = $this->statutoryService->calculateMonthlyTds($employee, $grossEarnings, $month, $year);

        $totalStatutoryDeductions = round($pfEmployee + $esiEmployee + $professionalTax + $lwfEmployee + $tds, 2);
        $totalEmployerContributions = round($pfEmployer + $esiEmployer + $lwfEmployer, 2);

        // 6. Non-Statutory Deductions (Loan EMI & Fine Recovery)
        $loanEmi = $this->resolveDueLoanEmi($employee, $year, $month);
        $fineRecovery = $this->resolveDueFineRecovery($employee);

        $otherDeductions = round($loanEmi + $fineRecovery, 2);
        $totalDeductions = round($totalStatutoryDeductions + $otherDeductions, 2);

        // 7. Net Salary Payable
        $rawNetSalary = max(0.00, $grossEarnings - $totalDeductions);
        $netSalary = $this->applyRounding($rawNetSalary, $payrollConfig->net_pay_rounding_rule);

        // 8. Compile Itemized Transactions
        $transactions = [
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'type' => 'Earning', 'standard' => $stdBasic, 'amount' => $basic],
            ['code' => 'HRA', 'name' => 'House Rent Allowance', 'type' => 'Earning', 'standard' => $stdHra, 'amount' => $hra],
            ['code' => 'DA', 'name' => 'Dearness Allowance', 'type' => 'Earning', 'standard' => $stdDa, 'amount' => $da],
            ['code' => 'SPECIAL', 'name' => 'Allowances / Other', 'type' => 'Earning', 'standard' => $stdAllowances, 'amount' => $allowances],
        ];

        if ($overtimeAmount > 0) {
            $transactions[] = ['code' => 'OT', 'name' => 'Overtime Pay', 'type' => 'Earning', 'standard' => 0.00, 'amount' => $overtimeAmount];
        }

        if ($pfEmployee > 0) {
            $transactions[] = ['code' => 'PF_EMP', 'name' => 'Provident Fund (Employee)', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $pfEmployee];
        }
        if ($esiEmployee > 0) {
            $transactions[] = ['code' => 'ESI_EMP', 'name' => 'ESI (Employee)', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $esiEmployee];
        }
        if ($professionalTax > 0) {
            $transactions[] = ['code' => 'PT', 'name' => 'Professional Tax', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $professionalTax];
        }
        if ($lwfEmployee > 0) {
            $transactions[] = ['code' => 'LWF', 'name' => 'Labour Welfare Fund', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $lwfEmployee];
        }
        if ($tds > 0) {
            $transactions[] = ['code' => 'TDS', 'name' => 'Income Tax (TDS)', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $tds];
        }
        if ($loanEmi > 0) {
            $transactions[] = ['code' => 'LOAN_EMI', 'name' => 'Loan EMI Recovery', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $loanEmi];
        }
        if ($fineRecovery > 0) {
            $transactions[] = ['code' => 'FINE_REC', 'name' => 'Asset Damage Fine Recovery', 'type' => 'Deduction', 'standard' => 0.00, 'amount' => $fineRecovery];
        }

        return [
            'employee_id' => $employee->id,
            'assignment_id' => $assignment->id,
            'year' => $year,
            'month' => $month,
            'calendar_days' => $calendarDays,
            'worked_days' => $workedDays,
            'paid_leave_days' => $paidLeaveDays,
            'weekly_off_days' => $weeklyOffDays,
            'holiday_days' => $holidayDays,
            'loss_of_pay_days' => $lossOfPayDays,
            'paid_days' => $paidDays,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'basic_salary' => $basic,
            'hra' => $hra,
            'da' => $da,
            'allowances_amount' => $allowances,
            'gross_earnings' => $grossEarnings,
            'pf_employee' => $pfEmployee,
            'esi_employee' => $esiEmployee,
            'professional_tax' => $professionalTax,
            'labour_welfare_fund' => $lwfEmployee,
            'income_tax_tds' => $tds,
            'pf_employer' => $pfEmployer,
            'esi_employer' => $esiEmployer,
            'loan_emi_deduction' => $loanEmi,
            'fine_recovery_deduction' => $fineRecovery,
            'other_deductions' => $otherDeductions,
            'total_statutory_deductions' => $totalStatutoryDeductions,
            'total_employer_contributions' => $totalEmployerContributions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'transactions' => $transactions,
            'snapshot' => [
                'pro_rata_factor' => $proRataFactor,
                'std_gross' => $assignment->gross_monthly,
                'std_basic' => $stdBasic,
                'epf_wages' => $pfData['epf_wages'] ?? 0.00,
                'is_target_ot' => $assignment->is_target_ot,
            ],
        ];
    }

    /**
     * Evaluate attendance muster and leave applications to compute worked, paid, and LOP days.
     */
    protected function evaluateAttendanceAndLeaves(Employee $employee, string $startDate, string $endDate, float $calendarDays): array
    {
        $records = AttendanceRecord::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $workedDays = 0.00;
        $weeklyOffDays = 0.00;
        $holidayDays = 0.00;
        $lopDays = 0.00;

        foreach ($records as $rec) {
            if (in_array($rec->status, ['Present', 'Worked During Shutdown', 'On Duty'])) {
                $workedDays += 1.0;
            } elseif ($rec->status === 'Half Day') {
                $workedDays += 0.5;
                $lopDays += 0.5;
            } elseif ($rec->status === 'Weekly Off') {
                $weeklyOffDays += 1.0;
            } elseif ($rec->status === 'Site Shutdown') {
                $workedDays += 1.0; // Paid shutdown wage protection
            } elseif ($rec->status === 'Absent') {
                $lopDays += 1.0;
            }
        }

        // Approved Leaves
        $approvedLeaves = LeaveApplication::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->with('leaveType')
            ->get();

        $paidLeaveDays = 0.00;
        foreach ($approvedLeaves as $leave) {
            $deducted = (float) ($leave->approved_days ?? $leave->calculated_days ?? 0);
            if ($leave->leaveType && in_array($leave->leaveType->code, ['LOP', 'UNPAID'])) {
                $lopDays += $deducted;
            } else {
                $paidLeaveDays += $deducted;
            }
        }

        // If attendance was not fully logged for every single day in the period,
        // treat remaining non-LOP days as standard scheduled working days for continuous salaried employees
        if ($records->isEmpty()) {
            $workedDays = $calendarDays - $paidLeaveDays - $lopDays;
        }

        return [
            'worked_days' => round($workedDays, 2),
            'paid_leave_days' => round($paidLeaveDays, 2),
            'weekly_off_days' => round($weeklyOffDays, 2),
            'holiday_days' => round($holidayDays, 2),
            'loss_of_pay_days' => round($lopDays, 2),
        ];
    }

    /**
     * Compute overtime hours and amount based on Target OT flag or approved biometric OT records.
     */
    protected function calculateOvertime(
        Employee $employee,
        EmployeeSalaryAssignment $assignment,
        string $startDate,
        string $endDate,
        float $basic,
        float $da,
        PayrollConfiguration $config
    ): array {
        if ($assignment->is_target_ot) {
            return [
                'overtime_hours' => 0.00,
                'overtime_amount' => (float) $assignment->target_ot_amount,
            ];
        }

        $approvedMinutes = OvertimeRecord::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereBetween('ot_date', [$startDate, $endDate])
            ->where('status', 'Approved')
            ->sum('approved_minutes');

        if ($approvedMinutes <= 0) {
            return ['overtime_hours' => 0.00, 'overtime_amount' => 0.00];
        }

        $overtimeHours = round($approvedMinutes / 60.0, 2);
        $divisor = $config->ot_hourly_divisor > 0 ? $config->ot_hourly_divisor : 208;
        $multiplier = (float) $config->ot_multiplier;

        $hourlyRate = ($basic + $da) / $divisor;
        $overtimeAmount = round($overtimeHours * $hourlyRate * $multiplier, 2);

        return [
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
        ];
    }

    /**
     * Resolve due loan EMI for this month.
     */
    protected function resolveDueLoanEmi(Employee $employee, int $year, int $month): float
    {
        $schedule = EmployeeLoanEmiSchedule::where('company_id', $employee->company_id)
            ->whereHas('loan', fn($q) => $q->where('employee_id', $employee->id)->whereIn('status', ['Active', 'Disbursed']))
            ->where('due_year', $year)
            ->where('due_month', $month)
            ->where('status', 'Pending')
            ->first();

        return $schedule ? (float) $schedule->emi_amount : 0.00;
    }

    /**
     * Resolve due asset damage fine recovery.
     */
    protected function resolveDueFineRecovery(Employee $employee): float
    {
        $record = FineRecoveryRecord::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('status', 'Queued for Phase 5 Payroll')
            ->first();

        return $record ? (float) $record->amount : 0.00;
    }

    /**
     * Apply configured net pay rounding rule.
     */
    protected function applyRounding(float $amount, string $rule): float
    {
        return match ($rule) {
            'Floor' => floor($amount),
            'Ceiling' => ceil($amount),
            default => round($amount),
        };
    }
}
