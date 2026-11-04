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
        }

        return $next($request);
    }
}

