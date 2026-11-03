<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveApplicationService;
use App\Services\LeaveBalanceService;
use Tests\TestCase;

class LeaveApplicationWorkflowTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected LeaveType $clType;
    protected User $hrUser;
    protected User $managerUser;
    protected LeaveApplicationService $applicationService;
    protected LeaveBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->clType = LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->managerUser = User::whereHas('role', fn($q) => $q->where('name', 'manager_mss'))->first()
            ?? $this->hrUser;
        $this->applicationService = app(LeaveApplicationService::class);
        $this->balanceService = app(LeaveBalanceService::class);

        // Ensure employee is Active and not in notice period
        $this->employee->update([
            'status' => 'Active',
            'notice_period_start_date' => null,
            'notice_period_end_date' => null,
        ]);

        // Clean up any pending applications from previous test runs
        LeaveApplication::where('employee_id', $this->employee->id)->delete();
        $bal = $this->balanceService->getOrCreateBalance($this->employee, $this->clType, 2026);
        $bal->update(['reserved_balance' => 0.00, 'consumed_balance' => 0.00]);
        $bal->recalculateBalances()->save();

        // Ensure employee has active balance
        $this->balanceService->setOpeningBalance($this->employee, $this->clType, 15.00, 2026, $this->hrUser);
    }

    public function test_can_apply_leave_and_balance_is_reserved(): void
    {
        $startDate = '2026-11-10'; // Tuesday
        $endDate = '2026-11-11';   // Wednesday

        $balanceBefore = $this->balanceService->getOrCreateBalance($this->employee, $this->clType, 2026);
        $availBefore = (float) $balanceBefore->available_balance;

        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'Family festival celebration',
            ],
            applicant: $this->hrUser
        );

        $this->assertEquals('Success', $result['status']);
        $app = $result['application'];

        $this->assertNotNull($app->application_number);
        $this->assertEquals(2.00, (float) $app->calculated_days);

        $balanceAfter = $this->balanceService->getOrCreateBalance($this->employee, $this->clType, 2026);
        $this->assertEquals($availBefore - 2.00, (float) $balanceAfter->available_balance);
        $this->assertEquals(2.00, (float) $balanceAfter->reserved_balance);
    }

    public function test_insufficient_balance_rejects_application(): void
    {
        // Zero out balance
        $bal = $this->balanceService->getOrCreateBalance($this->employee, $this->clType, 2026);
        $bal->update([
            'opening_balance' => 0.00,
            'accrued_balance' => 0.00,
            'carry_forward_balance' => 0.00,
            'adjustment_credit' => 0.00,
            'consumed_balance' => 0.00,
            'reserved_balance' => 0.00,
            'available_balance' => 0.00,
            'closing_balance' => 0.00,
        ]);
        $this->clType->update(['allow_negative_balance' => false]);

        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => '2026-11-12',
                'end_date' => '2026-11-13',
                'reason' => 'Should be rejected for insufficient quota',
            ],
            applicant: $this->hrUser
        );

        $this->assertEquals('Insufficient Balance', $result['status']);
    }

    public function test_full_approval_lifecycle_from_manager_to_hr_consumes_balance(): void
    {
        // Re-credit quota
        $this->balanceService->setOpeningBalance($this->employee, $this->clType, 10.00, 2026, $this->hrUser);

        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => '2026-11-17',
                'end_date' => '2026-11-18',
                'reason' => 'Two days casual leave',
            ],
            applicant: $this->hrUser
        );

        $app = $result['application'];

        // Manager Review
        if ($app->status === 'Pending Manager') {
            $mResult = $this->applicationService->managerAction($app, 'Approve', $this->managerUser, 'Approved by team lead');
            $this->assertEquals('Success', $mResult['status']);
            $app->refresh();
            $this->assertEquals('Pending HR', $app->status);
        }

        // HR Final Sanction
        $hrResult = $this->applicationService->hrAction($app, 'Approve', $this->hrUser, 'Final sanction granted');
        $this->assertEquals('Success', $hrResult['status']);

        $app->refresh();
        $this->assertEquals('Approved', $app->status);
        $this->assertEquals(2.00, (float) $app->approved_days);

        // Check consumed in ledger
        $this->assertDatabaseHas('leave_balance_transactions', [
            'reference_id' => $app->id,
            'transaction_type' => 'Leave Application',
            'debit_days' => 2.00,
        ]);
    }

    public function test_employee_withdrawal_releases_reserved_balance(): void
    {
        $this->balanceService->setOpeningBalance($this->employee, $this->clType, 10.00, 2026, $this->hrUser);

        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => '2026-11-24',
                'end_date' => '2026-11-25',
                'reason' => 'To be withdrawn',
            ],
            applicant: $this->hrUser
        );

        $app = $result['application'];
        $wResult = $this->applicationService->withdrawApplication($app, $this->hrUser);
        $this->assertEquals('Success', $wResult['status']);

        $app->refresh();
        $this->assertEquals('Withdrawn', $app->status);

        $balance = $this->balanceService->getOrCreateBalance($this->employee, $this->clType, 2026);
        $this->assertEquals(0.00, (float) $balance->reserved_balance);
    }
}
