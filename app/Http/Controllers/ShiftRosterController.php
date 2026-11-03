<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\EmployeeShiftRoster;
use App\Models\Location;
use App\Models\Shift;
use App\Services\CompanyContextService;
use App\Services\ShiftManagementService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftRosterController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected ShiftManagementService $shiftService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $startDate = $request->input('start_date', now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', now()->endOfWeek()->toDateString());

        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $dt) {
            $dates[] = $dt->toDateString();
        }

        $query = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->with(['department', 'designation', 'location']);

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('employee_code', 'like', $term);
            });
        }

        $employees = $query->orderBy('first_name')->paginate(20);

        // Fetch rosters for these employees and dates
        $employeeIds = $employees->pluck('id')->toArray();
        $rosters = EmployeeShiftRoster::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('shift')
            ->get()
            ->groupBy(fn($r) => $r->employee_id . '_' . $r->date->format('Y-m-d'));

        $shifts = Shift::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get();

        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();

        return view('shifts.roster', compact(
            'employees',
            'dates',
            'startDate',
            'endDate',
            'rosters',
            'shifts',
            'locations',
            'departments'
        ));
    }

    public function assign(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'shift_id' => 'nullable|exists:shifts,id',
            'is_weekly_off' => 'nullable|boolean',
            'is_holiday' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $this->shiftService->assignRoster(
            companyId: $companyId,
            employeeId: $validated['employee_id'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            shiftId: $validated['shift_id'] ?? null,
            isWeeklyOff: !empty($validated['is_weekly_off']),
            isHoliday: !empty($validated['is_holiday']),
            notes: $validated['notes'] ?? null,
            assigner: Auth::user()
        );

        return back()->with('success', 'Shift roster assigned successfully.');
    }

    public function assignRecurring(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $this->shiftService->assignShiftToEmployee(
            companyId: $companyId,
            employeeId: $validated['employee_id'],
            shiftId: $validated['shift_id'],
            fromDate: $validated['from_date'],
            toDate: $validated['to_date'] ?? null,
            assigner: Auth::user()
        );

        return back()->with('success', 'Default recurring shift assigned to employee.');
    }
}

