<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Location;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CompanyContextService
{
    protected const SESSION_KEY = 'active_company_id';

    public function getActiveCompanyId(): ?int
    {
        $sessionCompanyId = Session::get(self::SESSION_KEY);
        $authUser = Auth::guard('web')->user() ?: Auth::user();
        $user = ($authUser instanceof User) ? $authUser : null;

        if ($user) {
            // If user cannot switch companies, enforce their assigned company
            if (!$this->canUserSwitchCompany($user)) {
                return $user->company_id;
            }

            $userTenantId = $user->company?->tenant_id;

            // If user can switch, return session company if valid and belongs to the authorized tenant
            if ($sessionCompanyId && $userTenantId) {
                $sessionExists = Company::where('id', $sessionCompanyId)
                    ->where('status', 'Active')
                    ->where('tenant_id', $userTenantId)
                    ->exists();

                if ($sessionExists) {
                    return (int) $sessionCompanyId;
                }
            }

            // Fallback to assigned company or first active company in their tenant
            if ($user->company_id) {
                return $user->company_id;
            }

            return Company::where('status', 'Active')
                ->when($userTenantId, fn($q) => $q->where('tenant_id', $userTenantId))
                ->orderBy('id')
                ->value('id');
        }

        return $sessionCompanyId ? (int) $sessionCompanyId : null;
    }

    public function getActiveCompany(): ?Company
    {
        $id = $this->getActiveCompanyId();
        return $id ? Company::find($id) : null;
    }

    public function getActiveTenantId(): ?int
    {
        return $this->getActiveCompany()?->tenant_id;
    }

    public function getActiveTenant(): ?Tenant
    {
        return $this->getActiveCompany()?->tenant;
    }

    public function setActiveCompanyId(int $companyId): bool
    {
        $authUser = Auth::guard('web')->user() ?: Auth::user();
        $user = ($authUser instanceof User) ? $authUser : null;
        if ($user && !$this->canUserSwitchCompany($user) && $user->company_id !== $companyId) {
            return false;
        }

        $company = Company::where('id', $companyId)->where('status', 'Active')->first();
        if (!$company) {
            return false;
        }

        // Strict Tenant Boundary Check: User cannot switch to a company outside their tenant!
        if ($user) {
            $userTenantId = $user->company?->tenant_id;
            if (!$userTenantId || $company->tenant_id !== $userTenantId) {
                return false; // Cross-tenant or null-tenant switch blocked!
            }
        }

        $previousId = Session::get(self::SESSION_KEY);
        Session::put(self::SESSION_KEY, $companyId);

        if ($user) {
            app(AuditLogService::class)->log(
                action: 'COMPANY_SWITCH',
                companyId: $companyId,
                oldValues: ['company_id' => $previousId],
                newValues: ['company_id' => $companyId, 'company_name' => $company->name],
                customAuditableType: Company::class,
                customAuditableId: $companyId
            );
        }

        return true;
    }

    public function getAccessibleCompanies(): Collection
    {
        $authUser = Auth::guard('web')->user() ?: Auth::user();
        $user = ($authUser instanceof User) ? $authUser : null;
        if (!$user) {
            return Company::where('status', 'Active')->orderBy('name')->get();
        }

        // 1. Super Admin has unrestricted access across all active enterprise companies
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return Company::where('status', 'Active')->orderBy('name')->get();
        }

        // 2. Tenant Admin (HO Admin with Tenant context): strictly filtered to their tenant
        if ($this->canUserSwitchCompany($user)) {
            $userTenantId = $user->company?->tenant_id ?: $this->getActiveTenantId();
            if ($userTenantId) {
                return Company::where('tenant_id', $userTenantId)
                    ->where('status', 'Active')
                    ->orderBy('name')
                    ->get();
            }

            if ($user->company_id) {
                return Company::where('id', $user->company_id)->where('status', 'Active')->get();
            }
        }

        // 3. Company Admin / Branch Admin / Single-Company User: strictly their assigned company
        if ($user->company_id) {
            return Company::where('id', $user->company_id)->where('status', 'Active')->get();
        }

        return Company::where('status', 'Active')->whereRaw('1 = 0')->get();
    }

    public function getAccessibleLocations(?Collection $companies = null): Collection
    {
        $companies = $companies ?: $this->getAccessibleCompanies();
        $companyIds = $companies->pluck('id')->all();

        $authUser = Auth::guard('web')->user() ?: Auth::user();
        $user = ($authUser instanceof User) ? $authUser : null;

        $query = Location::whereIn('company_id', $companyIds)
            ->where('status', 'Active')
            ->orderBy('name');

        // 4. Branch Admin: strictly filtered to their assigned branch facility
        if ($user && method_exists($user, 'isBranchAdmin') && $user->isBranchAdmin()) {
            $empLocationId = $user->employee?->location_id;
            if ($empLocationId) {
                $query->where('id', $empLocationId);
            }
        }

        return $query->get();
    }

    public function canAccessCompany(int $companyId): bool
    {
        return $this->getAccessibleCompanies()->pluck('id')->contains($companyId);
    }

    public function canAccessLocation(int $locationId, ?int $companyId = null): bool
    {
        $query = Location::where('id', $locationId)->where('status', 'Active');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $loc = $query->first();
        if (!$loc) {
            return false;
        }

        $authUser = Auth::guard('web')->user() ?: Auth::user();
        $user = ($authUser instanceof User) ? $authUser : null;

        if ($user && method_exists($user, 'isBranchAdmin') && $user->isBranchAdmin()) {
            $empLocationId = $user->employee?->location_id;
            if ($empLocationId && $empLocationId !== $locationId) {
                return false;
            }
        }

        return $this->canAccessCompany($loc->company_id);
    }

    public function canUserSwitchCompany(mixed $user): bool
    {
        if (!$user || !($user instanceof User)) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Explicitly disallowed from switching
        if ($user->can_switch_company === false) {
            return false;
        }

        // Company Admin and Branch Admin roles are strictly isolated to a single company
        if ($user->hasRole(['company_admin', 'branch_admin', 'hr_executive', 'manager_mss', 'employee_ess', 'employee'])) {
            return false;
        }

        if ($user->can_switch_company) {
            return true;
        }

        $role = $user->role;
        return $role && in_array($role->name, ['ho_admin', 'super_admin']);
    }
}

