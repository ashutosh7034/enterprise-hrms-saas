<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PlantShutdown;
use App\Models\ShutdownCrewNomination;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlantShutdownService
{
    public function __construct(
        protected ?AttendanceCalculationService $attendanceService = null
    ) {}

    public function setAttendanceService(AttendanceCalculationService $service): void
    {
        $this->attendanceService = $service;
    }

    /**
     * Create a new plant shutdown request.
     */
    public function requestShutdown(int $companyId, array $data, User $requester): PlantShutdown
    {
        $code = $this->generateShutdownCode($companyId);

        return PlantShutdown::create([
            'company_id' => $companyId,
            'location_id' => $data['location_id'],
            'department_id' => !empty($data['department_id']) ? $data['department_id'] : null,
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'shutdown_code' => $code,
            'title' => trim($data['title']),
            'shutdown_type' => $data['shutdown_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => !empty($data['start_time']) ? $data['start_time'] : null,
            'end_time' => !empty($data['end_time']) ? $data['end_time'] : null,
            'reason' => trim($data['reason']),
            'compensation_rule' => $data['compensation_rule'] ?? 'Paid Shutdown',
            'status' => 'Pending',
            'requested_by' => $requester->id,
        ]);
    }

    /**
     * Preview list of employees impacted by shutdown scope.
     */
    public function getAffectedEmployees(PlantShutdown $shutdown): Collection
    {
        $query = Employee::withoutGlobalScopes()
            ->where('company_id', $shutdown->company_id)
            ->where('location_id', $shutdown->location_id)
            ->where('status', 'Active');

        if ($shutdown->department_id) {
            $query->where('department_id', $shutdown->department_id);
        }

        if ($shutdown->category_id) {
            $query->where('category_id', $shutdown->category_id);
        }

        return $query->get();
    }

    /**
     * Approve shutdown and trigger attendance reprocessing for affected workforce.
     */
    public function approveShutdown(PlantShutdown $shutdown, User $approver): PlantShutdown
    {
        $shutdown->update([
            'status' => 'Approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Recalculate attendance for affected employees across the date range
        $this->reprocessShutdownAttendance($shutdown);

        \App\Events\PlantShutdownApprovedEvent::dispatch($shutdown);

        return $shutdown;
    }

    /**
     * Cancel an approved or pending shutdown with mandatory justification.
     */
    public function cancelShutdown(PlantShutdown $shutdown, User $canceller, string $reason): PlantShutdown
    {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException("Cancellation justification is mandatory.");
        }

        $shutdown->update([
            'status' => 'Cancelled',
            'cancellation_reason' => trim($reason),
            'cancelled_by' => $canceller->id,
            'cancelled_at' => now(),
        ]);

        // Revert attendance for affected employees
        $this->reprocessShutdownAttendance($shutdown);

        return $shutdown;
    }

    /**
     * Nominate working maintenance crew for shutdown date.
     */
    public function nominateCrew(
        PlantShutdown $shutdown,
        int $employeeId,
        string $workDate,
        ?string $role = null,
        string $compensationType = 'Overtime Pay',
        ?User $supervisor = null
    ): ShutdownCrewNomination {
        return ShutdownCrewNomination::updateOrCreate(
            [
                'plant_shutdown_id' => $shutdown->id,
                'employee_id' => $employeeId,
                'work_date' => $workDate,
            ],
            [
                'role_description' => $role,
                'compensation_type' => $compensationType,
                'is_approved' => true,
                'supervisor_id' => $supervisor?->id,
            ]
        );
    }

    /**
     * Recalculate attendance for all affected dates and employees.
     */
    protected function reprocessShutdownAttendance(PlantShutdown $shutdown): void
    {
        $service = $this->attendanceService ?? app(AttendanceCalculationService::class);
        $employees = $this->getAffectedEmployees($shutdown);
        $period = CarbonPeriod::create($shutdown->start_date, $shutdown->end_date);

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            foreach ($employees as $employee) {
                $service->calculateDailyAttendance($employee, $dateStr);
            }
        }
    }

    /**
     * Generate unique shutdown code e.g. SHT-2026-001.
     */
    protected function generateShutdownCode(int $companyId): string
    {
        $year = now()->year;
        $count = PlantShutdown::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf("SHT-%d-%03d", $year, $count);
    }
}

