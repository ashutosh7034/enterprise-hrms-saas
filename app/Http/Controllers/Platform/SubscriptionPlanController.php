<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\SubscriptionFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    protected array $standardFeatures = [
        'employee_management' => 'Workforce & Onboarding',
        'attendance_management' => 'Time & Attendance Engine',
        'leave_management' => 'Leave Policies & Sandwich Rules',
        'payroll_management' => 'Payroll & Statutory Compliance',
        'asset_management' => 'Asset Lifecycle & Recovery',
        'document_management' => 'Document Generation & ESS',
        'reports' => 'Enterprise Analytics & Exports',
        'recruitment' => 'Talent Acquisition & Job Openings',
        'performance_management' => 'Performance & Appraisals',
        'employee_self_service' => 'Employee Self Service (ESS)',
        'manager_self_service' => 'Manager Self Service (MSS)',
    ];

    /**
     * Display a listing of subscription plans.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::with(['features'])
            ->withCount('subscriptions')
            ->orderBy('price')
            ->get();

        return view('platform.plans.index', [
            'plans' => $plans,
            'features' => $this->standardFeatures,
        ]);
    }

    /**
     * Show form for creating a new plan.
     */
    public function create(): View
    {
        return view('platform.plans.create', [
            'features' => $this->standardFeatures,
        ]);
    }

    /**
     * Store newly created subscription plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_code' => ['required', 'string', 'max:30', 'unique:subscription_plans,plan_code', 'alpha_dash'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'billing_interval' => ['required', 'in:monthly,quarterly,yearly'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'employee_limit' => ['required', 'integer', 'min:1'],
            'company_limit' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
            'features' => ['nullable', 'array'],
        ]);

        $validated['plan_code'] = Str::upper($validated['plan_code']);

        $plan = DB::transaction(function () use ($validated, $request) {
            $plan = SubscriptionPlan::create([
                'plan_code' => $validated['plan_code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'billing_interval' => $validated['billing_interval'],
                'price' => $validated['price'],
                'currency' => $validated['currency'],
                'employee_limit' => $validated['employee_limit'],
                'company_limit' => $validated['company_limit'],
                'status' => $validated['status'],
            ]);

            $enabledFeatures = $validated['features'] ?? [];
            foreach ($this->standardFeatures as $code => $name) {
                SubscriptionFeature::create([
                    'subscription_plan_id' => $plan->id,
                    'feature_code' => $code,
                    'feature_name' => $name,
                    'is_enabled' => in_array($code, $enabledFeatures),
                ]);
            }

            PlatformAuditLog::create([
                'platform_user_id' => Auth::guard('platform')->id(),
                'tenant_id' => null,
                'action' => 'SUBSCRIPTION_PLAN_CREATED',
                'metadata' => [
                    'plan_code' => $plan->plan_code,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'interval' => $plan->billing_interval,
                ],
                'ip_address' => $request->ip(),
            ]);

            return $plan;
        });

        return redirect()->route('platform.plans.index')
            ->with('success', "Subscription plan '{$plan->name}' created successfully.");
    }

    /**
     * Show form for editing plan.
     */
    public function edit(SubscriptionPlan $plan): View
    {
        $plan->load('features');
        return view('platform.plans.edit', [
            'plan' => $plan,
            'features' => $this->standardFeatures,
        ]);
    }

    /**
     * Update existing subscription plan.
     */
    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'plan_code' => ['nullable', 'string', 'max:30', Rule::unique('subscription_plans')->ignore($plan->id), 'alpha_dash'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'billing_interval' => ['required', 'in:monthly,quarterly,yearly'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'employee_limit' => ['required', 'integer', 'min:1'],
            'company_limit' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
            'features' => ['nullable', 'array'],
        ]);

        $validated['plan_code'] = !empty($validated['plan_code']) ? Str::upper($validated['plan_code']) : $plan->plan_code;

        DB::transaction(function () use ($plan, $validated, $request) {
            $plan->update([
                'plan_code' => $validated['plan_code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'billing_interval' => $validated['billing_interval'],
                'price' => $validated['price'],
                'currency' => $validated['currency'],
                'employee_limit' => $validated['employee_limit'],
                'company_limit' => $validated['company_limit'],
                'status' => $validated['status'],
            ]);

            $enabledFeatures = $validated['features'] ?? [];
            foreach ($this->standardFeatures as $code => $name) {
                SubscriptionFeature::updateOrCreate(
                    ['subscription_plan_id' => $plan->id, 'feature_code' => $code],
                    [
                        'feature_name' => $name,
                        'is_enabled' => in_array($code, $enabledFeatures),
                    ]
                );
            }

            PlatformAuditLog::create([
                'platform_user_id' => Auth::guard('platform')->id(),
                'tenant_id' => null,
                'action' => 'SUBSCRIPTION_PLAN_UPDATED',
                'metadata' => [
                    'plan_code' => $plan->plan_code,
                    'name' => $plan->name,
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('platform.plans.index')
            ->with('success', "Subscription plan '{$plan->name}' updated successfully.");
    }

    /**
     * Toggle plan active/inactive status.
     */
    public function toggleStatus(SubscriptionPlan $plan): RedirectResponse
    {
        $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->update(['status' => $newStatus]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => null,
            'action' => 'SUBSCRIPTION_PLAN_STATUS_CHANGED',
            'metadata' => ['plan_code' => $plan->plan_code, 'status' => $newStatus],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "Plan status changed to '{$newStatus}'.");
    }
}
