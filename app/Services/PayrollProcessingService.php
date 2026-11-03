<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineRecoveryRecord;
use App\Models\PayrollApprovalStep;
use App\Models\PayrollComponentTransaction;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollProcessingService
{
    public function __construct(
        protected PayrollPreValidationService $preValidationService,
        protected PayrollCalculationService $calculationService
    ) {}

    /**
     * Find or initialize a payroll period.
     */
    public function getOrCreatePeriod(int $companyId, int $year, int $month): PayrollPeriod
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $cutoffDate = Carbon::createFromDate($year, $month, min(25, $endDate->day));

        return PayrollPeriod::firstOrCreate(
            ['company_id' => $companyId, 'year' => $year, 'month' => $month],
            [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'cutoff_date' => $cutoffDate->toDateString(),
                'status' => 'Open',
            ]
        );
    }

    /**
     * Execute a payroll calculation run for a company, year, and month.
     */
    public function executePayrollRun(Company $company, int $year, int $month, User $processor): PayrollRun
    {
        $period = $this->getOrCreatePeriod($company->id, $year, $month);

        // Duplicate run prevention
        $existingApproved = PayrollRun::where('company_id', $company->id)
            ->where('payroll_period_id', $period->id)
            ->whereIn('status', ['Approved', 'Locked', 'Disbursed'])
            ->exists();

        if ($existingApproved) {
            throw new \RuntimeException("An approved or locked payroll run already exists for this period. Duplicate execution is prohibited.");
        }

        // Run Pre-validation
        $validation = $this->preValidationService->validatePayrollEligibility($company->id, $year, $month);

        if (empty($validation['eligible_employees'])) {
            throw new \RuntimeException("No eligible employees found for payroll processing. Please resolve onboarding or salary structure prerequisites.");
        }

        return DB::transaction(function () use ($company, $period, $year, $month, $processor, $validation) {
            // Delete any existing draft runs for this period
            $existingDrafts = PayrollRun::where('company_id', $company->id)
                ->where('payroll_period_id', $period->id)
                ->whereIn('status', ['Draft', 'Pre-Validation Passed', 'Pending Approval', 'Rejected'])
                ->get();

            foreach ($existingDrafts as $draft) {
                $draft->delete();
            }

            $batchNumber = sprintf('PAY-%s-%d%02d-%s', $company->code, $year, $month, strtoupper(Str::random(5)));

            $payrollRun = PayrollRun::create([
                'company_id' => $company->id,
                'payroll_period_id' => $period->id,
                'run_batch_number' => $batchNumber,
                'year' => $year,
                'month' => $month,
                'total_eligible_employees' => $validation['eligible_count'],
                'total_processed_employees' => 0,
                'status' => 'Draft',
                'processed_by' => $processor->id,
                'validation_errors_json' => [
                    'ineligible_employees' => $validation['ineligible_employees'],
                    'warnings' => $validation['warnings'],
                ],
            ]);

            $totalGross = 0.00;
            $totalStatutory = 0.00;
            $totalOther = 0.00;
            $totalDeductions = 0.00;
            $totalNet = 0.00;
            $totalEmployer = 0.00;

            foreach ($validation['eligible_employees'] as $item) {
                $employee = Employee::withoutGlobalScopes()->find($item['employee_id']);
                if (!$employee) {
                    continue;
                }

                $calc = $this->calculationService->calculateEmployeePayroll($employee, $year, $month);

                $payslipNumber = sprintf('PS-%s-%d%02d-%s', $company->code, $year, $month, $employee->employee_code);

                $runEmployee = PayrollRunEmployee::create([
                    'company_id' => $company->id,
                    'payroll_run_id' => $payrollRun->id,
                    'employee_id' => $employee->id,
                    'employee_salary_assignment_id' => $calc['assignment_id'],
                    'year' => $year,
                    'month' => $month,
                    'calendar_days' => $calc['calendar_days'],
                    'worked_days' => $calc['worked_days'],
                    'paid_leave_days' => $calc['paid_leave_days'],
                    'weekly_off_days' => $calc['weekly_off_days'],
                    'holiday_days' => $calc['holiday_days'],
                    'loss_of_pay_days' => $calc['loss_of_pay_days'],
                    'paid_days' => $calc['paid_days'],
                    'overtime_hours' => $calc['overtime_hours'],
                    'overtime_amount' => $calc['overtime_amount'],
                    'basic_salary' => $calc['basic_salary'],
                    'hra' => $calc['hra'],
                    'da' => $calc['da'],
                    'allowances_amount' => $calc['allowances_amount'],
                    'gross_earnings' => $calc['gross_earnings'],
                    'pf_employee' => $calc['pf_employee'],
                    'esi_employee' => $calc['esi_employee'],
                    'professional_tax' => $calc['professional_tax'],
                    'labour_welfare_fund' => $calc['labour_welfare_fund'],
                    'income_tax_tds' => $calc['income_tax_tds'],
                    'pf_employer' => $calc['pf_employer'],
                    'esi_employer' => $calc['esi_employer'],
                    'loan_emi_deduction' => $calc['loan_emi_deduction'],
                    'fine_recovery_deduction' => $calc['fine_recovery_deduction'],
                    'other_deductions' => $calc['other_deductions'],
                    'total_deductions' => $calc['total_deductions'],
                    'net_salary' => $calc['net_salary'],
                    'payment_status' => 'Pending',
                    'payslip_number' => $payslipNumber,
                    'calculation_snapshot_json' => $calc['snapshot'],
                ]);

                // Record itemized component transactions
                foreach ($calc['transactions'] as $tx) {
                    PayrollComponentTransaction::create([
                        'payroll_run_employee_id' => $runEmployee->id,
                        'component_type' => $tx['type'],
                        'component_code' => $tx['code'],
                        'component_name' => $tx['name'],
                        'standard_amount' => $tx['standard'],
                        'earned_or_deducted_amount' => $tx['amount'],
                    ]);
                }

                $totalGross += $calc['gross_earnings'];
                $totalStatutory += $calc['total_statutory_deductions'];
                $totalOther += $calc['other_deductions'];
                $totalDeductions += $calc['total_deductions'];
                $totalNet += $calc['net_salary'];
                $totalEmployer += $calc['total_employer_contributions'];
            }

            $payrollRun->update([
                'total_processed_employees' => count($validation['eligible_employees']),
                'total_gross_earnings' => round($totalGross, 2),
                'total_statutory_deductions' => round($totalStatutory, 2),
                'total_other_deductions' => round($totalOther, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net_payout' => round($totalNet, 2),
                'total_employer_contributions' => round($totalEmployer, 2),
                'status' => 'Pre-Validation Passed',
            ]);

            $period->update(['status' => 'Processing']);

            return $payrollRun;
        });
    }

    /**
     * Submit payroll run for approval.
     */
    public function submitForApproval(PayrollRun $run, User $actor, ?string $remarks = null): PayrollRun
    {
        $run->update(['status' => 'Pending Approval']);

        PayrollApprovalStep::create([
            'payroll_run_id' => $run->id,
            'stage' => 'Processing',
            'action' => 'Submitted',
            'action_by' => $actor->id,
            'action_at' => now(),
            'remarks' => $remarks ?? 'Submitted for management and finance approval',
        ]);

        return $run;
    }

    /**
     * Approve payroll run by authorized HO Admin or Finance lead.
     */
    public function approvePayrollRun(PayrollRun $run, User $approver, ?string $remarks = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $approver, $remarks) {
            $run->update([
                'status' => 'Approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            PayrollApprovalStep::create([
                'payroll_run_id' => $run->id,
                'stage' => 'HO Approval',
                'action' => 'Approved',
                'action_by' => $approver->id,
                'action_at' => now(),
                'remarks' => $remarks ?? 'Payroll approved for disbursement',
            ]);

            // Deduct loan EMIs permanently
            foreach ($run->payrollEmployees as $empPay) {
                if ($empPay->loan_emi_deduction > 0) {
                    $schedule = EmployeeLoanEmiSchedule::where('company_id', $run->company_id)
                        ->whereHas('loan', fn($q) => $q->where('employee_id', $empPay->employee_id))
                        ->where('due_year', $run->year)
                        ->where('due_month', $run->month)
                        ->where('status', 'Pending')
                        ->first();

                    if ($schedule) {
                        $schedule->update([
                            'status' => 'Deducted in Payroll',
                            'payroll_run_id' => $run->id,
                            'deducted_at' => now(),
                        ]);

                        $loan = $schedule->loan;
                        $newRecovered = (float) $loan->total_recovered + (float) $schedule->emi_amount;
                        $newRemaining = max(0.00, (float) $loan->remaining_balance - (float) $schedule->principal_component);

                        $loan->update([
                            'total_recovered' => $newRecovered,
                            'remaining_balance' => $newRemaining,
                            'status' => $newRemaining <= 0 ? 'Closed' : 'Active',
                        ]);
                    }
                }

                // Settle fine recovery records
                if ($empPay->fine_recovery_deduction > 0) {
                    $fine = FineRecoveryRecord::where('company_id', $run->company_id)
                        ->where('employee_id', $empPay->employee_id)
                        ->where('status', 'Queued for Phase 5 Payroll')
                        ->first();

                    if ($fine) {
                        $fine->update([
                            'status' => 'Paid',
                            'paid_date' => now()->toDateString(),
                            'receipt_reference' => "PAYROLL-{$run->run_batch_number}",
                            'recorded_by' => $approver->id,
                            'remarks' => "Recovered via Payroll Run {$run->run_batch_number}",
                        ]);
                    }
                }
            }

            return $run;
        });
    }

    /**
     * Reject payroll run with mandatory justification.
     */
    public function rejectPayrollRun(PayrollRun $run, User $actor, string $rejectionRemarks): PayrollRun
    {
        $run->update([
            'status' => 'Rejected',
            'rejection_remarks' => $rejectionRemarks,
        ]);

        PayrollApprovalStep::create([
            'payroll_run_id' => $run->id,
            'stage' => 'HO Approval',
            'action' => 'Rejected',
            'action_by' => $actor->id,
            'action_at' => now(),
            'remarks' => $rejectionRemarks,
        ]);

        $run->payrollPeriod?->update(['status' => 'Open']);

        return $run;
    }

    /**
     * Lock approved payroll run and close period against edits.
     */
    public function lockPayrollRun(PayrollRun $run, User $actor): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor) {
            $run->update(['status' => 'Locked']);
            $run->payrollPeriod?->update([
                'status' => 'Locked',
                'locked_by' => $actor->id,
                'locked_at' => now(),
            ]);

            return $run;
        });
    }

    /**
     * Mark payroll run as disbursed.
     */
    public function markAsDisbursed(PayrollRun $run, string $paymentReference = 'BANK-NEFT-DISBURSED'): PayrollRun
    {
        return DB::transaction(function () use ($run, $paymentReference) {
            $run->update(['status' => 'Disbursed']);
            
            $run->payrollEmployees()->update([
                'payment_status' => 'Paid',
                'payment_reference' => $paymentReference,
                'paid_at' => now(),
            ]);

            $run->payrollPeriod?->update(['status' => 'Closed']);

            return $run;
        });
    }
}

