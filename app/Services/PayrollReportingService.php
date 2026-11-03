<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\FnfSettlement;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use Illuminate\Support\Collection;

class PayrollReportingService
{
    /**
     * Generate detailed Payroll Register for a payroll run.
     */
    public function getPayrollRegister(PayrollRun $run): array
    {
        $employees = PayrollRunEmployee::where('payroll_run_id', $run->id)
            ->with(['employee.department', 'employee.designation'])
            ->get();

        return [
            'run' => $run,
            'employees' => $employees,
            'summary' => [
                'headcount' => $employees->count(),
                'total_gross' => $run->total_gross_earnings,
                'total_pf_employee' => $employees->sum('pf_employee'),
                'total_esi_employee' => $employees->sum('esi_employee'),
                'total_pt' => $employees->sum('professional_tax'),
                'total_lwf_employee' => $employees->sum('labour_welfare_fund'),
                'total_tds' => $employees->sum('income_tax_tds'),
                'total_loan_emi' => $employees->sum('loan_emi_deduction'),
                'total_fine_recovery' => $employees->sum('fine_recovery_deduction'),
                'total_deductions' => $run->total_deductions,
                'total_net_payout' => $run->total_net_payout,
                'total_employer_contributions' => $run->total_employer_contributions,
            ],
        ];
    }

    /**
     * Generate Bank Advice statement (NEFT payment format).
     */
    public function getBankAdviceData(PayrollRun $run): Collection
    {
        return PayrollRunEmployee::where('payroll_run_id', $run->id)
            ->with('employee')
            ->get()
            ->map(function ($item) {
                return [
                    'employee_code' => $item->employee?->employee_code ?? 'N/A',
                    'employee_name' => $item->employee?->full_name ?? 'N/A',
                    'net_salary' => (float) $item->net_salary,
                    'payment_status' => $item->payment_status,
                    'payment_reference' => $item->payment_reference ?? 'PENDING',
                ];
            });
    }

    /**
     * Generate PF Electronic Challan cum Return (ECR) data.
     */
    public function getPfReportData(PayrollRun $run): Collection
    {
        return PayrollRunEmployee::where('payroll_run_id', $run->id)
            ->where('pf_employee', '>', 0)
            ->with('employee')
            ->get()
            ->map(function ($item) {
                $basicDa = (float) ($item->basic_salary + $item->da);
                $epfWages = min(15000.00, $basicDa);
                return [
                    'employee_code' => $item->employee?->employee_code,
                    'employee_name' => $item->employee?->full_name,
                    'gross_wages' => (float) $item->gross_earnings,
                    'epf_wages' => $epfWages,
                    'eps_wages' => $epfWages,
                    'ee_share' => (float) $item->pf_employee,
                    'er_share' => (float) $item->pf_employer,
                ];
            });
    }

    /**
     * Generate ESI Return data.
     */
    public function getEsiReportData(PayrollRun $run): Collection
    {
        return PayrollRunEmployee::where('payroll_run_id', $run->id)
            ->where('esi_employee', '>', 0)
            ->with('employee')
            ->get()
            ->map(function ($item) {
                return [
                    'employee_code' => $item->employee?->employee_code,
                    'employee_name' => $item->employee?->full_name,
                    'worked_days' => (float) $item->paid_days,
                    'gross_wages' => (float) $item->gross_earnings,
                    'employee_contribution' => (float) $item->esi_employee,
                    'employer_contribution' => (float) $item->esi_employer,
                ];
            });
    }

    /**
     * Generate Month-on-Month Variance Report.
     */
    public function getVarianceReport(PayrollRun $currentRun): array
    {
        $prevMonth = $currentRun->month === 1 ? 12 : ($currentRun->month - 1);
        $prevYear = $currentRun->month === 1 ? ($currentRun->year - 1) : $currentRun->year;

        $previousRun = PayrollRun::where('company_id', $currentRun->company_id)
            ->where('year', $prevYear)
            ->where('month', $prevMonth)
            ->whereIn('status', ['Approved', 'Locked', 'Disbursed'])
            ->first();

        $prevHeadcount = $previousRun ? $previousRun->total_processed_employees : 0;
        $prevGross = $previousRun ? (float) $previousRun->total_gross_earnings : 0.00;
        $prevNet = $previousRun ? (float) $previousRun->total_net_payout : 0.00;

        $currHeadcount = $currentRun->total_processed_employees;
        $currGross = (float) $currentRun->total_gross_earnings;
        $currNet = (float) $currentRun->total_net_payout;

        return [
            'current_period' => "{$currentRun->year}-" . sprintf('%02d', $currentRun->month),
            'previous_period' => "{$prevYear}-" . sprintf('%02d', $prevMonth),
            'headcount_diff' => $currHeadcount - $prevHeadcount,
            'gross_payout_diff' => round($currGross - $prevGross, 2),
            'net_payout_diff' => round($currNet - $prevNet, 2),
            'percentage_gross_change' => $prevGross > 0 ? round((($currGross - $prevGross) / $prevGross) * 100, 2) : 0.00,
        ];
    }
}

