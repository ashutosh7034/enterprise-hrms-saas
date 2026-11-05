<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Location;
use App\Models\OnboardingDropdownMaster;
use App\Services\CompanyContextService;
use App\Services\OnboardingMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DropdownMasterController extends Controller
{
    public function __construct(
        protected OnboardingMasterService $masterService,
        protected CompanyContextService $companyContext
    ) {}

    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        $isAuthorized = $user && (
            $user->isHoAdmin() ||
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (method_exists($user, 'isCompanyAdmin') && $user->isCompanyAdmin()) ||
            (method_exists($user, 'isBranchAdmin') && $user->isBranchAdmin())
        );

        if (!$isAuthorized) {
            abort(403, 'Unauthorized. Only authorized administrators can access master configurations.');
        }
    }

    /**
     * Display centralized master management dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $activeCompany = $this->companyContext->getActiveCompany();
        $companyId = $activeCompany?->id;
        $tenantId = $this->companyContext->getActiveTenantId();

        $allCategories = OnboardingDropdownMaster::CATEGORIES;
        $selectedCategory = $request->input('category', array_key_first($allCategories));
        if (!array_key_exists($selectedCategory, $allCategories)) {
            $selectedCategory = array_key_first($allCategories);
        }

        $query = OnboardingDropdownMaster::with(['company', 'location'])
            ->where('category', $selectedCategory)
            ->forScope($companyId, $tenantId);

        // Filter by Search Query
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by Status
        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $masterItems = $query->orderBy('display_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Calculate counts per category
        $categoryCounts = OnboardingDropdownMaster::forScope($companyId, $tenantId)
            ->selectRaw('category, count(*) as total, sum(case when is_active = 1 then 1 else 0 end) as active_count')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Potential parent options for dependent dropdowns
        $parentOptions = OnboardingDropdownMaster::active()
            ->forScope($companyId, $tenantId)
            ->where('category', '!=', $selectedCategory)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Active companies and branches strictly filtered by authenticated tenant/company context
        $companies = $this->companyContext->getAccessibleCompanies();
        $locations = $this->companyContext->getAccessibleLocations($companies);

        return view('admin.masters.index', compact(
            'allCategories',
            'selectedCategory',
            'masterItems',
            'categoryCounts',
            'parentOptions',
            'activeCompany',
            'companies',
            'locations',
            'status',
            'search'
        ));
    }

    /**
     * Store a newly created master item.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(OnboardingDropdownMaster::CATEGORIES))],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:onboarding_dropdown_masters,id'],
            'scope' => ['nullable', 'string', 'in:global,active_company,selected_company,selected_branch,company'],
            'company_id' => [
                'nullable',
                'exists:companies,id',
            ],
            'location_id' => [
                'nullable',
                'exists:locations,id',
            ],
        ]);

        $scope = $validated['scope'] ?? 'active_company';
        if ($scope === 'company') {
            $scope = 'active_company';
        }
        $companyId = null;
        $locationId = null;
        $user = Auth::user();

        if ($scope === 'global') {
            $isHoAdmin = $user && method_exists($user, 'isHoAdmin') && $user->isHoAdmin();
            $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
            if (!$isHoAdmin && !$isSuperAdmin) {
                abort(403, 'Unauthorized. Only HO Administrators and Super Admins can create Global Enterprise Standard master values.');
            }
            $companyId = null;
            $locationId = null;
        } elseif ($scope === 'active_company') {
            $companyId = $this->companyContext->getActiveCompanyId();
            if (!$companyId || !$this->companyContext->canAccessCompany($companyId)) {
                abort(403, 'Unauthorized company context.');
            }
            $locationId = null;
        } elseif ($scope === 'selected_company' || $scope === 'selected_branch') {
            $accessibleCompanies = $this->companyContext->getAccessibleCompanies();
            if (empty($validated['company_id']) && $accessibleCompanies->count() === 1) {
                $companyId = (int) $accessibleCompanies->first()->id;
            } else {
                $companyId = (int) ($validated['company_id'] ?? 0);
            }

            // CRITICAL TENANT SECURITY ENFORCEMENT:
            if (!$this->companyContext->canAccessCompany($companyId)) {
                throw ValidationException::withMessages([
                    'company_id' => 'Unauthorized company selection. You cannot create master values for companies outside your authorized tenant.',
                ]);
            }

            if ($scope === 'selected_branch') {
                $locationId = (int) ($validated['location_id'] ?? 0);
                if (!$this->companyContext->canAccessLocation($locationId, $companyId)) {
                    throw ValidationException::withMessages([
                        'location_id' => 'The selected branch is unauthorized or does not belong to the selected company.',
                    ]);
                }
            } else {
                $locationId = null;
            }
        }

        // Duplicate prevention within the same scope
        $duplicateQuery = OnboardingDropdownMaster::where('category', $validated['category'])
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($validated['name']))]);

        if (is_null($companyId)) {
            $duplicateQuery->whereNull('company_id');
        } else {
            $duplicateQuery->where('company_id', $companyId);
        }

        if (is_null($locationId)) {
            $duplicateQuery->whereNull('location_id');
        } else {
            $duplicateQuery->where('location_id', $locationId);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'name' => "A master value with the name '{$validated['name']}' already exists within the selected scope.",
            ]);
        }

        $tenantId = $this->companyContext->getActiveTenantId();

        $this->masterService->create([
            'category' => $validated['category'],
            'name' => $validated['name'],
            'code' => !empty($validated['code']) ? $validated['code'] : strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', $validated['name'])),
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'parent_id' => $validated['parent_id'] ?? null,
            'company_id' => $companyId,
            'location_id' => $locationId,
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);

        return redirect()->route('admin.masters.index', ['category' => $validated['category']])
            ->with('success', "Master value '{$validated['name']}' created successfully.");
    }

    /**
     * Update an existing master item.
     */
    public function update(Request $request, OnboardingDropdownMaster $master): RedirectResponse
    {
        $this->authorizeAdmin();

        $user = Auth::user();

        // Enforce tenant/company ownership on existing master record
        if ($master->company_id !== null && !$this->companyContext->canAccessCompany($master->company_id)) {
            abort(403, 'Unauthorized. You cannot modify master values belonging to another organization.');
        }

        if (is_null($master->company_id)) {
            $isHoAdmin = ($user && method_exists($user, 'isHoAdmin') && $user->isHoAdmin());
            $isSuperAdmin = ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
            if (!$isHoAdmin && !$isSuperAdmin) {
                abort(403, 'Global Enterprise Standard master values are read-only for company-level administrators.');
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:onboarding_dropdown_masters,id'],
            'is_active' => ['required', 'boolean'],
            'scope' => ['nullable', 'string', 'in:global,active_company,selected_company,selected_branch,company'],
            'company_id' => [
                'nullable',
                'exists:companies,id',
            ],
            'location_id' => [
                'nullable',
                'exists:locations,id',
            ],
        ]);

        $companyId = $master->company_id;
        $locationId = $master->location_id;

        if (!empty($validated['scope'])) {
            $scopeVal = $validated['scope'] === 'company' ? 'active_company' : $validated['scope'];
            if ($scopeVal === 'global') {
                $isHoAdmin = ($user && method_exists($user, 'isHoAdmin') && $user->isHoAdmin());
                $isSuperAdmin = ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
                if (!$isHoAdmin && !$isSuperAdmin) {
                    abort(403, 'Unauthorized. Only HO Administrators and Super Admins can set Global Enterprise Standard scope.');
                }
                $companyId = null;
                $locationId = null;
            } elseif ($scopeVal === 'active_company') {
                $companyId = $this->companyContext->getActiveCompanyId();
                if (!$companyId || !$this->companyContext->canAccessCompany($companyId)) {
                    abort(403, 'Unauthorized company context.');
                }
                $locationId = null;
            } elseif ($scopeVal === 'selected_company' || $scopeVal === 'selected_branch') {
                $accessibleCompanies = $this->companyContext->getAccessibleCompanies();
                if (empty($validated['company_id']) && $accessibleCompanies->count() === 1) {
                    $companyId = (int) $accessibleCompanies->first()->id;
                } else {
                    $companyId = (int) ($validated['company_id'] ?? 0);
                }

                if (!$this->companyContext->canAccessCompany($companyId)) {
                    throw ValidationException::withMessages([
                        'company_id' => 'Unauthorized company selection. You cannot scope master values to companies outside your authorized tenant.',
                    ]);
                }

                if ($scopeVal === 'selected_branch') {
                    $locationId = (int) ($validated['location_id'] ?? 0);
                    if (!$this->companyContext->canAccessLocation($locationId, $companyId)) {
                        throw ValidationException::withMessages([
                            'location_id' => 'The selected branch is unauthorized or does not belong to the selected company.',
                        ]);
                    }
                } else {
                    $locationId = null;
                }
            }
        }

        // Duplicate check within same scope (excluding self)
        $dup = OnboardingDropdownMaster::where('category', $master->category)
            ->where('id', '!=', $master->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($validated['name']))]);

        if (is_null($companyId)) {
            $dup->whereNull('company_id');
        } else {
            $dup->where('company_id', $companyId);
        }

        if (is_null($locationId)) {
            $dup->whereNull('location_id');
        } else {
            $dup->where('location_id', $locationId);
        }

        if ($dup->exists()) {
            throw ValidationException::withMessages([
                'name' => "A master value with the name '{$validated['name']}' already exists within the selected scope.",
            ]);
        }

        $updatePayload = array_merge($validated, [
            'company_id' => $companyId,
            'location_id' => $locationId,
        ]);

        $this->masterService->update($master, $updatePayload);

        return redirect()->route('admin.masters.index', ['category' => $master->category])
            ->with('success', "Master value '{$master->name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status of a master item.
     */
    public function toggleStatus(OnboardingDropdownMaster $master): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($master->company_id !== null && !$this->companyContext->canAccessCompany($master->company_id)) {
            abort(403, 'Unauthorized. You cannot modify master values belonging to another organization.');
        }

        $newStatus = $this->masterService->toggleStatus($master);
        $statusLabel = $newStatus ? 'activated' : 'deactivated';

        return redirect()->route('admin.masters.index', ['category' => $master->category])
            ->with('success', "Master value '{$master->name}' was {$statusLabel}.");
    }

    /**
     * API endpoint for dependent child options.
     */
    public function dependentOptions(Request $request): JsonResponse
    {
        $parentId = (int) $request->input('parent_id');
        $options = $this->masterService->getDependentOptions($parentId);

        return response()->json([
            'success' => true,
            'data' => $options->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ]),
        ]);
    }
}

