<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanEmiSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanManagementService
{
    /**
     * Submit an employee loan application.
     */
    public function applyLoan(Employee $employee, array $data): EmployeeLoan
    {
        $principal = (float) $data['principal_amount'];
        $tenure = (int) ($data['tenure_months'] ?? 1);
        $interestRate = (float) ($data['interest_rate_percent'] ?? 0.00);

        // Simple straight-line EMI calculation: (Principal * (1 + (Rate/100) * (Tenure/12))) / Tenure
        $totalInterest = $interestRate > 0 ? ($principal * ($interestRate / 100.0) * ($tenure / 12.0)) : 0.00;
        $totalPayable = $principal + $totalInterest;
        $monthlyEmi = $tenure > 0 ? round($totalPayable / $tenure, 2) : $principal;

        $sequence = EmployeeLoan::withoutGlobalScopes()->where('company_id', $employee->company_id)->count() + 1;
        $appNumber = sprintf('LN-%s-%d-%04d-%s', $employee->company->code ?? 'VE', date('Y'), $sequence, strtoupper(Str::random(4)));

        return EmployeeLoan::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'loan_application_number' => $appNumber,
            'loan_type' => $data['loan_type'] ?? 'Salary Advance',
            'principal_amount' => $principal,
            'interest_rate_percent' => $interestRate,
            'tenure_months' => $tenure,
            'monthly_emi' => $monthlyEmi,
            'disbursed_amount' => 0.00,
            'total_recovered' => 0.00,
            'remaining_balance' => $principal,
            'reason' => $data['reason'] ?? null,
            'status' => 'Applied',
        ]);
    }

    /**
     * Approve loan (Manager or HR step).
     */
    public function approveLoan(EmployeeLoan $loan, User $actor, string $level = 'HR'): EmployeeLoan
    {
        if ($level === 'Manager') {
            $loan->update(['status' => 'Manager Approved']);
        } else {
            $loan->update([
                'status' => 'HR Approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);
        }

        return $loan;
    }

    /**
     * Disburse approved loan and generate monthly EMI schedule.
     */
    public function disburseLoan(EmployeeLoan $loan, ?string $disbursedDate = null): EmployeeLoan
    {
        return DB::transaction(function () use ($loan, $disbursedDate) {
            $date = $disbursedDate ? Carbon::parse($disbursedDate) : now();

            $loan->update([
                'status' => 'Active',
                'disbursed_amount' => $loan->principal_amount,
                'disbursed_date' => $date->toDateString(),
            ]);

            // Delete any existing schedule
            EmployeeLoanEmiSchedule::where('employee_loan_id', $loan->id)->delete();

            $principalPerMonth = round($loan->principal_amount / $loan->tenure_months, 2);
            $totalEmi = $loan->monthly_emi;
            $interestPerMonth = round(max(0.00, $totalEmi - $principalPerMonth), 2);

            $currentDueDate = $date->copy()->startOfMonth()->addMonth(); // 1st EMI due next month

            for ($i = 1; $i <= $loan->tenure_months; $i++) {
                // Adjust last installment for rounding
                $principalComponent = ($i === $loan->tenure_months)
                    ? round($loan->principal_amount - ($principalPerMonth * ($loan->tenure_months - 1)), 2)
                    : $principalPerMonth;

                $emiAmount = round($principalComponent + $interestPerMonth, 2);

                EmployeeLoanEmiSchedule::create([
                    'company_id' => $loan->company_id,
                    'employee_loan_id' => $loan->id,
                    'installment_number' => $i,
                    'due_month' => $currentDueDate->month,
                    'due_year' => $currentDueDate->year,
                    'emi_amount' => $emiAmount,
                    'principal_component' => $principalComponent,
                    'interest_component' => $interestPerMonth,
                    'status' => 'Pending',
                ]);

                $currentDueDate->addMonth();
            }

            return $loan;
        });
    }

    /**
     * Foreclose or settle loan early.
     */
    public function forecloseLoan(EmployeeLoan $loan, User $actor, ?string $remarks = null): EmployeeLoan
    {
        return DB::transaction(function () use ($loan, $actor, $remarks) {
            $loan->update([
                'total_recovered' => $loan->principal_amount,
                'remaining_balance' => 0.00,
                'status' => 'Closed',
            ]);

            EmployeeLoanEmiSchedule::where('employee_loan_id', $loan->id)
                ->where('status', 'Pending')
                ->update([
                    'status' => 'Paid via FNF',
                    'deducted_at' => now(),
                ]);

            return $loan;
        });
    }

    /**
     * Reject loan application.
     */
    public function rejectLoan(EmployeeLoan $loan, User $actor, string $reason): EmployeeLoan
    {
        $loan->update([
            'status' => 'Rejected',
            'rejection_reason' => $reason,
        ]);

        return $loan;
    }
}

