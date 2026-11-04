<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BiometricDevice;
use App\Models\BiometricPunchLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\Shift;
use App\Services\AttendanceCalculationService;
use App\Services\BiometricIntegrationService;
use App\Services\ShiftManagementService;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceCalculationEngineTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected Shift $shift;
    protected BiometricDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->shift = Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->where('code', 'S-GEN')->first()
            ?? Shift::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->device = BiometricDevice::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
    }

    public function test_full_day_punches_generate_present_status_with_deviations(): void
    {
        $testDate = '2026-10-21'; // Wednesday (Working day)
        BiometricPunchLog::withoutGlobalScopes()
            ->where('employee_id', $this->employee->id)
            ->whereDate('punch_time', $testDate)
            ->delete();

        $bioService = app(BiometricIntegrationService::class);
        $attService = app(AttendanceCalculationService::class);

        // Employee arrives at 09:25 (10 mins after 15m grace window, shift start 09:00 -> 25 mins late)
        // Employee leaves at 18:30 (overtime)
        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 09:25:00", 'direction' => 'IN'],
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 18:30:00", 'direction' => 'OUT'],
        ], 'Test');

        $record = $attService->calculateDailyAttendance($this->employee, $testDate);

        $this->assertEquals('Present', $record->status);
        $this->assertEquals(25, $record->late_arrival_minutes);
        $this->assertEquals(0, $record->early_departure_minutes);
        $this->assertGreaterThanOrEqual(480, $record->actual_worked_minutes);
        $this->assertEquals(2, $record->total_punches);
    }

    public function test_half_day_punches_generate_half_day_status(): void
    {
        $testDate = '2026-10-26';
        $bioService = app(BiometricIntegrationService::class);
        $attService = app(AttendanceCalculationService::class);

        // Employee works from 09:00 to 14:00 (5 hours total - 45m break = 4h 15m = 255 mins >= 240 mins)
        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 09:00:00", 'direction' => 'IN'],
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 14:00:00", 'direction' => 'OUT'],
        ], 'Test');

        $record = $attService->calculateDailyAttendance($this->employee, $testDate);

        $this->assertEquals('Half Day', $record->status);
        $this->assertGreaterThanOrEqual(240, $record->actual_worked_minutes);
        $this->assertLessThan(480, $record->actual_worked_minutes);
    }

    public function test_single_punch_generates_missing_punch_status(): void
    {
        $testDate = '2026-10-27';
        $bioService = app(BiometricIntegrationService::class);
        $attService = app(AttendanceCalculationService::class);

        // Only Morning IN punch, forgot OUT punch
        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 08:58:00", 'direction' => 'IN'],
        ], 'Test');

        $record = $attService->calculateDailyAttendance($this->employee, $testDate);

        $this->assertEquals('Missing Punch', $record->status);
        $this->assertEquals(0, $record->actual_worked_minutes);
        $this->assertEquals(1, $record->total_punches);
    }

    public function test_overnight_shift_pairs_cross_calendar_day_punches(): void
    {
        $overnightShift = Shift::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('is_overnight', true)
            ->first();

        $this->assertNotNull($overnightShift);

        $shiftDate = '2026-10-28';
        $nextDay = '2026-10-29';

        // Assign roster to overnight shift for shiftDate
        $shiftService = app(ShiftManagementService::class);
        $shiftService->assignRoster($this->company->id, $this->employee->id, $shiftDate, $shiftDate, $overnightShift->id);

        $bioService = app(BiometricIntegrationService::class);
        $attService = app(AttendanceCalculationService::class);

        // Night Check-in at 21:55 on Day 1
        // Morning Check-out at 06:35 on Day 2
        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$shiftDate} 21:55:00", 'direction' => 'IN'],
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$nextDay} 06:35:00", 'direction' => 'OUT'],
        ], 'Test');

        $record = $attService->calculateDailyAttendance($this->employee, $shiftDate);

        $this->assertEquals('Present', $record->status);
        $this->assertTrue($record->is_overnight);
        $this->assertGreaterThanOrEqual(480, $record->actual_worked_minutes);
    }

    public function test_attendance_calculation_is_strictly_idempotent(): void
    {
        $testDate = '2026-10-29';
        $bioService = app(BiometricIntegrationService::class);
        $attService = app(AttendanceCalculationService::class);

        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 08:50:00", 'direction' => 'IN'],
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 18:00:00", 'direction' => 'OUT'],
        ], 'Test');

        // Run calculation 1st time
        $record1 = $attService->calculateDailyAttendance($this->employee, $testDate);

        // Run calculation 2nd time
        $record2 = $attService->calculateDailyAttendance($this->employee, $testDate);

        // Must be exact same database row without duplicate rows
        $this->assertEquals($record1->id, $record2->id);
        $this->assertEquals($record1->actual_worked_minutes, $record2->actual_worked_minutes);
        $this->assertEquals($record1->status, $record2->status);

        $count = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('employee_id', $this->employee->id)
            ->where('attendance_date', $testDate)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_overtime_calculation_applies_configurable_rounding(): void
    {
        $testDate = '2026-10-14'; // Wednesday
        $bioService = app(BiometricIntegrationService::class);
        $attService = app(AttendanceCalculationService::class);

        // Shift is 09:00 to 18:00 (full day = 480 mins after 45m break)
        // Employee works from 08:00 to 19:40
        // Total span: 700 mins. Break: 45 mins. Actual worked: 655 mins.
        // Raw OT = 655 - 480 = 175 mins.
        // 15-min block rounding: intdiv(175, 15) * 15 = 11 * 15 = 165 mins.
        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 08:00:00", 'direction' => 'IN'],
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$testDate} 19:40:00", 'direction' => 'OUT'],
        ], 'Test');

        $record = $attService->calculateDailyAttendance($this->employee, $testDate);

        $ot = OvertimeRecord::withoutGlobalScopes()
            ->where('attendance_record_id', $record->id)
            ->first();

        $this->assertNotNull($ot);
        $this->assertEquals(175, $ot->raw_calculated_minutes);
        $this->assertEquals(165, $ot->rounded_ot_minutes);
        $this->assertEquals('Pending', $ot->status);
    }
}
