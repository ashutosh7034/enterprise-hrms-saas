<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\BiometricPunchLog;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\PlantShutdown;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceReconciliationService
{
    /**
     * Compute comprehensive compliance & reconciliation summary for a company on a given date.
     */
    public function getReconciliationSummary(int $companyId, string $date): array
    {
        $dateStr = Carbon::parse($date)->toDateString();

        $activeEmployeesCount = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->count();

        $records = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('attendance_date', $dateStr)
            ->get();

        $presentCount = $records->whereIn('status', ['Present', 'Half Day', 'Worked During Shutdown', 'Regularized', 'On Duty'])->count();
        $absentCount = $records->where('status', 'Absent')->count();
        $missingPunchCount = $records->where('status', 'Missing Punch')->count();
        $shutdownCount = $records->where('status', 'Site Shutdown')->count();
        $workedShutdownCount = $records->where('status', 'Worked During Shutdown')->count();
        $weeklyOffCount = $records->where('status', 'Weekly Off')->count();

        $duplicatePunchesCount = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('punch_time', $dateStr)
            ->where('is_duplicate', true)
            ->count();

        $unmappedPunchesCount = BiometricPunchLog::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDate('punch_time', $dateStr)
            ->where('processing_status', 'Error')
            ->count();

        $pendingRegularizationsCount = AttendanceRegularization::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('attendance_date', $dateStr)
            ->whereIn('status', ['Pending Manager', 'Pending HR'])
            ->count();

        $pendingOtCount = OvertimeRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('ot_date', $dateStr)
            ->where('status', 'Pending')
            ->count();

        // Discrepancy analysis
        $discrepancies = [];

        // 1. Expected but absent
        $absentEmployees = $records->where('status', 'Absent');
        foreach ($absentEmployees as $rec) {
            $discrepancies[] = [
                'type' => 'Expected Without Punch',
                'employee_id' => $rec->employee_id,
                'employee_code' => $rec->employee?->employee_code,
                'employee_name' => $rec->employee?->full_name,
                'severity' => 'Warning',
                'description' => 'Employee rostered but zero punches recorded.',
            ];
        }

        // 2. Missing punches (single punch)
        $missingPunches = $records->where('status', 'Missing Punch');
        foreach ($missingPunches as $rec) {
            $discrepancies[] = [
                'type' => 'Single Missing Punch',
                'employee_id' => $rec->employee_id,
                'employee_code' => $rec->employee?->employee_code,
                'employee_name' => $rec->employee?->full_name,
                'severity' => 'Danger',
                'description' => 'Only single IN or OUT punch captured. Requires regularization.',
            ];
        }

        // 3. Unmapped punches
        if ($unmappedPunchesCount > 0) {
            $discrepancies[] = [
                'type' => 'Biometric Device / Employee Unmapped',
                'severity' => 'Critical',
                'description' => "{$unmappedPunchesCount} punches captured with unknown employee identifiers.",
            ];
        }

        return [
            'date' => $dateStr,
            'expected_workforce' => $activeEmployeesCount,
            'worked_employees' => $presentCount,
            'absent_employees' => $absentCount,
            'missing_punch_count' => $missingPunchCount,
            'shutdown_employees' => $shutdownCount,
            'worked_during_shutdown' => $workedShutdownCount,
            'weekly_off_count' => $weeklyOffCount,
            'duplicate_punches_count' => $duplicatePunchesCount,
            'unmapped_punches_count' => $unmappedPunchesCount,
            'pending_regularizations_count' => $pendingRegularizationsCount,
            'pending_ot_count' => $pendingOtCount,
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * Mark an attendance record reconciled with notes.
     */
    public function reconcileRecord(AttendanceRecord $record, string $notes): AttendanceRecord
    {
        $record->update([
            'is_reconciled' => true,
            'reconciliation_notes' => trim($notes),
        ]);

        return $record;
    }
}

