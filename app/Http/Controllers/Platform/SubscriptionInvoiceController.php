<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionInvoiceController extends Controller
{
    /**
     * Display a listing of SaaS subscription invoices.
     */
    public function index(Request $request): View
    {
        $query = SubscriptionInvoice::with(['tenant', 'subscription.plan'])
            ->latest('id');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('tenant_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            if (in_array($status, ['pending', 'paid', 'cancelled', 'void'])) {
                $query->where('status', $status);
            }
        }

        $invoices = $query->paginate(15)->withQueryString();

        $counts = [
            'total' => SubscriptionInvoice::count(),
            'paid' => SubscriptionInvoice::where('status', 'paid')->count(),
            'pending' => SubscriptionInvoice::where('status', 'pending')->count(),
            'total_revenue' => SubscriptionInvoice::where('status', 'paid')->sum('amount'),
        ];

        return view('platform.invoices.index', compact('invoices', 'counts'));
    }

    /**
     * Display a single invoice with its payment transactions.
     */
    public function show(SubscriptionInvoice $invoice): View
    {
        $invoice->load(['tenant', 'subscription.plan', 'payments']);

        return view('platform.invoices.show', compact('invoice'));
    }

    /**
     * Mark an invoice as paid and record settlement payment.
     */
    public function markAsPaid(Request $request, SubscriptionInvoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'paid') {
            return back()->with('info', "Invoice {$invoice->invoice_number} is already marked as paid.");
        }

        $validated = $request->validate([
            'payment_provider' => ['required', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $platformUser = Auth::guard('platform')->user();

        DB::transaction(function () use ($invoice, $validated, $platformUser) {
            $reference = $validated['payment_reference'] ?: ('PAY-' . Str::upper(Str::random(12)));

            $payment = SubscriptionPayment::create([
                'tenant_id' => $invoice->tenant_id,
                'tenant_subscription_id' => $invoice->tenant_subscription_id,
                'subscription_invoice_id' => $invoice->id,
                'payment_reference' => $reference,
                'payment_provider' => $validated['payment_provider'],
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'status' => 'completed',
                'paid_at' => now(),
                'metadata' => [
                    'recorded_by_platform_user_id' => $platformUser?->id,
                    'notes' => $validated['notes'] ?? null,
                ],
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            PlatformAuditLog::create([
                'platform_user_id' => $platformUser?->id,
                'tenant_id' => $invoice->tenant_id,
                'action' => 'SUBSCRIPTION_INVOICE_PAID',
                'metadata' => [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                    'currency' => $invoice->currency,
                    'payment_reference' => $payment->payment_reference,
                    'provider' => $payment->payment_provider,
                ],
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as paid successfully.");
    }
}

