<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PlantShutdown;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use App\Services\BiometricIntegrationService;
use App\Services\PlantShutdownService;
use Tests\TestCase;

class PlantShutdownManagementTest extends TestCase
{
    protected Company $company;
    protected Employee $employee;
    protected Location $location;
    protected User $hoAdmin;
    protected User $hrUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        $this->location = $this->employee->location ?? Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        if ($this->employee && $this->location) {
            $this->employee->update(['location_id' => $this->location->id]);
        }
        $this->hoAdmin = User::whereHas('role', fn($q) => $q->where('name', 'ho_admin'))->first() ?? User::first();
        $this->hrUser = User::whereHas('role', fn($q) => $q->where('name', 'hr_executive'))->first() ?? $this->hoAdmin;
    }

    public function test_can_request_and_approve_plant_shutdown(): void
    {
        $startDate = '2026-11-15';
        $endDate = '2026-11-16';
        $title = 'Emergency Turbine Pipeline Repair ' . mt_rand(1000, 9999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('plant-shutdowns.store'), [
                'location_id' => $this->location->id,
                'title' => $title,
                'shutdown_type' => 'Emergency',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'High-pressure steam leak detected in turbine section A',
                'compensation_rule' => 'Paid Shutdown',
            ]);

        $response->assertRedirect();

        $shutdown = PlantShutdown::withoutGlobalScopes()
            ->where('title', $title)
            ->first();

        $this->assertNotNull($shutdown);
        $this->assertEquals('Pending', $shutdown->status);

        // HO Admin Approves
        $approveResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('plant-shutdowns.approve', $shutdown->id));

        $approveResponse->assertRedirect();
        $shutdown->refresh();
        $this->assertEquals('Approved', $shutdown->status);

        // Verify affected employee attendance on shutdown date is automatically "Site Shutdown"
        $attService = app(AttendanceCalculationService::class);
        $attRecord = $attService->calculateDailyAttendance($this->employee, $startDate);

        $this->assertEquals('Site Shutdown', $attRecord->status);
    }

    public function test_employee_with_valid_punch_during_shutdown_marked_worked_during_shutdown(): void
    {
        $shutdownDate = '2026-11-18';
        $shutdownService = app(PlantShutdownService::class);

        $shutdown = $shutdownService->requestShutdown(
            companyId: $this->company->id,
            data: [
                'location_id' => $this->location->id,
                'title' => 'Plant Grid Power Cut',
                'shutdown_type' => 'Power Failure',
                'start_date' => $shutdownDate,
                'end_date' => $shutdownDate,
                'reason' => 'State electricity board sub-station scheduled line maintenance',
                'compensation_rule' => 'Paid Shutdown',
            ],
            requester: $this->hrUser
        );

        $shutdownService->approveShutdown($shutdown, $this->hoAdmin);

        // Maintenance crew employee punches during shutdown
        $bioService = app(BiometricIntegrationService::class);
        $bioService->ingestPunches($this->company->id, [
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$shutdownDate} 08:00:00", 'direction' => 'IN'],
            ['user_id' => $this->employee->employee_code, 'punch_time' => "{$shutdownDate} 16:30:00", 'direction' => 'OUT'],
        ], 'Test');

        $attService = app(AttendanceCalculationService::class);
        $record = $attService->calculateDailyAttendance($this->employee, $shutdownDate);

        $this->assertEquals('Worked During Shutdown', $record->status);
        $this->assertGreaterThan(0, $record->actual_worked_minutes);
    }

    public function test_shutdown_cancellation_reverses_affected_attendance_muster(): void
    {
        $cancelDate = '2026-11-20';
        $shutdownService = app(PlantShutdownService::class);

        $shutdown = $shutdownService->requestShutdown(
            companyId: $this->company->id,
            data: [
                'location_id' => $this->location->id,
                'title' => 'Proposed Boiler Overhaul',
                'shutdown_type' => 'Planned Maintenance',
                'start_date' => $cancelDate,
                'end_date' => $cancelDate,
                'reason' => 'Testing boiler inspection',
                'compensation_rule' => 'Paid Shutdown',
            ],
            requester: $this->hrUser
        );

        $shutdownService->approveShutdown($shutdown, $this->hoAdmin);

        $attService = app(AttendanceCalculationService::class);
        $recBefore = $attService->calculateDailyAttendance($this->employee, $cancelDate);
        $this->assertEquals('Site Shutdown', $recBefore->status);

        // Cancel shutdown with mandatory reason
        $shutdownService->cancelShutdown($shutdown, $this->hoAdmin, 'Inspection postponed by regulatory inspector');
        $shutdown->refresh();
        $this->assertEquals('Cancelled', $shutdown->status);

        // After cancellation, recalculation marks employee absent or normal instead of site shutdown
        $recAfter = $attService->calculateDailyAttendance($this->employee, $cancelDate);
        $this->assertNotEquals('Site Shutdown', $recAfter->status);
        $this->assertEquals('Absent', $recAfter->status); // No punches exist
    }
}
