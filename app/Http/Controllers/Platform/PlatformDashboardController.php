<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformUser;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    /**
     * Render the Platform Super Admin Dashboard.
     */
    public function index(): View
    {
        $platformAdmin = Auth::guard('platform')->user();

        // Platform-level high level metrics (strictly isolated from HRMS operational details)
        $metrics = [
            'total_platform_admins' => PlatformUser::count(),
            'active_platform_admins' => PlatformUser::where('status', 'active')->count(),
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_companies' => Company::count(),
            'total_plans' => SubscriptionPlan::count(),
            'active_plans' => SubscriptionPlan::where('status', 'active')->count(),
            'total_subscriptions' => TenantSubscription::count(),
            'active_subscriptions' => TenantSubscription::where('status', 'active')->count(),
            'grace_subscriptions' => TenantSubscription::where('status', 'grace_period')->count(),
            'expired_subscriptions' => TenantSubscription::where('status', 'expired')->count(),
            'total_invoices' => SubscriptionInvoice::count(),
            'paid_invoices' => SubscriptionInvoice::where('status', 'paid')->count(),
            'pending_invoices' => SubscriptionInvoice::where('status', 'pending')->count(),
            'total_revenue' => SubscriptionInvoice::where('status', 'paid')->sum('amount'),
            'database_connection' => DB::connection()->getDatabaseName(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
        ];

        $recentSubscriptions = TenantSubscription::with(['tenant', 'plan'])
            ->latest('id')
            ->take(5)
            ->get();

        $recentInvoices = SubscriptionInvoice::with(['tenant', 'subscription'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('platform.dashboard', compact('platformAdmin', 'metrics', 'recentSubscriptions', 'recentInvoices'));
    }
}
