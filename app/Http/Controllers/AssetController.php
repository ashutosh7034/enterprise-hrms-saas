<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\AssetCategory;
use App\Models\Employee;
use App\Models\Location;
use App\Services\AssetManagementService;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AssetController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AssetManagementService $assetService,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = Asset::with(['category', 'location', 'activeAllocation.employee'])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('asset_category_id')) {
            $query->where('asset_category_id', $request->input('asset_category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        $assets = $query->latest('id')->paginate(15)->withQueryString();

        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();
        $employees = Employee::where('company_id', $companyId)->where('status', 'Active')->orderBy('first_name')->get();

        $totalCount = Asset::where('company_id', $companyId)->count();
        $allocatedCount = Asset::where('company_id', $companyId)->where('status', 'Allocated')->count();
        $availableCount = Asset::where('company_id', $companyId)->where('status', 'Available')->count();
        $damagedCount = Asset::where('company_id', $companyId)->whereIn('status', ['Damaged', 'Lost'])->count();

        return view('assets.index', compact(
            'assets', 'categories', 'locations', 'employees',
            'totalCount', 'allocatedCount', 'availableCount', 'damagedCount'
        ));
    }

    public function create()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('assets.create', compact('categories', 'locations'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'asset_code' => ['required', 'string', 'max:100', 'unique:assets,asset_code'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'string', 'max:100'],
            'is_returnable' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = $companyId;
        $validated['status'] = 'Available';
        $validated['is_returnable'] = $request->has('is_returnable') ? (bool) $request->input('is_returnable') : true;

        $asset = Asset::create($validated);

        $this->auditLogService->log(
            action: 'Asset Registered',
            entityType: 'Asset',
            entityId: $asset->id,
            oldValues: [],
            newValues: $asset->only(['asset_code', 'name', 'purchase_cost', 'status']),
            companyId: $companyId,
            actor: Auth::user()
        );

        return redirect()->route('assets.index')
            ->with('success', "Asset {$asset->asset_code} registered successfully.");
    }

    public function show(Asset $asset)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($asset->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $asset->load([
            'category',
            'location',
            'allocations.employee',
            'allocations.allocatedByUser',
            'allocations.receivedByUser',
            'allocations.damageAssessments.recoveryRecords',
        ]);

        $depreciation = $this->assetService->calculateDepreciation(
            (float) $asset->purchase_cost,
            $asset->purchase_date?->toDateString(),
            'Straight-Line'
        );

        return view('assets.show', compact('asset', 'depreciation'));
    }

    public function edit(Asset $asset)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($asset->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->get();

        return view('assets.edit', compact('asset', 'categories', 'locations'));
    }

    public function update(Request $request, Asset $asset)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($asset->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('company_id', $companyId)],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'string', 'max:100'],
            'is_returnable' => ['nullable', 'boolean'],
            'status' => ['required', 'in:Available,Allocated,Returned,Damaged,Lost,Under Repair,Retired'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['is_returnable'] = $request->has('is_returnable') ? (bool) $request->input('is_returnable') : false;

        $oldValues = $asset->only(array_keys($validated));
        $asset->update($validated);

        $this->auditLogService->log(
            action: 'Asset Updated',
            entityType: 'Asset',
            entityId: $asset->id,
            oldValues: $oldValues,
            newValues: $validated,
            companyId: $companyId,
            actor: Auth::user()
        );

        return redirect()->route('assets.show', $asset->id)
            ->with('success', "Asset {$asset->asset_code} updated successfully.");
    }

    public function allocate(Request $request, Asset $asset)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($asset->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'allocated_date' => ['required', 'date'],
            'condition_at_issue' => ['required', 'string', 'max:100'],
            'acknowledged_by_employee' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        try {
            $this->assetService->allocateAsset($asset, $employee, $validated, Auth::user());

            return redirect()->route('assets.show', $asset->id)
                ->with('success', "Asset {$asset->asset_code} allocated to {$employee->employee_code} ({$employee->full_name}).");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['allocation' => $e->getMessage()]);
        }
    }

    public function processReturn(Request $request, AssetAllocation $allocation)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($allocation->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'condition_at_return' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        try {
            $this->assetService->returnAsset($allocation, $validated, Auth::user());

            return redirect()->route('assets.show', $allocation->asset_id)
                ->with('success', "Asset {$allocation->asset->asset_code} returned. Recorded condition: {$validated['condition_at_return']}.");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }
}

