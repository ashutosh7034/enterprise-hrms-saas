<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveCarryForwardBatch;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveCarryForwardService
{
    public function __construct(
        protected LeaveBalanceService $balanceService
    ) {}

    /**
     * Process or simulate carry-forward and expiry between two years.
     */
    public function processYearEnd(
        int $companyId,
        int $fromYear,
        int $toYear,
        ?int $leaveTypeId = null,
        bool $dryRun = false,
        ?User $actor = null
    ): array {
        return DB::transaction(function () use ($companyId, $fromYear, $toYear, $leaveTypeId, $dryRun, $actor) {
            $batchNumber = 'CF-' . $companyId . '-' . $fromYear . '-' . $toYear . '-' . Str::upper(Str::random(6));

            $batch = LeaveCarryForwardBatch::create([
                'company_id' => $companyId,
                'batch_number' => $batchNumber,
                'from_year' => $fromYear,
                'to_year' => $toYear,
                'leave_type_id' => $leaveTypeId,
                'is_dry_run' => $dryRun,
                'status' => $dryRun ? 'Simulated' : 'Completed',
                'executed_by' => $actor?->id,
            ]);

            $leaveTypesQuery = LeaveType::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', 'Active');

            if ($leaveTypeId) {
                $leaveTypesQuery->where('id', $leaveTypeId);
            }

            $leaveTypes = $leaveTypesQuery->get();

            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', 'Active')
                ->get();

            $processedCount = 0;
            $totalCarriedForward = 0.00;
            $totalExpired = 0.00;
            $log = [];

            foreach ($leaveTypes as $leaveType) {
                foreach ($employees as $employee) {
                    $oldBalance = LeaveBalance::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->where('employee_id', $employee->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $fromYear)
                        ->first();

                    if (!$oldBalance) {
                        continue;
                    }

                    $closing = (float) $oldBalance->closing_balance;
                    if ($closing <= 0) {
                        continue;
                    }

                    $processedCount++;
                    $carried = 0.00;
                    $expired = 0.00;

                    if ($leaveType->is_carry_forward_enabled) {
                        $limit = (float) $leaveType->carry_forward_limit;
                        if ($limit > 0) {
                            $carried = min($closing, $limit);
                            $expired = max(0.00, $closing - $carried);
                        } else {
                            $carried = $closing;
                            $expired = 0.00;
                        }
                    } else {
                        $carried = 0.00;
                        $expired = $closing;
                    }

                    $totalCarriedForward += $carried;
                    $totalExpired += $expired;

                    $log[] = [
                        'employee_code' => $employee->employee_code,
                        'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                        'leave_type' => $leaveType->code,
                        'previous_closing' => $closing,
                        'carried_forward' => $carried,
                        'expired' => $expired,
                    ];

                    if (!$dryRun) {
                        // 1. Mark expired in previous year balance
                        if ($expired > 0) {
                            $oldBalance->expired_balance = round((float) $oldBalance->expired_balance + $expired, 2);
                            $oldBalance->recalculateBalances();
                            $oldBalance->save();

                            LeaveBalanceTransaction::create([
                                'company_id' => $companyId,
                                'employee_id' => $employee->id,
                                'leave_type_id' => $leaveType->id,
                                'transaction_type' => 'Expiry',
                                'transaction_date' => now()->toDateString(),
                                'credit_days' => 0.00,
                                'debit_days' => $expired,
                                'balance_before' => $closing,
                                'balance_after' => $oldBalance->closing_balance,
                                'reference_type' => 'Year-End Expiry',
                                'reference_id' => $batch->id,
                                'transaction_uuid' => 'EXP-' . $employee->id . '-' . $leaveType->id . '-' . $fromYear . '-' . Str::random(8),
                                'remarks' => 'Expired uncarried balance for year ' . $fromYear,
                                'created_by' => $actor?->id,
                            ]);
                        }

                        // 2. Set carry forward balance in new year
                        if ($carried > 0) {
                            $newBalance = $this->balanceService->getOrCreateBalance($employee, $leaveType, $toYear);
                            $beforeNew = (float) $newBalance->closing_balance;
                            $newBalance->carry_forward_balance = round((float) $newBalance->carry_forward_balance + $carried, 2);
                            $newBalance->recalculateBalances();
                            $newBalance->save();

                            LeaveBalanceTransaction::create([
                                'company_id' => $companyId,
                                'employee_id' => $employee->id,
                                'leave_type_id' => $leaveType->id,
                                'transaction_type' => 'Carry Forward',
                                'transaction_date' => now()->toDateString(),
                                'credit_days' => $carried,
                                'debit_days' => 0.00,
                                'balance_before' => $beforeNew,
                                'balance_after' => $newBalance->closing_balance,
                                'reference_type' => 'Year-End Carry Forward',
                                'reference_id' => $batch->id,
                                'transaction_uuid' => 'CF-' . $employee->id . '-' . $leaveType->id . '-' . $toYear . '-' . Str::random(8),
                                'remarks' => 'Carried forward from year ' . $fromYear,
                                'created_by' => $actor?->id,
                            ]);
                        }
                    }
                }
            }

            $batch->update([
                'total_employees_processed' => $processedCount,
                'total_days_carried_forward' => $totalCarriedForward,
                'total_days_expired' => $totalExpired,
                'execution_log_json' => array_slice($log, 0, 100), // store preview sample
            ]);

            return [
                'batch' => $batch,
                'is_dry_run' => $dryRun,
                'total_processed' => $processedCount,
                'total_carried_forward' => $totalCarriedForward,
                'total_expired' => $totalExpired,
                'log' => $log,
            ];
        });
    }
}

