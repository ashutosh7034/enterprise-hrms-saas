<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('platform*')) {
                return route('platform.login');
            }
            return route('login');
        });

        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('platform*') || \Illuminate\Support\Facades\Auth::guard('platform')->check()) {
                return route('platform.dashboard');
            }
            return route('dashboard');
        });

        $middleware->web(append: [
            \App\Http\Middleware\SetCompanyContext::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'platform.guest' => \App\Http\Middleware\RedirectIfPlatformAuthenticated::class,
            'platform.active' => \App\Http\Middleware\EnsurePlatformUserIsActive::class,
            'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
            'tenant.subscription' => \App\Http\Middleware\EnsureTenantSubscriptionIsValid::class,
            'entitled' => \App\Http\Middleware\EnsureModuleEntitlement::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
