<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\CompanyContextService;
use App\Services\LicenseEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantSubscriptionProfileController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected LicenseEntitlementService $licenseService
    ) {}

    /**
     * Display the customer tenant's subscription and license profile.
     */
    public function show(Request $request): View
    {
        $tenant = $this->companyContext->getActiveTenant();
        if (!$tenant) {
            $user = Auth::user();
            $tenant = $user?->tenant();
        }

        abort_if(!$tenant, 403, 'Tenant context could not be determined.');

        $subscription = $tenant->latestSubscription();
        if ($subscription) {
            $subscription->load(['plan.features', 'invoices.payments']);
        }

        // Calculate current resource consumption
        $companyIds = $tenant->companies()->pluck('id');

        $activeEmployeesCount = Employee::withoutGlobalScopes()
            ->whereIn('company_id', $companyIds)
            ->whereNull('deleted_at')
            ->where('status', 'Active')
            ->count();

        $companiesCount = $tenant->companies()->count();

        $employeeLimit = $subscription?->plan?->employee_limit ?? 0;
        $companyLimit = $subscription?->plan?->company_limit ?? 0;

        $employeeUsagePercentage = ($employeeLimit > 0)
            ? min(100, round(($activeEmployeesCount / $employeeLimit) * 100, 1))
            : 0;

        $companyUsagePercentage = ($companyLimit > 0)
            ? min(100, round(($companiesCount / $companyLimit) * 100, 1))
            : 0;

        return view('subscription.show', compact(
            'tenant',
            'subscription',
            'activeEmployeesCount',
            'companiesCount',
            'employeeLimit',
            'companyLimit',
            'employeeUsagePercentage',
            'companyUsagePercentage'
        ));
    }
}

