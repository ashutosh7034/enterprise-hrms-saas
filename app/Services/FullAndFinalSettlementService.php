<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryAssignment;
use App\Models\FineRecoveryRecord;
use App\Models\FnfSettlement;
use App\Models\FnfSettlementComponent;
use App\Models\LeaveBalance;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FullAndFinalSettlementService
{
    /**
     * Prepare a draft Full & Final (F&F) settlement for an employee.
     */
    public function prepareDraftSettlement(Employee $employee, array $data, User $actor): FnfSettlement
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $resignationDate = Carbon::parse($data['resignation_date']);
            $lastWorkingDate = Carbon::parse($data['last_working_date']);

            // 1. Notice Period Analysis
            $requiredNoticeDays = (int) ($data['notice_period_required_days'] ?? ($employee->category?->notice_period_days ?? 30));
            $servedNoticeDays = (int) ($data['notice_period_served_days'] ?? $resignationDate->diffInDays($lastWorkingDate));
            $shortfallDays = max(0, $requiredNoticeDays - $servedNoticeDays);

            // 2. Resolve Active Salary
            $assignment = EmployeeSalaryAssignment::where('employee_id', $employee->id)
                ->where('status', 'Approved')
                ->latest('effective_from_date')
                ->first();

            $grossMonthly = $assignment ? (float) $assignment->gross_monthly : 0.00;
            $componentValues = $assignment ? ($assignment->component_values_json ?? []) : [];
            $basicSalary = (float) ($componentValues['BASIC'] ?? ($grossMonthly * 0.50));
            $perDayGross = $grossMonthly > 0 ? round($grossMonthly / 30.0, 2) : 0.00;
            $perDayBasic = $basicSalary > 0 ? round($basicSalary / 30.0, 2) : 0.00;

            // 3. Unpaid Salary in Final Month
            $unpaidDays = (float) ($data['unpaid_salary_days'] ?? $lastWorkingDate->day);
            $unpaidSalaryAmount = round($unpaidDays * $perDayGross, 2);

            // 4. Leave Encashment (Earned / Privilege Leaves)
            $plBalance = LeaveBalance::where('employee_id', $employee->id)
                ->whereHas('leaveType', fn($q) => $q->where('code', 'PL'))
                ->where('year', $lastWorkingDate->year)
                ->value('available_balance') ?? 0.00;

            $encashableDays = (float) ($data['leave_encashment_days'] ?? max(0.00, (float) $plBalance));
            $leaveEncashmentAmount = round($encashableDays * $perDayBasic, 2);

            // 5. Gratuity Calculation (Eligibility: >= 5 years continuous service)
            $doj = $employee->date_of_joining ? Carbon::parse($employee->date_of_joining) : $resignationDate;
            $tenureYears = round($doj->diffInDays($lastWorkingDate) / 365.25, 2);
            $gratuityAmount = 0.00;

            if ($tenureYears >= 5.0 || !empty($data['force_gratuity'])) {
                // Payment of Gratuity Act formula: (15 * Last Drawn Basic * Completed Years) / 26
                $completedYears = floor($tenureYears);
                $gratuityAmount = round((15.0 * $basicSalary * $completedYears) / 26.0, 2);
            }

            // 6. Deductions
            $noticeShortfallDeduction = round($shortfallDays * $perDayGross, 2);

            // Outstanding Loans
            $loanOutstanding = EmployeeLoan::where('company_id', $employee->company_id)
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['Active', 'Disbursed'])
                ->sum('remaining_balance');

            // Pending Asset Damage Fines
            $assetFineDeduction = FineRecoveryRecord::where('company_id', $employee->company_id)
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['Pending', 'Queued for Phase 5 Payroll'])
                ->sum('amount');

            $otherDeductions = (float) ($data['other_deductions_amount'] ?? 0.00);

            // 7. Grand Totals
            $grossSettlement = round($unpaidSalaryAmount + $leaveEncashmentAmount + $gratuityAmount, 2);
            $totalDeductions = round($noticeShortfallDeduction + $loanOutstanding + $assetFineDeduction + $otherDeductions, 2);
            $rawNet = $grossSettlement - $totalDeductions;

            $settlementType = $rawNet >= 0 ? 'Payable to Employee' : 'Recoverable from Employee';
            $netSettlement = abs(round($rawNet, 2));

            $sequence = FnfSettlement::withoutGlobalScopes()->where('company_id', $employee->company_id)->count() + 1;
            $settlementNumber = sprintf('FNF-%s-%d-%04d-%s', $employee->company->code ?? 'VE', date('Y'), $sequence, strtoupper(Str::random(4)));

            $settlement = FnfSettlement::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'settlement_number' => $settlementNumber,
                'resignation_date' => $resignationDate->toDateString(),
                'last_working_date' => $lastWorkingDate->toDateString(),
                'notice_period_required_days' => $requiredNoticeDays,
                'notice_period_served_days' => $servedNoticeDays,
                'notice_shortfall_days' => $shortfallDays,
                'unpaid_salary_days' => $unpaidDays,
                'unpaid_salary_amount' => $unpaidSalaryAmount,
                'leave_encashment_days' => $encashableDays,
                'leave_encashment_amount' => $leaveEncashmentAmount,
                'gratuity_amount' => $gratuityAmount,
                'notice_shortfall_deduction' => $noticeShortfallDeduction,
                'loan_outstanding_deduction' => (float) $loanOutstanding,
                'asset_damage_fine_deduction' => (float) $assetFineDeduction,
                'other_deductions_amount' => $otherDeductions,
                'gross_settlement_amount' => $grossSettlement,
                'total_deductions_amount' => $totalDeductions,
                'net_settlement_amount' => $netSettlement,
                'settlement_type' => $settlementType,
                'status' => 'Draft',
                'remarks' => $data['remarks'] ?? null,
            ]);

            // Save component breakdown
            FnfSettlementComponent::create([
                'fnf_settlement_id' => $settlement->id,
                'component_category' => 'Earning',
                'component_name' => "Unpaid Wages ({$unpaidDays} days)",
                'amount' => $unpaidSalaryAmount,
            ]);
            if ($leaveEncashmentAmount > 0) {
                FnfSettlementComponent::create([
                    'fnf_settlement_id' => $settlement->id,
                    'component_category' => 'Earning',
                    'component_name' => "Leave Encashment ({$encashableDays} PL days)",
                    'amount' => $leaveEncashmentAmount,
                ]);
            }
            if ($gratuityAmount > 0) {
                FnfSettlementComponent::create([
                    'fnf_settlement_id' => $settlement->id,
                    'component_category' => 'Earning',
                    'component_name' => "Gratuity Payout ({$completedYears} years service)",
                    'amount' => $gratuityAmount,
                ]);
            }
            if ($noticeShortfallDeduction > 0) {
                FnfSettlementComponent::create([
                    'fnf_settlement_id' => $settlement->id,
                    'component_category' => 'Deduction',
                    'component_name' => "Notice Shortfall ({$shortfallDays} days)",
                    'amount' => $noticeShortfallDeduction,
                ]);
            }
            if ($loanOutstanding > 0) {
                FnfSettlementComponent::create([
                    'fnf_settlement_id' => $settlement->id,
                    'component_category' => 'Deduction',
                    'component_name' => "Outstanding Loan Principal",
                    'amount' => $loanOutstanding,
                ]);
            }
            if ($assetFineDeduction > 0) {
                FnfSettlementComponent::create([
                    'fnf_settlement_id' => $settlement->id,
                    'component_category' => 'Deduction',
                    'component_name' => "Asset Damage / Loss Fine Clearance",
                    'amount' => $assetFineDeduction,
                ]);
            }

            return $settlement;
        });
    }

    /**
     * Approve or finalize F&F settlement.
     */
    public function finalizeSettlement(FnfSettlement $settlement, User $actor, string $action = 'Approve'): FnfSettlement
    {
        return DB::transaction(function () use ($settlement, $actor, $action) {
            if ($action === 'Verify') {
                $settlement->update([
                    'status' => 'HR Verified',
                    'verified_by' => $actor->id,
                ]);
            } elseif ($action === 'Approve') {
                $settlement->update([
                    'status' => 'Finance Approved',
                    'approved_by' => $actor->id,
                ]);
                \App\Events\FnfSettlementEvent::dispatch($settlement, 'approved');
            } elseif ($action === 'Settle') {
                $settlement->update([
                    'status' => 'Settled',
                    'settled_at' => now(),
                ]);
                \App\Events\FnfSettlementEvent::dispatch($settlement, 'paid');

                // Close loans
                EmployeeLoan::where('employee_id', $settlement->employee_id)
                    ->whereIn('status', ['Active', 'Disbursed'])
                    ->update([
                        'remaining_balance' => 0.00,
                        'status' => 'Closed',
                    ]);

                // Settle fines
                FineRecoveryRecord::where('employee_id', $settlement->employee_id)
                    ->whereIn('status', ['Pending', 'Queued for Phase 5 Payroll'])
                    ->update([
                        'status' => 'Paid',
                        'paid_date' => now()->toDateString(),
                        'receipt_reference' => "FNF-{$settlement->settlement_number}",
                    ]);

                // Update employee status to Resigned
                $settlement->employee?->update(['status' => 'Resigned']);
            }

            return $settlement;
        });
    }

    /**
     * Generate PDF statement for an F&F settlement using Dompdf.
     */
    public function generateStatementPdf(FnfSettlement $settlement): string
    {
        $settlement->loadMissing(['employee.department', 'employee.designation', 'company', 'components']);

        $html = view('payroll.fnf.statement_template', ['settlement' => $settlement])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
