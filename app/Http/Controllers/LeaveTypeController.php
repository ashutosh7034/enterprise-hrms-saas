<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\Location;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = LeaveType::withoutGlobalScopes()
            ->where('company_id', $companyId);

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

        $leaveTypes = $query->orderBy('name')->paginate(15);

        return view('leaves.types.index', compact('leaveTypes'));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('leaves.types.create', compact('locations', 'departments', 'categories'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('leave_types', 'code')->where('company_id', $companyId),
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_paid' => 'nullable|boolean',
            'annual_entitlement' => 'required|numeric|min:0|max:365',
            'monthly_accrual_rate' => 'required|numeric|min:0|max:31',
            'accrual_frequency' => 'required|in:None,Monthly,Quarterly,Yearly',
            'accrual_timing' => 'required|in:Start of Month,End of Month',
            'is_accrual_enabled' => 'nullable|boolean',
            'requires_confirmation' => 'nullable|boolean',
            'min_service_days' => 'nullable|integer|min:0',
            'max_consecutive_days' => 'nullable|integer|min:0',
            'min_notice_days' => 'nullable|integer|min:0',
            'max_backdated_days' => 'nullable|integer|min:0',
            'is_carry_forward_enabled' => 'nullable|boolean',
            'carry_forward_limit' => 'nullable|numeric|min:0',
            'expiry_months' => 'nullable|integer|min:0',
            'allow_negative_balance' => 'nullable|boolean',
            'max_negative_balance' => 'nullable|numeric|min:0',
            'is_sandwich_enabled' => 'nullable|boolean',
            'is_compoff' => 'nullable|boolean',
            'is_applicable_in_notice_period' => 'nullable|boolean',
            'max_notice_period_days' => 'nullable|numeric|min:0',
            'applicable_categories' => 'nullable|array',
            'applicable_locations' => 'nullable|array',
            'applicable_departments' => 'nullable|array',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['is_accrual_enabled'] = $request->boolean('is_accrual_enabled');
        $validated['requires_confirmation'] = $request->boolean('requires_confirmation');
        $validated['is_carry_forward_enabled'] = $request->boolean('is_carry_forward_enabled');
        $validated['allow_negative_balance'] = $request->boolean('allow_negative_balance');
        $validated['is_sandwich_enabled'] = $request->boolean('is_sandwich_enabled');
        $validated['is_compoff'] = $request->boolean('is_compoff');
        $validated['is_applicable_in_notice_period'] = $request->boolean('is_applicable_in_notice_period');

        LeaveType::create($validated);

        return redirect()->route('leaves.types.index')->with('success', 'Leave Type created successfully.');
    }

    public function edit(LeaveType $type)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($type->company_id !== $companyId) {
            abort(404);
        }

        $locations = Location::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $departments = Department::withoutGlobalScopes()->where('company_id', $companyId)->get();
        $categories = Category::all();

        return view('leaves.types.edit', compact('type', 'locations', 'departments', 'categories'));
    }

    public function update(Request $request, LeaveType $type)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($type->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('leave_types', 'code')->where('company_id', $companyId)->ignore($type->id),
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_paid' => 'nullable|boolean',
            'annual_entitlement' => 'required|numeric|min:0|max:365',
            'monthly_accrual_rate' => 'required|numeric|min:0|max:31',
            'accrual_frequency' => 'required|in:None,Monthly,Quarterly,Yearly',
            'accrual_timing' => 'required|in:Start of Month,End of Month',
            'is_accrual_enabled' => 'nullable|boolean',
            'requires_confirmation' => 'nullable|boolean',
            'min_service_days' => 'nullable|integer|min:0',
            'max_consecutive_days' => 'nullable|integer|min:0',
            'min_notice_days' => 'nullable|integer|min:0',
            'max_backdated_days' => 'nullable|integer|min:0',
            'is_carry_forward_enabled' => 'nullable|boolean',
            'carry_forward_limit' => 'nullable|numeric|min:0',
            'expiry_months' => 'nullable|integer|min:0',
            'allow_negative_balance' => 'nullable|boolean',
            'max_negative_balance' => 'nullable|numeric|min:0',
            'is_sandwich_enabled' => 'nullable|boolean',
            'is_compoff' => 'nullable|boolean',
            'is_applicable_in_notice_period' => 'nullable|boolean',
            'max_notice_period_days' => 'nullable|numeric|min:0',
            'applicable_categories' => 'nullable|array',
            'applicable_locations' => 'nullable|array',
            'applicable_departments' => 'nullable|array',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['is_paid'] = $request->boolean('is_paid');
        $validated['is_accrual_enabled'] = $request->boolean('is_accrual_enabled');
        $validated['requires_confirmation'] = $request->boolean('requires_confirmation');
        $validated['is_carry_forward_enabled'] = $request->boolean('is_carry_forward_enabled');
        $validated['allow_negative_balance'] = $request->boolean('allow_negative_balance');
        $validated['is_sandwich_enabled'] = $request->boolean('is_sandwich_enabled');
        $validated['is_compoff'] = $request->boolean('is_compoff');
        $validated['is_applicable_in_notice_period'] = $request->boolean('is_applicable_in_notice_period');

        $type->update($validated);

        return redirect()->route('leaves.types.index')->with('success', 'Leave Type updated successfully.');
    }
}

