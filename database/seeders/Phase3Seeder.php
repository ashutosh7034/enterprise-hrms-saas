<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\BiometricDevice;
use App\Models\BiometricPunchLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\OvertimeRecord;
use App\Models\PlantShutdown;
use App\Models\Shift;
use App\Models\ShutdownCrewNomination;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\BiometricIntegrationService;
use App\Services\ShiftManagementService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class Phase3Seeder extends Seeder
{
    public function run(): void
    {
        $ve = Company::where('code', 'VE')->first() ?? Company::first();
        $vre = Company::where('code', 'VRE')->first() ?? Company::skip(1)->first();

        if (!$ve) return;

        $hoAdmin = User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first() ?? User::first();
        $hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first() ?? $hoAdmin;

        $veLocation = Location::withoutGlobalScopes()->where('company_id', $ve->id)->first();
        $veDept = Department::withoutGlobalScopes()->where('company_id', $ve->id)->first();

        // 1. Seed Shifts for Vishal Enterprise
        $generalShift = Shift::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $ve->id, 'code' => 'S-GEN'],
            [
                'name' => 'General Plant Shift',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_duration_minutes' => 45,
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 240,
                'full_day_threshold_minutes' => 480,
                'is_overnight' => false,
                'weekly_off_days' => ['Sunday'],
                'status' => 'Active',
                'effective_from' => '2026-01-01',
            ]
        );

        $morningShift = Shift::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $ve->id, 'code' => 'S-MORN'],
            [
                'name' => 'Morning Plant Shift A',
                'start_time' => '06:00:00',
                'end_time' => '14:30:00',
                'break_duration_minutes' => 30,
                'grace_period_minutes' => 10,
                'half_day_threshold_minutes' => 240,
                'full_day_threshold_minutes' => 480,
                'is_overnight' => false,
                'weekly_off_days' => ['Sunday'],
                'status' => 'Active',
                'effective_from' => '2026-01-01',
            ]
        );

        $nightOvernightShift = Shift::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $ve->id, 'code' => 'S-NGHT'],
            [
                'name' => 'Night Overnight Shift C',
                'start_time' => '22:00:00',
                'end_time' => '06:30:00',
                'break_duration_minutes' => 30,
                'grace_period_minutes' => 15,
                'half_day_threshold_minutes' => 240,
                'full_day_threshold_minutes' => 480,
                'is_overnight' => true,
                'weekly_off_days' => ['Sunday'],
                'status' => 'Active',
                'effective_from' => '2026-01-01',
            ]
        );

        // 2. Seed Biometric Devices
        $device1 = BiometricDevice::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $ve->id, 'device_identifier' => 'PN-P1-BIO-01'],
            [
                'location_id' => $veLocation?->id,
                'device_name' => 'Pune Plant Main Turnstile Check-in',
                'serial_number' => 'ZK-BIO-99201',
                'ip_address' => '192.168.1.101',
                'port' => 4370,
                'device_type' => 'Biometric Fingerprint',
                'protocol' => 'REST Webhook',
                'status' => 'Active',
                'health_status' => 'Healthy',
                'secret_token' => 've-secret-turnstile-token-01',
                'last_sync_at' => now(),
                'last_heartbeat_at' => now(),
                'remarks' => 'Primary factory entrance turnstile terminal',
            ]
        );

        // 3. Assign Active Employees to Shifts
        $shiftService = app(ShiftManagementService::class);
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $ve->id)
            ->where('status', 'Active')
            ->get();

        foreach ($employees as $emp) {
            $shiftService->assignShiftToEmployee(
                companyId: $ve->id,
                employeeId: $emp->id,
                shiftId: $generalShift->id,
                fromDate: '2026-01-01',
                assigner: $hrUser
            );
        }

        // 4. Ingest Sample Punches for Today
        $biometricService = app(BiometricIntegrationService::class);
        $attendanceService = app(AttendanceCalculationService::class);
        $today = now()->toDateString();

        if ($employees->count() >= 2) {
            $emp1 = $employees[0];
            $emp2 = $employees[1];

            // Emp 1: Normal full day (08:55 IN, 18:05 OUT)
            $biometricService->ingestPunches($ve->id, [
                ['user_id' => $emp1->employee_code, 'punch_time' => "{$today} 08:55:00", 'direction' => 'IN'],
                ['user_id' => $emp1->employee_code, 'punch_time' => "{$today} 18:05:00", 'direction' => 'OUT'],
            ], 'REST Webhook', $device1);

            // Emp 2: Single punch (Missing OUT)
            $biometricService->ingestPunches($ve->id, [
                ['user_id' => $emp2->employee_code, 'punch_time' => "{$today} 09:10:00", 'direction' => 'IN'],
            ], 'REST Webhook', $device1);

            // Calculate attendance for today
            $attendanceService->calculateDailyAttendance($emp1, $today);
            $attendanceService->calculateDailyAttendance($emp2, $today);
        }

        // 5. Seed Plant Shutdown
        $shutdown = PlantShutdown::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $ve->id, 'shutdown_code' => 'SHT-2026-001'],
            [
                'location_id' => $veLocation?->id,
                'department_id' => null, // Whole site
                'category_id' => null,
                'title' => 'Scheduled Substation Overhaul & Refractory Maintenance',
                'shutdown_type' => 'Planned Maintenance',
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(6)->toDateString(),
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'reason' => 'Annual mandatory inspection of high-voltage transformers and furnace lining repair.',
                'compensation_rule' => 'Paid Shutdown',
                'status' => 'Approved',
                'requested_by' => $hrUser->id,
                'approved_by' => $hoAdmin->id,
                'approved_at' => now(),
            ]
        );

        // Nominate maintenance crew
        if ($employees->isNotEmpty()) {
            ShutdownCrewNomination::updateOrCreate(
                [
                    'plant_shutdown_id' => $shutdown->id,
                    'employee_id' => $employees->first()->id,
                    'work_date' => now()->addDays(5)->toDateString(),
                ],
                [
                    'role_description' => 'Electrical substation monitoring engineer',
                    'compensation_type' => 'Overtime Pay',
                    'is_approved' => true,
                    'supervisor_id' => $hrUser->id,
                ]
            );
        }
    }
}

