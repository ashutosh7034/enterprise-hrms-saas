<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\FineMaster;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FineMasterController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $query = FineMaster::with('assetCategory')->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('fine_code', 'like', "%{$search}%")
                  ->orWhere('fine_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $fines = $query->latest('id')->paginate(15)->withQueryString();
        $categories = AssetCategory::orderBy('name')->get();

        return view('fines.index', compact('fines', 'categories'));
    }

    public function create()
    {
        $categories = AssetCategory::orderBy('name')->get();
        return view('fines.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'fine_code' => [
                'required', 'string', 'max:50',
                Rule::unique('fine_masters', 'fine_code')->where('company_id', $companyId),
            ],
            'fine_name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'in:Asset Damage,Asset Loss,Asset Non-Return,Other Policy Violation'],
            'asset_category_id' => ['nullable', 'exists:asset_categories,id'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'status' => ['required', 'in:Active,Inactive'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = $companyId;
        $fine = FineMaster::create($validated);

        $this->auditLogService->log(
            action: 'Fine Master Created',
            entityType: 'FineMaster',
            entityId: $fine->id,
            oldValues: [],
            newValues: $fine->only(['fine_code', 'fine_name', 'default_amount']),
            companyId: $companyId,
            actor: Auth::user()
        );

        return redirect()->route('fine-masters.index')
            ->with('success', "Fine policy '{$fine->fine_name}' ({$fine->fine_code}) created successfully.");
    }

    public function edit(FineMaster $fineMaster)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($fineMaster->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $categories = AssetCategory::orderBy('name')->get();
        return view('fines.edit', compact('fineMaster', 'categories'));
    }

    public function update(Request $request, FineMaster $fineMaster)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($fineMaster->company_id !== $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $validated = $request->validate([
            'fine_name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'in:Asset Damage,Asset Loss,Asset Non-Return,Other Policy Violation'],
            'asset_category_id' => ['nullable', 'exists:asset_categories,id'],
            'default_amount' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'status' => ['required', 'in:Active,Inactive'],
            'remarks' => ['nullable', 'string'],
        ]);

        $oldValues = $fineMaster->only(array_keys($validated));
        $fineMaster->update($validated);

        $this->auditLogService->log(
            action: 'Fine Master Updated',
            entityType: 'FineMaster',
            entityId: $fineMaster->id,
            oldValues: $oldValues,
            newValues: $validated,
            companyId: $companyId,
            actor: Auth::user()
        );

        return redirect()->route('fine-masters.index')
            ->with('success', "Fine policy '{$fineMaster->fine_name}' updated successfully.");
    }
}

