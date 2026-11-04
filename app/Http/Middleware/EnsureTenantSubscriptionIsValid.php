<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\LicenseEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionIsValid
{
    public function __construct(
        protected LicenseEntitlementService $entitlementService
    ) {}

    /**
     * Ensure the authenticated HRMS user's customer tenant has a valid subscription.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');

        if ($guard->check()) {
            $user = $guard->user();

            if ($user instanceof User) {
                // Allow logout without subscription check
                if ($request->routeIs('logout')) {
                    return $next($request);
                }

                $tenant = $user->tenant();
                if (!$tenant) {
                    return $next($request); // Defer to EnsureTenantIsActive
                }

                $subscription = $this->entitlementService->getTenantSubscription($tenant);

                if (!$subscription || !$subscription->isValid()) {
                    $status = $subscription ? $subscription->status : 'missing';
                    $guard->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => "Your organization subscription license is {$status}. Please contact Advait IT Solutions billing support.",
                    ]);
                }

                // If in grace period, flash an informative warning banner
                if ($subscription->isGracePeriod()) {
                    $endsFormatted = $subscription->ends_at ? $subscription->ends_at->format('d M Y') : 'recently';
                    $graceFormatted = $subscription->grace_period_ends_at ? $subscription->grace_period_ends_at->format('d M Y') : 'soon';
                    session()->flash('subscription_grace_warning', "Notice: Your organization subscription license expired on {$endsFormatted} and is operating in a grace period until {$graceFormatted}. Please contact Advait IT Solutions to renew.");
                }
            }
        }

        return $next($request);
    }
}
