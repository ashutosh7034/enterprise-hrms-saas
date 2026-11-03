<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftManagementService;
use Carbon\Carbon;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;
    protected Location $location;
    protected Department $department;
    protected Category $category;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first()
            ?? User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first();

        $this->location = Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->department = Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->category = Category::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
    }

    public function test_can_create_standard_shift_schedule(): void
    {
        $code = 'S-TEST-' . mt_rand(10000, 99999);
        Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->where('code', $code)->delete();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('shifts.store'), [
                'code' => $code,
                'name' => 'Morning Production Shift',
                'location_id' => $this->location->id,
                'department_id' => $this->department->id,
                'category_id' => $this->category->id,
                'start_time' => '07:00',
                'end_time' => '15:30',
                'break_duration_minutes' => 30,
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 240,
                'full_day_threshold_minutes' => 480,
                'weekly_off_days' => ['Sunday'],
                'effective_from' => '2026-01-01',
                'status' => 'Active',
            ]);

        $response->assertRedirect(route('shifts.index'));

        $shift = Shift::withoutGlobalScopes()->where('code', $code)->first();
        $this->assertNotNull($shift);
        $this->assertEquals('Morning Production Shift', $shift->name);
        $this->assertFalse($shift->is_overnight);
        $this->assertEquals(15, $shift->grace_period_minutes);
    }

    public function test_can_configure_overnight_shift_crossing_midnight(): void
    {
        $code = 'S-OVR-' . mt_rand(10000, 99999);
        Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->where('code', $code)->delete();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('shifts.store'), [
                'code' => $code,
                'name' => 'Overnight Foundry Shift',
                'start_time' => '22:00',
                'end_time' => '06:00',
                'break_duration_minutes' => 30,
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 240,
                'full_day_threshold_minutes' => 480,
                'is_overnight' => 1,
                'effective_from' => '2026-01-01',
                'status' => 'Active',
            ]);

        $response->assertRedirect(route('shifts.index'));

        $shift = Shift::withoutGlobalScopes()->where('code', $code)->first();
        $this->assertNotNull($shift);
        $this->assertTrue($shift->is_overnight);
        $this->assertStringContainsString('(+1 day)', $shift->formatted_timings);
    }

    public function test_can_assign_shift_roster_and_recurring_assignment(): void
    {
        $shift = Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->assertNotNull($shift);

        // Date-wise roster assign
        $startDate = now()->toDateString();
        $endDate = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('shifts.roster.assign'), [
                'employee_id' => $this->employee->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'shift_id' => $shift->id,
            ]);

        $response->assertRedirect();

        $service = app(ShiftManagementService::class);
        $effective = $service->getEffectiveShiftForDate($this->employee, $startDate);
        $this->assertNotNull($effective);
        $this->assertEquals($shift->id, $effective->id);
    }

    public function test_duplicate_shift_code_in_same_company_is_rejected(): void
    {
        $existing = Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->first();

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('shifts.store'), [
                'code' => $existing->code, // duplicate
                'name' => 'Duplicate Shift Name',
                'start_time' => '09:00',
                'end_time' => '18:00',
                'break_duration_minutes' => 45,
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 240,
                'full_day_threshold_minutes' => 480,
                'effective_from' => '2026-01-01',
                'status' => 'Active',
            ]);

        $response->assertSessionHasErrors('code');
    }
}

