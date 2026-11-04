<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display a listing of all tenant subscriptions.
     */
    public function index(Request $request): View
    {
        $query = TenantSubscription::with(['tenant', 'plan'])
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subscription_code', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('tenant_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            if (in_array($status, ['active', 'trial', 'grace_period', 'expired', 'suspended', 'cancelled', 'pending'])) {
                $query->where('status', $status);
            }
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('subscription_plan_id', $planId);
        }

        $subscriptions = $query->paginate(15)->withQueryString();
        $plans = SubscriptionPlan::orderBy('name')->get();

        $counts = [
            'total' => TenantSubscription::count(),
            'active' => TenantSubscription::where('status', 'active')->count(),
            'grace_period' => TenantSubscription::where('status', 'grace_period')->count(),
            'expired' => TenantSubscription::where('status', 'expired')->count(),
            'suspended' => TenantSubscription::where('status', 'suspended')->count(),
        ];

        return view('platform.subscriptions.index', compact('subscriptions', 'plans', 'counts'));
    }

    /**
     * Show the form to assign a subscription plan to a tenant.
     */
    public function create(Tenant $tenant): View
    {
        $plans = SubscriptionPlan::where('status', 'active')->with('features')->get();
        $currentSubscription = $tenant->latestSubscription();

        return view('platform.subscriptions.assign', compact('tenant', 'plans', 'currentSubscription'));
    }

    /**
     * Store (assign) a new subscription plan to a tenant.
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'status' => ['required', 'in:active,trial,pending'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'renewal_type' => ['required', 'in:manual,automatic'],
            'is_paid' => ['nullable', 'boolean'],
            'payment_provider' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

        $subscription = $this->subscriptionService->assignPlan($tenant, $plan, [
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
            'grace_days' => $validated['grace_days'] ?? 14,
            'status' => $validated['status'],
            'renewal_type' => $validated['renewal_type'],
            'is_paid' => !empty($validated['is_paid']),
            'payment_provider' => $validated['payment_provider'] ?? 'manual_bank_transfer',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('platform.subscriptions.show', $subscription)
            ->with('success', "Subscription plan '{$plan->name}' successfully assigned to {$tenant->name}.");
    }

    /**
     * Show subscription details.
     */
    public function show(TenantSubscription $subscription): View
    {
        $subscription->load(['tenant.companies', 'plan.features', 'invoices.payments']);

        $auditLogs = PlatformAuditLog::where('tenant_id', $subscription->tenant_id)
            ->where(function ($q) use ($subscription) {
                $q->where('action', 'like', 'TENANT_SUBSCRIPTION_%')
                  ->orWhere('metadata->subscription_code', $subscription->subscription_code);
            })
            ->with('platformUser:id,name,email')
            ->latest()
            ->take(20)
            ->get();

        return view('platform.subscriptions.show', compact('subscription', 'auditLogs'));
    }

    /**
     * Activate a pending or trial subscription.
     */
    public function activate(TenantSubscription $subscription): RedirectResponse
    {
        $this->subscriptionService->activate($subscription);

        return back()->with('success', "Subscription {$subscription->subscription_code} is now active.");
    }

    /**
     * Renew an existing subscription.
     */
    public function renew(Request $request, TenantSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'ends_at' => ['nullable', 'date', 'after:today'],
            'grace_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_paid' => ['nullable', 'boolean'],
            'payment_provider' => ['nullable', 'string', 'max:50'],
        ]);

        $this->subscriptionService->renew($subscription, $validated);

        return back()->with('success', "Subscription {$subscription->subscription_code} has been successfully renewed.");
    }

    /**
     * Suspend a customer tenant subscription.
     */
    public function suspend(Request $request, TenantSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->subscriptionService->suspend($subscription, $validated['reason'] ?? null);

        return back()->with('warning', "Subscription {$subscription->subscription_code} has been suspended.");
    }

    /**
     * Reactivate a suspended subscription.
     */
    public function reactivate(TenantSubscription $subscription): RedirectResponse
    {
        $this->subscriptionService->reactivate($subscription);

        return back()->with('success', "Subscription {$subscription->subscription_code} has been reactivated.");
    }

    /**
     * Cancel a customer subscription.
     */
    public function cancel(Request $request, TenantSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->subscriptionService->cancel($subscription, $validated['reason'] ?? null);

        return back()->with('info', "Subscription {$subscription->subscription_code} has been cancelled.");
    }

    /**
     * Extend subscription validity.
     */
    public function extend(Request $request, TenantSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->subscriptionService->extendValidity($subscription, (int) $validated['days']);

        return back()->with('success', "Subscription {$subscription->subscription_code} validity extended by {$validated['days']} days.");
    }
}

