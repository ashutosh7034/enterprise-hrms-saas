<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlatformAuthController extends Controller
{
    /**
     * Show the Platform Super Admin login interface.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('platform')->check()) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.auth.login');
    }

    /**
     * Authenticate the Platform Super Admin.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (Auth::guard('platform')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('platform')->user();

            if (!$user->isActive()) {
                Auth::guard('platform')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                RateLimiter::hit($throttleKey);

                return back()->withErrors([
                    'email' => 'Your platform administrator account is deactivated. Please contact systems support.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();
            $user->recordLogin($request->ip());

            return redirect()->intended(route('platform.dashboard'));
        }

        RateLimiter::hit($throttleKey);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Terminate the platform session and log out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login')->with('success', 'You have been safely signed out of the platform.');
    }

    /**
     * One-click fast demo login for SaaS Platform Super Admin.
     */
    public function fastLogin(Request $request): RedirectResponse
    {
        $admin = \App\Models\PlatformUser::where('email', 'superadmin@advaititsolutions.com')->where('status', 'active')->first()
            ?? \App\Models\PlatformUser::where('status', 'active')->first();

        if (!$admin) {
            return redirect()->route('platform.login')->withErrors([
                'email' => 'Active Platform Super Admin account not found.',
            ]);
        }

        Auth::guard('platform')->login($admin, true);
        $request->session()->regenerate();
        $admin->recordLogin($request->ip());

        return redirect()->route('platform.dashboard')
            ->with('success', 'Welcome, ' . $admin->name . '! Logged in via 1-Click Fast Login.');
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}

