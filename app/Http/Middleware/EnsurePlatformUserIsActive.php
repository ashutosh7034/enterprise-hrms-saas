<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('platform');

        if ($guard->check()) {
            $user = $guard->user();

            if (!$user || !$user->isActive()) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('platform.login')->withErrors([
                    'email' => 'Your platform administrator account is deactivated. Please contact systems support.',
                ]);
            }

            // Enforce mandatory password change if flagged on the platform user account
            if ($user->must_change_password) {
                $exemptRouteNames = [
                    'platform.password.change',
                    'platform.password.update',
                    'platform.logout',
                ];

                $currentRouteName = $request->route()?->getName();
                if (!in_array($currentRouteName, $exemptRouteNames)) {
                    return redirect()->route('platform.password.change')
                        ->with('warning', 'Mandatory Security Action: You must change your temporary password before accessing platform management.');
                }
            }
        }

        return $next($request);
    }
}

