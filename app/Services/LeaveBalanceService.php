<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveBalanceService
{
    /**
     * Get or initialize leave balance for employee and leave type for a given year.
     */
    public function getOrCreateBalance(Employee $employee, LeaveType $leaveType, ?int $year = null): LeaveBalance
    {
        $year = $year ?? (int) now()->format('Y');

        return DB::transaction(function () use ($employee, $leaveType, $year) {
            $balance = LeaveBalance::withoutGlobalScopes()
                ->where('company_id', $employee->company_id)
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = LeaveBalance::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'opening_balance' => 0.00,
                    'accrued_balance' => 0.00,
                    'consumed_balance' => 0.00,
                    'reserved_balance' => 0.00,
                    'carry_forward_balance' => 0.00,
                    'expired_balance' => 0.00,
                    'adjustment_credit' => 0.00,
                    'adjustment_debit' => 0.00,
                    'closing_balance' => 0.00,
                    'available_balance' => 0.00,
                ]);
            }

            return $balance;
        });
    }

    /**
     * Initialize or set opening balance with audit ledger.
     */
    public function setOpeningBalance(
        Employee $employee,
        LeaveType $leaveType,
        float $days,
        int $year,
        ?User $actor = null,
        ?string $remarks = null
    ): LeaveBalance {
        return DB::transaction(function () use ($employee, $leaveType, $days, $year, $actor, $remarks) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);

            $before = (float) $balance->closing_balance;
            $diff = $days - (float) $balance->opening_balance;
            $balance->opening_balance = $days;
            $balance->recalculateBalances();
            $balance->save();

            LeaveBalanceTransaction::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'transaction_type' => 'Opening Balance',
                'transaction_date' => now()->toDateString(),
                'credit_days' => $diff >= 0 ? $diff : 0.00,
                'debit_days' => $diff < 0 ? abs($diff) : 0.00,
                'balance_before' => $before,
                'balance_after' => $balance->closing_balance,
                'reference_type' => 'Opening Balance Setup',
                'reference_id' => $balance->id,
                'transaction_uuid' => 'OPN-' . $employee->id . '-' . $leaveType->id . '-' . $year . '-' . Str::random(8),
                'remarks' => $remarks ?? 'Initial opening balance set to ' . $days . ' days',
                'created_by' => $actor?->id,
            ]);

            return $balance;
        });
    }

    /**
     * Reserve balance for pending application.
     */
    public function reserveBalance(Employee $employee, LeaveType $leaveType, float $days, int $year): bool
    {
        return DB::transaction(function () use ($employee, $leaveType, $days, $year) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);

            // Check if overdraft allowed or sufficient available balance
            if (!$leaveType->allow_negative_balance && ((float) $balance->available_balance < $days)) {
                return false;
            }

            if ($leaveType->allow_negative_balance) {
                $projectedAvailable = (float) $balance->available_balance - $days;
                if ($projectedAvailable < -((float) $leaveType->max_negative_balance)) {
                    return false;
                }
            }

            $balance->reserved_balance = round((float) $balance->reserved_balance + $days, 2);
            $balance->recalculateBalances();
            $balance->save();

            return true;
        });
    }

    /**
     * Release reserved balance (when application is rejected or withdrawn).
     */
    public function releaseReservedBalance(Employee $employee, LeaveType $leaveType, float $days, int $year): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days, $year) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);

            $balance->reserved_balance = max(0.00, round((float) $balance->reserved_balance - $days, 2));
            $balance->recalculateBalances();
            $balance->save();
        });
    }

    /**
     * Deduct balance upon final approval of leave application (atomic consume).
     */
    public function consumeApprovedLeave(
        LeaveApplication $application,
        float $days,
        ?User $actor = null,
        ?string $remarks = null
    ): LeaveBalance {
        return DB::transaction(function () use ($application, $days, $actor, $remarks) {
            $year = (int) $application->start_date->format('Y');
            $balance = $this->getOrCreateBalance($application->employee, $application->leaveType, $year);

            $before = (float) $balance->closing_balance;

            // Release reserved and add to consumed
            $balance->reserved_balance = max(0.00, round((float) $balance->reserved_balance - (float) $application->calculated_days, 2));
            $balance->consumed_balance = round((float) $balance->consumed_balance + $days, 2);
            $balance->recalculateBalances();
            $balance->save();

            LeaveBalanceTransaction::create([
                'company_id' => $application->company_id,
                'employee_id' => $application->employee_id,
                'leave_type_id' => $application->leave_type_id,
                'transaction_type' => 'Leave Application',
                'transaction_date' => now()->toDateString(),
                'credit_days' => 0.00,
                'debit_days' => $days,
                'balance_before' => $before,
                'balance_after' => $balance->closing_balance,
                'reference_type' => LeaveApplication::class,
                'reference_id' => $application->id,
                'transaction_uuid' => 'DED-' . $application->id . '-' . Str::random(8),
                'remarks' => $remarks ?? ('Leave approved for application ' . $application->application_number),
                'created_by' => $actor?->id,
            ]);

            return $balance;
        });
    }

    /**
     * Reverse consumed leave upon cancellation of approved application.
     */
    public function reverseApprovedLeave(
        LeaveApplication $application,
        ?User $actor = null,
        ?string $remarks = null
    ): LeaveBalance {
        return DB::transaction(function () use ($application, $actor, $remarks) {
            $year = (int) $application->start_date->format('Y');
            $balance = $this->getOrCreateBalance($application->employee, $application->leaveType, $year);

            $days = (float) ($application->approved_days ?? $application->calculated_days);
            $before = (float) $balance->closing_balance;

            $balance->consumed_balance = max(0.00, round((float) $balance->consumed_balance - $days, 2));
            $balance->recalculateBalances();
            $balance->save();

            LeaveBalanceTransaction::create([
                'company_id' => $application->company_id,
                'employee_id' => $application->employee_id,
                'leave_type_id' => $application->leave_type_id,
                'transaction_type' => 'Leave Cancellation',
                'transaction_date' => now()->toDateString(),
                'credit_days' => $days,
                'debit_days' => 0.00,
                'balance_before' => $before,
                'balance_after' => $balance->closing_balance,
                'reference_type' => LeaveApplication::class,
                'reference_id' => $application->id,
                'transaction_uuid' => 'REV-' . $application->id . '-' . Str::random(8),
                'remarks' => $remarks ?? ('Approved leave cancelled for application ' . $application->application_number),
                'created_by' => $actor?->id,
            ]);

            return $balance;
        });
    }

    /**
     * Credit monthly accrual with strict idempotency.
     */
    public function creditMonthlyAccrual(
        Employee $employee,
        LeaveType $leaveType,
        float $days,
        int $year,
        int $month,
        string $batchNumber,
        ?User $actor = null
    ): LeaveBalance {
        return DB::transaction(function () use ($employee, $leaveType, $days, $year, $month, $batchNumber, $actor) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);

            $idempotencyUuid = 'ACCR-' . $employee->company_id . '-' . $employee->id . '-' . $leaveType->id . '-' . $year . '-' . sprintf('%02d', $month);

            // Verify if already credited
            $exists = LeaveBalanceTransaction::where('transaction_uuid', $idempotencyUuid)->exists();
            if ($exists) {
                return $balance;
            }

            $before = (float) $balance->closing_balance;
            $balance->accrued_balance = round((float) $balance->accrued_balance + $days, 2);
            $balance->recalculateBalances();
            $balance->save();

            LeaveBalanceTransaction::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'transaction_type' => 'Monthly Accrual',
                'transaction_date' => now()->toDateString(),
                'credit_days' => $days,
                'debit_days' => 0.00,
                'balance_before' => $before,
                'balance_after' => $balance->closing_balance,
                'reference_type' => 'Accrual Batch ' . $batchNumber,
                'reference_id' => null,
                'transaction_uuid' => $idempotencyUuid,
                'remarks' => 'Monthly accrual credited for ' . date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year,
                'created_by' => $actor?->id,
            ]);

            return $balance;
        });
    }

    /**
     * Manual adjustment (Credit or Debit) by HR.
     */
    public function manualAdjustment(
        Employee $employee,
        LeaveType $leaveType,
        string $adjustmentType, // 'Credit' or 'Debit'
        float $days,
        int $year,
        string $reason,
        User $actor
    ): LeaveBalance {
        return DB::transaction(function () use ($employee, $leaveType, $adjustmentType, $days, $year, $reason, $actor) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);
            $before = (float) $balance->closing_balance;

            if ($adjustmentType === 'Credit') {
                $balance->adjustment_credit = round((float) $balance->adjustment_credit + $days, 2);
            } else {
                $balance->adjustment_debit = round((float) $balance->adjustment_debit + $days, 2);
            }

            $balance->recalculateBalances();
            $balance->save();

            LeaveBalanceTransaction::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'transaction_type' => 'Manual Adjustment',
                'transaction_date' => now()->toDateString(),
                'credit_days' => $adjustmentType === 'Credit' ? $days : 0.00,
                'debit_days' => $adjustmentType === 'Debit' ? $days : 0.00,
                'balance_before' => $before,
                'balance_after' => $balance->closing_balance,
                'reference_type' => 'HR Manual Adjustment',
                'reference_id' => null,
                'transaction_uuid' => 'ADJ-' . $employee->id . '-' . Str::random(10),
                'remarks' => $reason,
                'created_by' => $actor->id,
            ]);

            return $balance;
        });
    }

    /**
     * Credit Comp-Off to balance.
     */
    public function creditCompOff(
        Employee $employee,
        LeaveType $leaveType,
        float $days,
        int $year,
        int $compOffRecordId,
        ?User $actor = null
    ): LeaveBalance {
        return DB::transaction(function () use ($employee, $leaveType, $days, $year, $compOffRecordId, $actor) {
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);
            $before = (float) $balance->closing_balance;

            $balance->accrued_balance = round((float) $balance->accrued_balance + $days, 2);
            $balance->recalculateBalances();
            $balance->save();

            LeaveBalanceTransaction::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'transaction_type' => 'Comp-Off Credit',
                'transaction_date' => now()->toDateString(),
                'credit_days' => $days,
                'debit_days' => 0.00,
                'balance_before' => $before,
                'balance_after' => $balance->closing_balance,
                'reference_type' => 'Comp-Off Record',
                'reference_id' => $compOffRecordId,
                'transaction_uuid' => 'CO-' . $compOffRecordId . '-' . Str::random(8),
                'remarks' => 'Comp-Off credit for approved holiday/weekly-off duty',
                'created_by' => $actor?->id,
            ]);

            return $balance;
        });
    }
}

