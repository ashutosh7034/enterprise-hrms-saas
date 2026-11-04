<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Ensure the authenticated HRMS user's customer tenant is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');

        if ($guard->check()) {
            $user = $guard->user();

            if ($user instanceof User) {
                $tenant = $user->tenant();

                if (!$tenant || !$tenant->isActive()) {
                    $status = $tenant ? $tenant->status : 'unassigned';
                    $guard->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => "Your organization tenant is {$status}. Please contact Advait IT Solutions support.",
                    ]);
                }
            }
        }

        return $next($request);
    }
}

