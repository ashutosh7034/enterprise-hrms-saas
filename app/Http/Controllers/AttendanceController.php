<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Services\AttendanceCalculationService;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AttendanceCalculationService $attendanceService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $date = $request->input('date', now()->toDateString());

        $query = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('attendance_date', $date)
            ->with(['employee.department', 'employee.designation', 'employee.location', 'shift', 'regularization']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('location_id')) {
            $query->whereHas('employee', fn($eq) => $eq->where('location_id', $request->location_id));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($eq) => $eq->where('department_id', $request->department_id));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->whereHas('employee', function ($eq) use ($term) {
                $eq->where('first_name', 'like', $term)
                   ->orWhere('last_name', 'like', $term)
                   ->orWhere('employee_code', 'like', $term);
            });
        }

        $user = auth()->user();
        if ($user && $user->isManager() && !$user->isHr()) {
            $managerEmp = $user->employee();
            if ($managerEmp) {
                $query->whereHas('employee', fn($eq) => $eq->where('reporting_manager_id', $managerEmp->id));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $records = $query->paginate(20);

        // Status counts for cards
        $statusCounts = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('attendance_date', $date)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();

        return view('attendance.index', compact('records', 'date', 'statusCounts', 'locations', 'departments'));
    }

    /**
     * Trigger muster recalculation for active company on date.
     */
    public function recalculate(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $date = $request->input('date', now()->toDateString());

        $count = $this->attendanceService->calculateCompanyAttendance($companyId, $date);

        return back()->with('success', "Attendance muster recalculated successfully for {$count} employees on {$date}.");
    }

    /**
     * View punch details for an individual attendance record.
     */
    public function show(AttendanceRecord $attendance)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($attendance->company_id !== $companyId) {
            abort(404);
        }

        $attendance->load(['employee.department', 'employee.designation', 'shift', 'regularization', 'overtimeRecord']);

        $user = auth()->user();
        if ($user && $user->isManager() && !$user->isHr()) {
            $managerEmp = $user->employee();
            if (!$managerEmp || $attendance->employee?->reporting_manager_id !== $managerEmp->id) {
                abort(403, 'Unauthorized. You can only view attendance records for your assigned team members.');
            }
        }

        return view('attendance.show', compact('attendance'));
    }
}

