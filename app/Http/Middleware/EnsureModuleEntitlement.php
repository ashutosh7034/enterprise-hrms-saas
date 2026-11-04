<?php

namespace App\Http\Middleware;

use App\Services\LicenseEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEntitlement
{
    public function __construct(
        protected LicenseEntitlementService $entitlementService
    ) {}

    /**
     * Enforce server-side module/feature license entitlements for customer tenant.
     */
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        if (!$this->entitlementService->hasFeature($moduleCode)) {
            $readableName = ucwords(str_replace('_', ' ', $moduleCode));
            abort(403, "Access Denied: The {$readableName} module is not included in your organization's subscription plan. Please contact your platform administrator.");
        }

        return $next($request);
    }
}

