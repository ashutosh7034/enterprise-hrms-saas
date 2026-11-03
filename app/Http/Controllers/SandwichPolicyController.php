<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\SandwichLeavePolicy;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class SandwichPolicyController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index()
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $policies = SandwichLeavePolicy::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['leaveType', 'location', 'department', 'category'])
            ->orderByDesc('priority')
            ->paginate(15);

        return view('leaves.sandwich.index', compact('policies'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $leaveTypes = LeaveType::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('leaves.sandwich.create', compact('leaveTypes', 'locations', 'departments', 'categories'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'policy_name' => 'required|string|max:100',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'is_enabled' => 'nullable|boolean',
            'include_intervening_weekly_offs' => 'nullable|boolean',
            'include_intervening_holidays' => 'nullable|boolean',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'priority' => 'required|integer|min:1|max:100',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_enabled'] = $request->boolean('is_enabled');
        $validated['include_intervening_weekly_offs'] = $request->boolean('include_intervening_weekly_offs');
        $validated['include_intervening_holidays'] = $request->boolean('include_intervening_holidays');

        SandwichLeavePolicy::create($validated);

        return redirect()->route('leaves.sandwich-policies.index')->with('success', 'Sandwich Leave Policy created successfully.');
    }

    public function edit(SandwichLeavePolicy $sandwichPolicy)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($sandwichPolicy->company_id !== $companyId) {
            abort(404);
        }

        $leaveTypes = LeaveType::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('leaves.sandwich.edit', compact('sandwichPolicy', 'leaveTypes', 'locations', 'departments', 'categories'));
    }

    public function update(Request $request, SandwichLeavePolicy $sandwichPolicy)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($sandwichPolicy->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'policy_name' => 'required|string|max:100',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'is_enabled' => 'nullable|boolean',
            'include_intervening_weekly_offs' => 'nullable|boolean',
            'include_intervening_holidays' => 'nullable|boolean',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'category_id' => 'nullable|exists:categories,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'priority' => 'required|integer|min:1|max:100',
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');
        $validated['include_intervening_weekly_offs'] = $request->boolean('include_intervening_weekly_offs');
        $validated['include_intervening_holidays'] = $request->boolean('include_intervening_holidays');

        $sandwichPolicy->update($validated);

        return redirect()->route('leaves.sandwich-policies.index')->with('success', 'Sandwich Leave Policy updated successfully.');
    }

    public function destroy(SandwichLeavePolicy $sandwichPolicy)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($sandwichPolicy->company_id !== $companyId) {
            abort(404);
        }

        $sandwichPolicy->delete();

        return redirect()->route('leaves.sandwich-policies.index')->with('success', 'Sandwich Leave Policy deleted successfully.');
    }
}

