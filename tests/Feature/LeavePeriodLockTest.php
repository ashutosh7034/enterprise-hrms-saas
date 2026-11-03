<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeavePeriodLock;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveApplicationService;
use App\Services\LeavePeriodLockService;
use Tests\TestCase;

class LeavePeriodLockTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected LeaveType $clType;
    protected User $hrUser;
    protected User $hoAdminUser;
    protected LeavePeriodLockService $lockService;
    protected LeaveApplicationService $applicationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->clType = LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();
        $this->hoAdminUser = User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'super_admin'))->first();
        $this->lockService = app(LeavePeriodLockService::class);
        $this->applicationService = app(LeaveApplicationService::class);
    }

    public function test_locked_leave_period_blocks_new_leave_applications(): void
    {
        $year = 2026;
        $month = 3; // March 2026
        $dateInPeriod = '2026-03-15';

        // 1. Lock March 2026
        $lock = $this->lockService->lockPeriod(
            companyId: $this->company->id,
            year: $year,
            month: $month,
            locker: $this->hrUser,
            remarks: 'March payroll finalized'
        );

        $this->assertTrue($this->lockService->isPeriodLocked($this->company->id, $dateInPeriod));

        // 2. Attempt to apply leave in locked period
        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => $dateInPeriod,
                'end_date' => $dateInPeriod,
                'reason' => 'Should be blocked because period is locked',
            ],
            applicant: $this->hrUser
        );

        $this->assertEquals('Error', $result['status']);
        $this->assertStringContainsString('period is locked', $result['message']);
    }

    public function test_ho_admin_can_unlock_period_with_mandatory_justification(): void
    {
        $year = 2026;
        $month = 4;

        $lock = $this->lockService->lockPeriod($this->company->id, $year, $month, $this->hrUser);

        // Unlock with justification
        $justification = 'Head Office audit authorization for retroactive emergency medical leave correction.';
        $result = $this->lockService->unlockPeriod($lock, $this->hoAdminUser, $justification);

        $this->assertEquals('Success', $result['status']);
        $lock->refresh();
        $this->assertFalse($lock->is_locked);

        $this->assertDatabaseHas('leave_period_unlock_histories', [
            'leave_period_lock_id' => $lock->id,
            'unlocked_by' => $this->hoAdminUser->id,
            'justification' => $justification,
        ]);
    }
}

