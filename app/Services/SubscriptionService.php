<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Assign a subscription plan to a customer tenant.
     */
    public function assignPlan(Tenant $tenant, SubscriptionPlan $plan, array $options = []): TenantSubscription
    {
        return DB::transaction(function () use ($tenant, $plan, $options) {
            $startsAt = isset($options['starts_at']) ? Carbon::parse($options['starts_at']) : Carbon::now();
            $interval = $plan->billing_interval;

            $endsAt = match ($interval) {
                'yearly' => $startsAt->copy()->addYear(),
                'quarterly' => $startsAt->copy()->addMonths(3),
                default => $startsAt->copy()->addMonth(),
            };

            if (isset($options['ends_at'])) {
                $endsAt = Carbon::parse($options['ends_at']);
            }

            $graceDays = (int) ($options['grace_days'] ?? 14);
            $graceEndsAt = $endsAt->copy()->addDays($graceDays);

            $subscriptionCode = 'SUB-' . $tenant->tenant_code . '-' . date('Ymd') . '-' . Str::upper(Str::random(4));
            $platformUserId = Auth::guard('platform')->id();

            // Archive or expire any existing active subscription for this tenant
            TenantSubscription::where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'trial', 'grace_period'])
                ->update(['status' => 'expired']);

            $subscription = TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'subscription_code' => $subscriptionCode,
                'status' => $options['status'] ?? 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_period_ends_at' => $graceEndsAt,
                'activated_at' => ($options['status'] ?? 'active') === 'active' ? now() : null,
                'renewal_type' => $options['renewal_type'] ?? 'manual',
                'notes' => $options['notes'] ?? null,
                'metadata' => $options['metadata'] ?? null,
                'created_by_platform_user_id' => $platformUserId,
            ]);

            // Generate initial billing invoice
            $invoiceNumber = 'INV-' . date('Y') . '-' . sprintf('%05d', SubscriptionInvoice::count() + 1);
            $invoice = SubscriptionInvoice::create([
                'tenant_id' => $tenant->id,
                'tenant_subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => !empty($options['is_paid']) ? 'paid' : 'pending',
                'issued_at' => $startsAt,
                'due_at' => $startsAt->copy()->addDays(15),
                'paid_at' => !empty($options['is_paid']) ? now() : null,
                'notes' => "Invoice for {$plan->name} ({$subscription->subscription_code})",
            ]);

            if (!empty($options['is_paid'])) {
                SubscriptionPayment::create([
                    'tenant_id' => $tenant->id,
                    'tenant_subscription_id' => $subscription->id,
                    'subscription_invoice_id' => $invoice->id,
                    'payment_reference' => 'PAY-' . Str::upper(Str::random(12)),
                    'payment_provider' => $options['payment_provider'] ?? 'manual_bank_transfer',
                    'amount' => $plan->price,
                    'currency' => $plan->currency,
                    'status' => 'completed',
                    'paid_at' => now(),
                    'metadata' => ['recorded_by_platform_user' => $platformUserId],
                ]);
            }

            PlatformAuditLog::create([
                'platform_user_id' => $platformUserId,
                'tenant_id' => $tenant->id,
                'action' => 'TENANT_SUBSCRIPTION_ASSIGNED',
                'metadata' => [
                    'subscription_code' => $subscription->subscription_code,
                    'plan_code' => $plan->plan_code,
                    'plan_name' => $plan->name,
                    'ends_at' => $endsAt->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
            ]);

            return $subscription;
        });
    }

    /**
     * Activate a pending/trial subscription.
     */
    public function activate(TenantSubscription $subscription): bool
    {
        $subscription->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $subscription->tenant_id,
            'action' => 'TENANT_SUBSCRIPTION_ACTIVATED',
            'metadata' => ['subscription_code' => $subscription->subscription_code],
            'ip_address' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Renew an existing subscription.
     */
    public function renew(TenantSubscription $subscription, array $options = []): TenantSubscription
    {
        return DB::transaction(function () use ($subscription, $options) {
            $plan = $subscription->plan;
            $interval = $plan->billing_interval;

            $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture())
                ? $subscription->ends_at
                : Carbon::now();

            $newEndsAt = match ($interval) {
                'yearly' => $baseDate->copy()->addYear(),
                'quarterly' => $baseDate->copy()->addMonths(3),
                default => $baseDate->copy()->addMonth(),
            };

            if (isset($options['ends_at'])) {
                $newEndsAt = Carbon::parse($options['ends_at']);
            }

            $graceDays = (int) ($options['grace_days'] ?? 14);
            $graceEndsAt = $newEndsAt->copy()->addDays($graceDays);

            $subscription->update([
                'status' => 'active',
                'ends_at' => $newEndsAt,
                'grace_period_ends_at' => $graceEndsAt,
                'suspended_at' => null,
            ]);

            // Create renewal invoice
            $invoiceNumber = 'INV-' . date('Y') . '-' . sprintf('%05d', SubscriptionInvoice::count() + 1);
            $invoice = SubscriptionInvoice::create([
                'tenant_id' => $subscription->tenant_id,
                'tenant_subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => !empty($options['is_paid']) ? 'paid' : 'pending',
                'issued_at' => now(),
                'due_at' => now()->addDays(15),
                'paid_at' => !empty($options['is_paid']) ? now() : null,
                'notes' => "Renewal invoice for {$plan->name} ({$subscription->subscription_code})",
            ]);

            if (!empty($options['is_paid'])) {
                SubscriptionPayment::create([
                    'tenant_id' => $subscription->tenant_id,
                    'tenant_subscription_id' => $subscription->id,
                    'subscription_invoice_id' => $invoice->id,
                    'payment_reference' => 'PAY-' . Str::upper(Str::random(12)),
                    'payment_provider' => $options['payment_provider'] ?? 'manual_bank_transfer',
                    'amount' => $plan->price,
                    'currency' => $plan->currency,
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);
            }

            PlatformAuditLog::create([
                'platform_user_id' => Auth::guard('platform')->id(),
                'tenant_id' => $subscription->tenant_id,
                'action' => 'TENANT_SUBSCRIPTION_RENEWED',
                'metadata' => [
                    'subscription_code' => $subscription->subscription_code,
                    'new_ends_at' => $newEndsAt->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
            ]);

            return $subscription;
        });
    }

    /**
     * Suspend a customer tenant subscription.
     */
    public function suspend(TenantSubscription $subscription, ?string $reason = null): bool
    {
        $subscription->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'notes' => $reason ? ($subscription->notes . "\n[Suspended: {$reason}]") : $subscription->notes,
        ]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $subscription->tenant_id,
            'action' => 'TENANT_SUBSCRIPTION_SUSPENDED',
            'metadata' => [
                'subscription_code' => $subscription->subscription_code,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Reactivate a suspended customer tenant subscription.
     */
    public function reactivate(TenantSubscription $subscription): bool
    {
        $status = 'active';
        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            $status = ($subscription->grace_period_ends_at && $subscription->grace_period_ends_at->isFuture())
                ? 'grace_period'
                : 'expired';
        }

        $subscription->update([
            'status' => $status,
            'suspended_at' => null,
        ]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $subscription->tenant_id,
            'action' => 'TENANT_SUBSCRIPTION_REACTIVATED',
            'metadata' => [
                'subscription_code' => $subscription->subscription_code,
                'new_status' => $status,
            ],
            'ip_address' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Cancel a customer tenant subscription.
     */
    public function cancel(TenantSubscription $subscription, ?string $reason = null): bool
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'notes' => $reason ? ($subscription->notes . "\n[Cancelled: {$reason}]") : $subscription->notes,
        ]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $subscription->tenant_id,
            'action' => 'TENANT_SUBSCRIPTION_CANCELLED',
            'metadata' => [
                'subscription_code' => $subscription->subscription_code,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
        ]);

        return true;
    }

    /**
     * Extend subscription validity by specified days.
     */
    public function extendValidity(TenantSubscription $subscription, int $days): bool
    {
        $endsAt = ($subscription->ends_at && $subscription->ends_at->isFuture())
            ? $subscription->ends_at->copy()->addDays($days)
            : Carbon::now()->addDays($days);

        $graceEndsAt = $endsAt->copy()->addDays(14);

        $subscription->update([
            'status' => 'active',
            'ends_at' => $endsAt,
            'grace_period_ends_at' => $graceEndsAt,
        ]);

        PlatformAuditLog::create([
            'platform_user_id' => Auth::guard('platform')->id(),
            'tenant_id' => $subscription->tenant_id,
            'action' => 'TENANT_SUBSCRIPTION_EXTENDED',
            'metadata' => [
                'subscription_code' => $subscription->subscription_code,
                'extended_days' => $days,
                'new_ends_at' => $endsAt->toIso8601String(),
            ],
            'ip_address' => request()->ip(),
        ]);

        return true;
    }
}

