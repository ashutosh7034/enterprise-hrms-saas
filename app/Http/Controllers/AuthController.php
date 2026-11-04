<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AuditLogService;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is deactivated. Please contact your administrator.',
                ]);
            }

            $request->session()->regenerate();

            // Set active company context
            $companyContext = app(CompanyContextService::class);
            if ($user->company_id) {
                $companyContext->setActiveCompanyId($user->company_id);
            } else {
                $firstCompany = Company::where('status', 'Active')->first();
                if ($firstCompany) {
                    $companyContext->setActiveCompanyId($firstCompany->id);
                }
            }

            app(AuditLogService::class)->log(
                action: 'LOGIN',
                model: $user,
                oldValues: null,
                newValues: ['email' => $user->email]
            );

            return $this->redirectBasedOnRole($user);
        }

        // Seamless check for SaaS Platform Super Admin credentials entered on the tenant login form
        $platformUser = \App\Models\PlatformUser::where('email', $credentials['email'])->first();
        if ($platformUser && \Illuminate\Support\Facades\Hash::check($credentials['password'], $platformUser->password)) {
            if (!$platformUser->isActive()) {
                return back()->withErrors([
                    'email' => 'Your platform administrator account is deactivated. Please contact systems support.',
                ])->onlyInput('email');
            }

            Auth::guard('platform')->login($platformUser, $request->boolean('remember'));
            $request->session()->regenerate();
            $platformUser->recordLogin($request->ip());

            return redirect()->route('platform.dashboard')
                ->with('success', 'Authenticated as SaaS Platform Super Admin.');
        }

        if ($platformUser) {
            return redirect()->route('platform.login')->withErrors([
                'email' => 'Invalid password for Platform Super Admin. Please sign in via the Platform Portal.',
            ])->withInput(['email' => $credentials['email']]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            app(AuditLogService::class)->log(
                action: 'LOGOUT',
                model: $user,
                oldValues: null,
                newValues: ['email' => $user->email]
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been safely logged out.');
    }

    protected function redirectBasedOnRole($user): RedirectResponse
    {
        if ($user->isEmployee()) {
            return redirect()->route('dashboard.employee');
        }

        if ($user->isManager()) {
            return redirect()->route('dashboard.manager');
        }

        if ($user->isHrExecutive()) {
            return redirect()->route('dashboard.hr');
        }

        return redirect()->route('dashboard.admin');
    }
}

