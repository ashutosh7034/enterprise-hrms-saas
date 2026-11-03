<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Location;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $year = (int) ($request->year ?? now()->format('Y'));

        $query = Holiday::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->with(['location', 'department', 'category']);

        if ($request->filled('type')) {
            $query->where('holiday_type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $holidays = $query->orderBy('date')->paginate(20);

        return view('leaves.holidays.index', compact('holidays', 'year'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('leaves.holidays.create', compact('locations', 'departments', 'categories'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'date' => 'required|date',
            'holiday_type' => 'required|in:National Holiday,Regional Holiday,Festival Holiday,Company Holiday,Optional Holiday,Site-Specific Holiday,Shutdown Holiday',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'is_ground_staff_applicable' => 'nullable|boolean',
            'is_paid' => 'nullable|boolean',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['company_id'] = $companyId;
        $validated['year'] = (int) date('Y', strtotime($validated['date']));
        $validated['is_ground_staff_applicable'] = $request->boolean('is_ground_staff_applicable');
        $validated['is_paid'] = $request->boolean('is_paid');

        Holiday::create($validated);

        return redirect()->route('leaves.holidays.index', ['year' => $validated['year']])
            ->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($holiday->company_id !== $companyId) {
            abort(404);
        }

        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('leaves.holidays.edit', compact('holiday', 'locations', 'departments', 'categories'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($holiday->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'date' => 'required|date',
            'holiday_type' => 'required|in:National Holiday,Regional Holiday,Festival Holiday,Company Holiday,Optional Holiday,Site-Specific Holiday,Shutdown Holiday',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'is_ground_staff_applicable' => 'nullable|boolean',
            'is_paid' => 'nullable|boolean',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['year'] = (int) date('Y', strtotime($validated['date']));
        $validated['is_ground_staff_applicable'] = $request->boolean('is_ground_staff_applicable');
        $validated['is_paid'] = $request->boolean('is_paid');

        $holiday->update($validated);

        return redirect()->route('leaves.holidays.index', ['year' => $validated['year']])
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($holiday->company_id !== $companyId) {
            abort(404);
        }

        $year = $holiday->year;
        $holiday->delete();

        return redirect()->route('leaves.holidays.index', ['year' => $year])
            ->with('success', 'Holiday deleted successfully.');
    }
}

