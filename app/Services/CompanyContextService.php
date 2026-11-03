<?php

namespace App\Services;

use App\Models\Company;
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
        $user = Auth::user();

        if ($user) {
            // If user cannot switch companies, enforce their assigned company
            if (!$this->canUserSwitchCompany($user)) {
                return $user->company_id;
            }

            // If user can switch, return session company if valid, otherwise fallback to user company
            if ($sessionCompanyId && Company::where('id', $sessionCompanyId)->where('status', 'Active')->exists()) {
                return (int) $sessionCompanyId;
            }

            return $user->company_id ?: Company::where('status', 'Active')->orderBy('id')->value('id');
        }

        return $sessionCompanyId ? (int) $sessionCompanyId : null;
    }

    public function getActiveCompany(): ?Company
    {
        $id = $this->getActiveCompanyId();
        return $id ? Company::find($id) : null;
    }

    public function setActiveCompanyId(int $companyId): bool
    {
        $user = Auth::user();
        if ($user && !$this->canUserSwitchCompany($user) && $user->company_id !== $companyId) {
            return false;
        }

        $company = Company::where('id', $companyId)->where('status', 'Active')->first();
        if (!$company) {
            return false;
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
        $user = Auth::user();
        if (!$user) {
            return Company::where('status', 'Active')->get();
        }

        if ($this->canUserSwitchCompany($user)) {
            return Company::where('status', 'Active')->orderBy('name')->get();
        }

        return Company::where('id', $user->company_id)->where('status', 'Active')->get();
    }

    public function canUserSwitchCompany(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->can_switch_company) {
            return true;
        }

        $role = $user->role;
        return $role && in_array($role->name, ['ho_admin', 'super_admin']);
    }
}

