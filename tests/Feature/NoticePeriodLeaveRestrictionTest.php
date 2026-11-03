<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveApplicationService;
use App\Services\LeaveBalanceService;
use Tests\TestCase;

class NoticePeriodLeaveRestrictionTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected LeaveType $clType;
    protected User $hrUser;
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
        $this->applicationService = app(LeaveApplicationService::class);
        $this->balanceService = app(LeaveBalanceService::class);

        $this->balanceService->setOpeningBalance($this->employee, $this->clType, 10.00, 2026, $this->hrUser);
    }

    protected function tearDown(): void
    {
        $this->employee->update([
            'status' => 'Active',
            'notice_period_start_date' => null,
            'notice_period_end_date' => null,
        ]);
        parent::tearDown();
    }

    public function test_notice_period_employee_is_restricted_from_taking_disallowed_leaves(): void
    {
        // 1. Put employee in notice period
        $this->employee->update([
            'status' => 'Resigned',
            'notice_period_start_date' => now()->subDays(5),
            'notice_period_end_date' => now()->addDays(25),
        ]);

        $this->assertTrue($this->employee->isInNoticePeriod());

        // Ensure CL is restricted in notice period
        $this->clType->update(['is_applicable_in_notice_period' => false]);

        // Attempt application without HR override
        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'reason' => 'Leave during notice',
            ],
            applicant: $this->hrUser
        );

        $this->assertEquals('Notice Period Restricted', $result['status']);
    }

    public function test_hr_override_with_mandatory_justification_permits_leave(): void
    {
        $this->employee->update([
            'status' => 'Resigned',
            'notice_period_start_date' => now()->subDays(5),
            'notice_period_end_date' => now()->addDays(25),
        ]);

        $this->clType->update(['is_applicable_in_notice_period' => false]);

        // Apply with HR override and justification
        $result = $this->applicationService->applyLeave(
            employee: $this->employee,
            leaveType: $this->clType,
            data: [
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'reason' => 'Urgent medical requirement during notice',
                'is_notice_period_override' => true,
                'notice_period_justification' => 'Authorized by HR Director due to hospital admission of family member.',
            ],
            applicant: $this->hrUser
        );

        $this->assertEquals('Success', $result['status']);
        $this->assertTrue($result['application']->is_notice_period_override);
        $this->assertNotEmpty($result['application']->notice_period_justification);
    }
}
