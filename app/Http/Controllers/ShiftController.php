<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Shift;
use App\Services\CompanyContextService;
use App\Services\ShiftManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected ShiftManagementService $shiftService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        
        $query = Shift::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['location', 'department', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        $shifts = $query->orderBy('name')->paginate(15);

        return view('shifts.index', compact('shifts'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('shifts.create', compact('locations', 'departments', 'categories'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('shifts', 'code')->where('company_id', $companyId),
            ],
            'name' => 'required|string|max:100',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration_minutes' => 'required|integer|min:0|max:180',
            'grace_period_minutes' => 'required|integer|min:0|max:120',
            'half_day_threshold_minutes' => 'required|integer|min:60|max:480',
            'full_day_threshold_minutes' => 'required|integer|min:120|max:960',
            'is_overnight' => 'nullable|boolean',
            'weekly_off_days' => 'nullable|array',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:Active,Inactive',
        ]);

        $this->shiftService->saveShift($companyId, $validated);

        return redirect()->route('shifts.index')->with('success', 'Shift schedule created successfully.');
    }

    public function edit(Shift $shift)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($shift->company_id !== $companyId) {
            abort(404);
        }

        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('shifts.edit', compact('shift', 'locations', 'departments', 'categories'));
    }

    public function update(Request $request, Shift $shift)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($shift->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('shifts', 'code')->where('company_id', $companyId)->ignore($shift->id),
            ],
            'name' => 'required|string|max:100',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration_minutes' => 'required|integer|min:0|max:180',
            'grace_period_minutes' => 'required|integer|min:0|max:120',
            'half_day_threshold_minutes' => 'required|integer|min:60|max:480',
            'full_day_threshold_minutes' => 'required|integer|min:120|max:960',
            'is_overnight' => 'nullable|boolean',
            'weekly_off_days' => 'nullable|array',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:Active,Inactive',
        ]);

        $this->shiftService->saveShift($companyId, $validated, $shift);

        return redirect()->route('shifts.index')->with('success', 'Shift schedule updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($shift->company_id !== $companyId) {
            abort(404);
        }

        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deactivated successfully.');
    }
}

