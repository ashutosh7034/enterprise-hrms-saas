<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TenantSubscriptionSeeder extends Seeder
{
    /**
     * Seed initial active Enterprise subscription for Vishal Group.
     * Idempotent: Does not overwrite or duplicate existing subscriptions.
     */
    public function run(): void
    {
        $tenant = Tenant::where('tenant_code', 'TNT-VISHAL')->first();
        if (!$tenant) {
            return;
        }

        $plan = SubscriptionPlan::where('plan_code', 'PLAN-ENTERPRISE')->first();
        if (!$plan) {
            $this->call(SubscriptionPlanSeeder::class);
            $plan = SubscriptionPlan::where('plan_code', 'PLAN-ENTERPRISE')->first();
        }

        $platformAdmin = PlatformUser::first();

        $startsAt = Carbon::now()->startOfYear();
        $endsAt = Carbon::now()->addYear()->endOfDay();
        $graceEndsAt = $endsAt->copy()->addDays(14);

        $subscription = TenantSubscription::updateOrCreate(
            ['subscription_code' => 'SUB-VISHAL-2026'],
            [
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_period_ends_at' => $graceEndsAt,
                'activated_at' => $startsAt,
                'renewal_type' => 'manual',
                'notes' => 'Commercial Enterprise Annual Tier provisioned by Advait IT Solutions.',
                'created_by_platform_user_id' => $platformAdmin?->id,
            ]
        );

        // Initial Paid Invoice
        $invoice = SubscriptionInvoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-00001'],
            [
                'tenant_id' => $tenant->id,
                'tenant_subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => 'paid',
                'issued_at' => $startsAt,
                'due_at' => $startsAt->copy()->addDays(15),
                'paid_at' => $startsAt,
                'notes' => 'Annual Enterprise License Subscription Payment',
            ]
        );

        // Payment Settlement Record
        SubscriptionPayment::updateOrCreate(
            ['payment_reference' => 'PAY-VISHAL-INIT-001'],
            [
                'tenant_id' => $tenant->id,
                'tenant_subscription_id' => $subscription->id,
                'subscription_invoice_id' => $invoice->id,
                'payment_provider' => 'manual_bank_transfer',
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => 'completed',
                'paid_at' => $startsAt,
                'metadata' => [
                    'bank_reference' => 'NEFT-AXIS-20260101-9988',
                    'verified_by' => 'Advait Billing Operations',
                ],
            ]
        );
    }
}

