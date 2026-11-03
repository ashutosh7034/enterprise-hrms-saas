<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveAccrualBatch;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveAccrualService
{
    public function __construct(
        protected LeaveBalanceService $balanceService
    ) {}

    /**
     * Run monthly accrual for a company, year, and month.
     * Can optionally target a specific leave type.
     */
    public function processMonthlyAccrual(
        int $companyId,
        int $year,
        int $month,
        ?int $leaveTypeId = null,
        ?User $actor = null
    ): array {
        return DB::transaction(function () use ($companyId, $year, $month, $leaveTypeId, $actor) {
            $batchNumber = 'ACCR-' . $companyId . '-' . $year . '-' . sprintf('%02d', $month) . '-' . Str::upper(Str::random(6));

            $batch = LeaveAccrualBatch::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'year' => $year,
                    'month' => $month,
                    'leave_type_id' => $leaveTypeId,
                ],
                [
                    'batch_number' => $batchNumber,
                    'status' => 'Initiated',
                    'executed_by' => $actor?->id,
                ]
            );

            if ($batch->status === 'Completed') {
                return [
                    'status' => 'Already Processed',
                    'batch' => $batch,
                    'credited_count' => $batch->total_credited_employees,
                    'total_days' => (float) $batch->total_days_credited,
                ];
            }

            // Get eligible leave types
            $leaveTypesQuery = LeaveType::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', 'Active')
                ->where('is_accrual_enabled', true)
                ->where('monthly_accrual_rate', '>', 0);

            if ($leaveTypeId) {
                $leaveTypesQuery->where('id', $leaveTypeId);
            }

            $leaveTypes = $leaveTypesQuery->get();

            // Fetch active employees
            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('status', 'Active')
                ->get();

            $totalEligible = 0;
            $totalCredited = 0;
            $totalDaysCredited = 0.00;

            foreach ($leaveTypes as $leaveType) {
                foreach ($employees as $employee) {
                    if (!$leaveType->isApplicableToEmployee($employee)) {
                        continue;
                    }

                    // Confirmation check
                    if ($leaveType->requires_confirmation && empty($employee->confirmation_date)) {
                        continue;
                    }

                    $totalEligible++;

                    $rate = (float) $leaveType->monthly_accrual_rate;

                    // If joined in the current month, calculate pro-rated accrual
                    if ($employee->date_of_joining) {
                        $joiningYear = (int) $employee->date_of_joining->format('Y');
                        $joiningMonth = (int) $employee->date_of_joining->format('m');
                        if ($joiningYear === $year && $joiningMonth === $month) {
                            $totalDaysInMonth = (int) cal_days_in_month(CAL_GREGORIAN, $month, $year);
                            $workedDays = $totalDaysInMonth - (int) $employee->date_of_joining->format('d') + 1;
                            $rate = round(($workedDays / $totalDaysInMonth) * $rate, 2);
                        }
                    }

                    if ($rate > 0) {
                        $this->balanceService->creditMonthlyAccrual(
                            employee: $employee,
                            leaveType: $leaveType,
                            days: $rate,
                            year: $year,
                            month: $month,
                            batchNumber: $batch->batch_number,
                            actor: $actor
                        );

                        $totalCredited++;
                        $totalDaysCredited += $rate;
                    }
                }
            }

            $batch->update([
                'total_eligible_employees' => $totalEligible,
                'total_credited_employees' => $totalCredited,
                'total_days_credited' => $totalDaysCredited,
                'status' => 'Completed',
                'execution_notes' => 'Accrual successfully executed for ' . date('F', mktime(0, 0, 0, $month, 10)) . ' ' . $year,
            ]);

            return [
                'status' => 'Success',
                'batch' => $batch,
                'credited_count' => $totalCredited,
                'total_days' => $totalDaysCredited,
            ];
        });
    }

    /**
     * Confirmation-based allocation when employee confirmation is processed.
     */
    public function allocateConfirmationLeaves(Employee $employee, ?User $actor = null): array
    {
        if (empty($employee->confirmation_date)) {
            return ['status' => 'Not Confirmed', 'allocated' => []];
        }

        $year = (int) now()->format('Y');
        $confirmationLeaveTypes = LeaveType::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('status', 'Active')
            ->where('requires_confirmation', true)
            ->get();

        $allocated = [];

        foreach ($confirmationLeaveTypes as $leaveType) {
            if (!$leaveType->isApplicableToEmployee($employee)) {
                continue;
            }

            $entitlement = (float) $leaveType->annual_entitlement;
            if ($entitlement > 0) {
                // Check if already allocated for this year
                $balance = $this->balanceService->getOrCreateBalance($employee, $leaveType, $year);
                if ((float) $balance->accrued_balance <= 0 && (float) $balance->opening_balance <= 0) {
                    $balance->accrued_balance = $entitlement;
                    $balance->recalculateBalances();
                    $balance->save();

                    \App\Models\LeaveBalanceTransaction::create([
                        'company_id' => $employee->company_id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'transaction_type' => 'Confirmation Allocation',
                        'transaction_date' => now()->toDateString(),
                        'credit_days' => $entitlement,
                        'debit_days' => 0.00,
                        'balance_before' => 0.00,
                        'balance_after' => $balance->closing_balance,
                        'reference_type' => 'Confirmation Allocation',
                        'reference_id' => $employee->id,
                        'transaction_uuid' => 'CONF-' . $employee->id . '-' . $leaveType->id . '-' . $year . '-' . Str::random(8),
                        'remarks' => 'Leave entitlement allocated on confirmation date: ' . $employee->confirmation_date->format('Y-m-d'),
                        'created_by' => $actor?->id,
                    ]);

                    $allocated[$leaveType->code] = $entitlement;
                }
            }
        }

        return ['status' => 'Success', 'allocated' => $allocated];
    }
}

