<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveAccrualBatch;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveAccrualService;
use App\Services\LeaveCarryForwardService;
use Tests\TestCase;

class LeaveAccrualEngineTest extends TestCase
{
    protected Company $company;
    protected User $hrUser;
    protected LeaveAccrualService $accrualService;
    protected LeaveCarryForwardService $carryForwardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->accrualService = app(LeaveAccrualService::class);
        $this->carryForwardService = app(LeaveCarryForwardService::class);
    }

    public function test_monthly_accrual_execution_is_strictly_idempotent(): void
    {
        $year = 2026;
        $month = 7;

        // Clean up prior test runs
        LeaveAccrualBatch::where('company_id', $this->company->id)->where('year', $year)->where('month', $month)->delete();
        \App\Models\LeaveBalanceTransaction::where('transaction_uuid', 'like', "ACCR-{$this->company->id}-%-{$year}-" . sprintf('%02d', $month))->delete();

        // 1. First Run
        $result1 = $this->accrualService->processMonthlyAccrual(
            companyId: $this->company->id,
            year: $year,
            month: $month,
            leaveTypeId: null,
            actor: $this->hrUser
        );

        $this->assertEquals('Success', $result1['status']);
        $creditedFirst = $result1['credited_count'];
        $totalDaysFirst = $result1['total_days'];

        $this->assertDatabaseHas('leave_accrual_batches', [
            'company_id' => $this->company->id,
            'year' => $year,
            'month' => $month,
            'status' => 'Completed',
        ]);

        // 2. Second Run for same company, year, and month
        $result2 = $this->accrualService->processMonthlyAccrual(
            companyId: $this->company->id,
            year: $year,
            month: $month,
            leaveTypeId: null,
            actor: $this->hrUser
        );

        $this->assertEquals('Already Processed', $result2['status']);
        $this->assertEquals($creditedFirst, $result2['credited_count']);
        $this->assertEquals($totalDaysFirst, $result2['total_days']);
    }

    public function test_confirmation_based_allocation_only_allocates_when_confirmed(): void
    {
        $employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->assertNotNull($employee);

        // Case 1: Unconfirmed employee
        $employee->update(['confirmation_date' => null]);
        $unconfirmedResult = $this->accrualService->allocateConfirmationLeaves($employee, $this->hrUser);
        $this->assertEquals('Not Confirmed', $unconfirmedResult['status']);

        // Case 2: Confirmed employee
        $employee->update(['confirmation_date' => now()->subMonth()]);
        $confirmedResult = $this->accrualService->allocateConfirmationLeaves($employee, $this->hrUser);
        $this->assertEquals('Success', $confirmedResult['status']);
    }

    public function test_carry_forward_dry_run_mode_does_not_mutate_database(): void
    {
        $fromYear = 2025;
        $toYear = 2026;

        $result = $this->carryForwardService->processYearEnd(
            companyId: $this->company->id,
            fromYear: $fromYear,
            toYear: $toYear,
            leaveTypeId: null,
            dryRun: true,
            actor: $this->hrUser
        );

        $this->assertTrue($result['is_dry_run']);
        $this->assertEquals('Simulated', $result['batch']->status);

        // Verify that no real 'Carry Forward' or 'Expiry' ledger entries were written with this batch id
        $this->assertDatabaseMissing('leave_balance_transactions', [
            'reference_type' => 'Year-End Carry Forward',
            'reference_id' => $result['batch']->id,
        ]);
        $this->assertDatabaseMissing('leave_balance_transactions', [
            'reference_type' => 'Year-End Expiry',
            'reference_id' => $result['batch']->id,
        ]);
    }
}
