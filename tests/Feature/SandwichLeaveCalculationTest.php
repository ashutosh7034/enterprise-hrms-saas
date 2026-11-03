<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\SandwichLeavePolicy;
use App\Services\SandwichLeaveCalculationService;
use Carbon\Carbon;
use Tests\TestCase;

class SandwichLeaveCalculationTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected LeaveType $clType;
    protected SandwichLeaveCalculationService $sandwichService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->clType = LeaveType::where('company_id', $this->company->id)->where('code', 'CL')->first();
        $this->sandwichService = app(SandwichLeaveCalculationService::class);

        // Ensure shift has Saturday and Sunday as weekly offs
        \App\Models\Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->update([
            'weekly_off_days' => ['Saturday', 'Sunday'],
        ]);
        $assignment = \App\Models\EmployeeShiftAssignment::withoutGlobalScopes()
            ->where('employee_id', $this->employee->id)
            ->with('shift')
            ->first();
        if ($assignment && $assignment->shift) {
            $assignment->shift->update(['weekly_off_days' => ['Saturday', 'Sunday']]);
        }
    }

    public function test_friday_and_monday_leave_with_sandwich_policy_enabled(): void
    {
        // 2026-10-16 is Friday, 2026-10-19 is Monday. Sat (17) and Sun (18) are intervening weekend
        $friday = '2026-10-16';
        $monday = '2026-10-19';

        // Ensure policy is enabled with weekly-offs
        SandwichLeavePolicy::updateOrCreate(
            ['company_id' => $this->company->id, 'policy_name' => 'Default Company Sandwich Policy'],
            [
                'is_enabled' => true,
                'include_intervening_weekly_offs' => true,
                'include_intervening_holidays' => true,
                'priority' => 10,
                'effective_from' => '2026-01-01',
            ]
        );

        $this->clType->update(['is_sandwich_enabled' => true]);

        $calc = $this->sandwichService->calculateLeaveDays(
            employee: $this->employee,
            leaveType: $this->clType,
            startDate: $friday,
            endDate: $monday
        );

        $this->assertEquals(2.00, (float) $calc['requested_days']); // Fri, Mon
        $this->assertEquals(4.00, (float) $calc['total_payable_deduction']); // Fri, Sat, Sun, Mon
        $this->assertCount(4, $calc['breakdown']);
    }

    public function test_friday_and_monday_leave_with_sandwich_policy_disabled(): void
    {
        $friday = '2026-10-16';
        $monday = '2026-10-19';

        // Disable sandwich policy
        SandwichLeavePolicy::updateOrCreate(
            ['company_id' => $this->company->id, 'policy_name' => 'Default Company Sandwich Policy'],
            [
                'is_enabled' => false,
                'include_intervening_weekly_offs' => false,
                'include_intervening_holidays' => false,
                'priority' => 10,
                'effective_from' => '2026-01-01',
            ]
        );

        $calc = $this->sandwichService->calculateLeaveDays(
            employee: $this->employee,
            leaveType: $this->clType,
            startDate: $friday,
            endDate: $monday
        );

        $this->assertEquals(2.00, (float) $calc['requested_days']);
        $this->assertEquals(2.00, (float) $calc['total_payable_deduction']); // Weekend excluded!
        $this->assertFalse($calc['sandwich_applied']);
    }

    public function test_intervening_holiday_is_included_when_holiday_sandwich_is_enabled(): void
    {
        // 2026-10-01 (Thu) to 2026-10-03 (Sat). 2026-10-02 (Fri) is Gandhi Jayanti (Holiday)
        $start = '2026-10-01';
        $end = '2026-10-03';

        // Seed Gandhi Jayanti holiday
        Holiday::updateOrCreate(
            ['company_id' => $this->company->id, 'date' => '2026-10-02'],
            ['name' => 'Gandhi Jayanti', 'holiday_type' => 'National Holiday', 'year' => 2026, 'status' => 'Active']
        );

        SandwichLeavePolicy::updateOrCreate(
            ['company_id' => $this->company->id, 'policy_name' => 'Default Company Sandwich Policy'],
            [
                'is_enabled' => true,
                'include_intervening_weekly_offs' => false,
                'include_intervening_holidays' => true,
                'priority' => 10,
                'effective_from' => '2026-01-01',
            ]
        );

        $calc = $this->sandwichService->calculateLeaveDays(
            employee: $this->employee,
            leaveType: $this->clType,
            startDate: $start,
            endDate: $end
        );

        $this->assertGreaterThanOrEqual(1, $calc['intervening_holidays']);
        $this->assertTrue($calc['sandwich_applied']);
    }

    public function test_half_day_leave_calculation(): void
    {
        $date = '2026-10-14';

        $calc = $this->sandwichService->calculateLeaveDays(
            employee: $this->employee,
            leaveType: $this->clType,
            startDate: $date,
            endDate: $date,
            isHalfDay: true
        );

        $this->assertEquals(0.5, (float) $calc['requested_days']);
        $this->assertEquals(0.5, (float) $calc['total_payable_deduction']);
        $this->assertFalse($calc['sandwich_applied']);
    }
}
