<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Tests\TestCase;

class LeaveBalanceLedgerTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected LeaveType $leaveType;
    protected User $hrUser;
    protected LeaveBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->leaveType = LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->first()
            ?? LeaveType::where('company_id', $this->company->id)->first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->balanceService = app(LeaveBalanceService::class);

        $bal = LeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->leaveType->id)
            ->where('year', 2026)
            ->first();
        if ($bal) {
            $bal->update([
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
    }

    public function test_setting_opening_balance_creates_immutable_ledger_record(): void
    {
        $year = 2026;
        $openingDays = 14.50;

        $balance = $this->balanceService->setOpeningBalance(
            employee: $this->employee,
            leaveType: $this->leaveType,
            days: $openingDays,
            year: $year,
            actor: $this->hrUser,
            remarks: 'Audited opening balance setup'
        );

        $this->assertEquals($openingDays, (float) $balance->opening_balance);
        $this->assertEquals($openingDays, (float) $balance->available_balance);

        $this->assertDatabaseHas('leave_balance_transactions', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'transaction_type' => 'Opening Balance',
            'balance_after' => $balance->closing_balance,
        ]);
    }

    public function test_manual_adjustment_credits_and_debits_ledger(): void
    {
        $year = 2026;
        $initialBalance = $this->balanceService->getOrCreateBalance($this->employee, $this->leaveType, $year);
        $beforeClosing = (float) $initialBalance->closing_balance;

        // 1. Credit 3 days
        $this->balanceService->manualAdjustment(
            employee: $this->employee,
            leaveType: $this->leaveType,
            adjustmentType: 'Credit',
            days: 3.00,
            year: $year,
            reason: 'Overtime compensation adjustment',
            actor: $this->hrUser
        );

        $initialBalance->refresh();
        $this->assertEquals($beforeClosing + 3.00, (float) $initialBalance->closing_balance);

        // 2. Debit 1.5 days
        $this->balanceService->manualAdjustment(
            employee: $this->employee,
            leaveType: $this->leaveType,
            adjustmentType: 'Debit',
            days: 1.50,
            year: $year,
            reason: 'Correction of excess unavailed credit',
            actor: $this->hrUser
        );

        $initialBalance->refresh();
        $this->assertEquals($beforeClosing + 1.50, (float) $initialBalance->closing_balance);

        $this->assertDatabaseHas('leave_balance_transactions', [
            'employee_id' => $this->employee->id,
            'transaction_type' => 'Manual Adjustment',
            'credit_days' => 3.00,
        ]);

        $this->assertDatabaseHas('leave_balance_transactions', [
            'employee_id' => $this->employee->id,
            'transaction_type' => 'Manual Adjustment',
            'debit_days' => 1.50,
        ]);
    }

    public function test_balance_reservation_and_release_lifecycle(): void
    {
        $year = 2026;
        $balance = $this->balanceService->getOrCreateBalance($this->employee, $this->leaveType, $year);
        
        // Ensure at least 5 days available
        if ((float) $balance->available_balance < 5.0) {
            $this->balanceService->manualAdjustment($this->employee, $this->leaveType, 'Credit', 5.0, $year, 'Test seed', $this->hrUser);
            $balance->refresh();
        }

        $beforeAvail = (float) $balance->available_balance;
        $beforeReserved = (float) $balance->reserved_balance;

        // Reserve 2 days
        $success = $this->balanceService->reserveBalance($this->employee, $this->leaveType, 2.00, $year);
        $this->assertTrue($success);

        $balance->refresh();
        $this->assertEquals($beforeReserved + 2.00, (float) $balance->reserved_balance);
        $this->assertEquals($beforeAvail - 2.00, (float) $balance->available_balance);

        // Release 2 days (e.g. rejection or withdrawal)
        $this->balanceService->releaseReservedBalance($this->employee, $this->leaveType, 2.00, $year);
        $balance->refresh();

        $this->assertEquals($beforeReserved, (float) $balance->reserved_balance);
        $this->assertEquals($beforeAvail, (float) $balance->available_balance);
    }
}
