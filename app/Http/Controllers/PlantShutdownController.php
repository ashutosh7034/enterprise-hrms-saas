<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PlantShutdown;
use App\Services\CompanyContextService;
use App\Services\PlantShutdownService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlantShutdownController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected PlantShutdownService $shutdownService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = PlantShutdown::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['location', 'department', 'category', 'requester', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $shutdowns = $query->orderByDesc('start_date')->paginate(15);

        return view('shutdowns.index', compact('shutdowns'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('shutdowns.create', compact('locations', 'departments', 'categories'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'title' => 'required|string|max:191',
            'shutdown_type' => 'required|in:Planned Maintenance,Emergency,Power Failure,Raw Material Shortage,Other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'required|string',
            'compensation_rule' => 'required|in:Paid Shutdown,Adjust Against Leave,Unpaid Shutdown,Comp-Off for Duty',
        ]);

        $shutdown = $this->shutdownService->requestShutdown(
            companyId: $companyId,
            data: $validated,
            requester: Auth::user()
        );

        return redirect()->route('plant-shutdowns.show', $shutdown->id)->with('success', 'Plant shutdown request submitted successfully.');
    }

    public function show(PlantShutdown $plantShutdown)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($plantShutdown->company_id !== $companyId) {
            abort(404);
        }

        $plantShutdown->load(['location', 'department', 'category', 'requester', 'approver', 'crewNominations.employee']);
        $affectedEmployees = $this->shutdownService->getAffectedEmployees($plantShutdown);

        $siteEmployees = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('location_id', $plantShutdown->location_id)
            ->where('status', 'Active')
            ->get();

        return view('shutdowns.show', compact('plantShutdown', 'affectedEmployees', 'siteEmployees'));
    }

    public function approve(PlantShutdown $plantShutdown)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($plantShutdown->company_id !== $companyId) {
            abort(404);
        }

        $this->shutdownService->approveShutdown($plantShutdown, Auth::user());

        return back()->with('success', 'Plant shutdown approved. Daily attendance muster updated.');
    }

    public function cancel(Request $request, PlantShutdown $plantShutdown)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($plantShutdown->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $this->shutdownService->cancelShutdown($plantShutdown, Auth::user(), $validated['cancellation_reason']);

        return back()->with('success', 'Shutdown cancelled. Attendance flags reversed.');
    }

    public function nominateCrew(Request $request, PlantShutdown $plantShutdown)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($plantShutdown->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'work_date' => 'required|date|after_or_equal:' . $plantShutdown->start_date->format('Y-m-d') . '|before_or_equal:' . $plantShutdown->end_date->format('Y-m-d'),
            'role_description' => 'nullable|string|max:191',
            'compensation_type' => 'required|in:Overtime Pay,Comp-Off Credit,Special Site Allowance',
        ]);

        $this->shutdownService->nominateCrew(
            shutdown: $plantShutdown,
            employeeId: $validated['employee_id'],
            workDate: $validated['work_date'],
            role: $validated['role_description'] ?? null,
            compensationType: $validated['compensation_type'],
            supervisor: Auth::user()
        );

        return back()->with('success', 'Maintenance crew member nominated successfully.');
    }
}

