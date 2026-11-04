<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Auth;

class LicenseEntitlementService
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    /**
     * Get the active or latest subscription for a tenant.
     */
    public function getTenantSubscription(?Tenant $tenant = null): ?TenantSubscription
    {
        if (!$tenant) {
            $tenant = $this->companyContext->getActiveTenant();
        }

        if (!$tenant) {
            $user = Auth::guard('web')->user();
            $tenant = $user instanceof \App\Models\User ? $user->tenant() : null;
        }

        if (!$tenant) {
            return null;
        }

        return $tenant->activeSubscription() ?: $tenant->latestSubscription();
    }

    /**
     * Check if a tenant currently has a valid subscription (active, trial, or grace_period).
     */
    public function hasValidSubscription(?Tenant $tenant = null): bool
    {
        $subscription = $this->getTenantSubscription($tenant);
        return $subscription ? $subscription->isValid() : false;
    }

    /**
     * Check if a specific module/feature is enabled for the tenant.
     */
    public function hasFeature(string $featureCode, ?Tenant $tenant = null): bool
    {
        $subscription = $this->getTenantSubscription($tenant);
        if (!$subscription || !$subscription->isValid()) {
            return false;
        }

        return $subscription->plan->hasFeature($featureCode);
    }

    /**
     * Validate whether the tenant can add a new active employee within their plan quota.
     *
     * @return array{allowed: bool, current: int, limit: int, message: ?string}
     */
    public function canAddEmployee(?Tenant $tenant = null, ?int $companyId = null): array
    {
        if (!$tenant && $companyId) {
            $tenant = Company::find($companyId)?->tenant;
        }

        if (!$tenant) {
            $subscription = $this->getTenantSubscription();
            $tenant = $subscription?->tenant;
        } else {
            $subscription = $this->getTenantSubscription($tenant);
        }

        if (!$subscription || !$subscription->isValid()) {
            return [
                'allowed' => false,
                'current' => 0,
                'limit' => 0,
                'message' => 'Organization does not have an active subscription license.',
            ];
        }

        $limit = $subscription->plan->employee_limit;
        $companyIds = $tenant->companies()->pluck('id');

        $currentCount = Employee::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at')
            ->where('status', 'Active')
            ->count();

        if ($limit > 0 && $currentCount >= $limit) {
            return [
                'allowed' => false,
                'current' => $currentCount,
                'limit' => $limit,
                'message' => "Tenant employee quota limit reached ({$currentCount}/{$limit}). Please upgrade your subscription tier.",
            ];
        }

        return [
            'allowed' => true,
            'current' => $currentCount,
            'limit' => $limit,
            'message' => null,
        ];
    }

    /**
     * Validate whether the tenant can register a new legal company within their plan quota.
     *
     * @return array{allowed: bool, current: int, limit: int, message: ?string}
     */
    public function canAddCompany(?Tenant $tenant = null): array
    {
        if (!$tenant) {
            $subscription = $this->getTenantSubscription();
            $tenant = $subscription?->tenant;
        } else {
            $subscription = $this->getTenantSubscription($tenant);
        }

        if (!$subscription || !$subscription->isValid()) {
            return [
                'allowed' => false,
                'current' => 0,
                'limit' => 0,
                'message' => 'Organization does not have an active subscription license.',
            ];
        }

        $limit = $subscription->plan->company_limit;
        $currentCount = $tenant->companies()->count();

        if ($limit > 0 && $currentCount >= $limit) {
            return [
                'allowed' => false,
                'current' => $currentCount,
                'limit' => $limit,
                'message' => "Tenant company quota limit reached ({$currentCount}/{$limit}). Please upgrade your subscription tier.",
            ];
        }

        return [
            'allowed' => true,
            'current' => $currentCount,
            'limit' => $limit,
            'message' => null,
        ];
    }
}

