@extends('layouts.platform')

@section('title', 'Invoice ' . $invoice->invoice_number . ' | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.invoices.index') }}" class="text-decoration-none">Invoices</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.02em;">Invoice {{ $invoice->invoice_number }}</h3>
            @if($invoice->status === 'paid')
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6">Paid</span>
            @else
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fs-6">Pending Settlement</span>
            @endif
        </div>

        @if($invoice->status !== 'paid')
            <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                <i class="bi bi-check2-circle me-1"></i> Record Settlement Payment
            </button>
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="platform-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text text-primary me-2"></i>Invoice Details</h5>
            <dl class="row mb-0" style="font-size: 13.5px;">
                <dt class="col-sm-4 text-muted fw-normal">Invoice Number</dt>
                <dd class="col-sm-8"><span class="code-pill fw-semibold">{{ $invoice->invoice_number }}</span></dd>

                <dt class="col-sm-4 text-muted fw-normal">Customer Tenant</dt>
                <dd class="col-sm-8">
                    <a href="{{ route('platform.tenants.show', $invoice->tenant) }}" class="fw-semibold text-decoration-none">
                        {{ $invoice->tenant->name }} ({{ $invoice->tenant->tenant_code }})
                    </a>
                </dd>

                <dt class="col-sm-4 text-muted fw-normal">Subscription Code</dt>
                <dd class="col-sm-8">
                    @if($invoice->subscription)
                        <a href="{{ route('platform.subscriptions.show', $invoice->subscription) }}" class="text-decoration-none">
                            {{ $invoice->subscription->subscription_code }}
                        </a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </dd>

                <dt class="col-sm-4 text-muted fw-normal">Subscribed Tier</dt>
                <dd class="col-sm-8 text-dark">{{ $invoice->subscription->plan->name ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Invoice Amount</dt>
                <dd class="col-sm-8 fw-bold fs-5 text-dark">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Issued Date</dt>
                <dd class="col-sm-8 text-dark">{{ $invoice->issued_at ? $invoice->issued_at->format('d M Y') : '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Due Date</dt>
                <dd class="col-sm-8 text-dark">{{ $invoice->due_at ? $invoice->due_at->format('d M Y') : '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Paid At</dt>
                <dd class="col-sm-8 text-dark">{{ $invoice->paid_at ? $invoice->paid_at->format('d M Y, h:i A') : 'Pending' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Notes</dt>
                <dd class="col-sm-8 text-muted">{{ $invoice->notes ?: '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="platform-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-wallet2 text-primary me-2"></i>Platform Owner Entity</h5>
            <div class="p-3 bg-light rounded border mb-3" style="font-size: 13px;">
                <div class="fw-bold text-dark mb-1">Kanvtech</div>
                <div class="text-muted mb-2">Enterprise SaaS Platform Operations</div>
                <div class="text-muted small">Official SaaS Provider for Enterprise HRMS Architecture.</div>
            </div>

            <h6 class="fw-bold text-dark small text-uppercase mb-2">Customer Billing Address</h6>
            <div class="text-muted small">
                <div class="fw-semibold text-dark">{{ $invoice->tenant->name }}</div>
                <div>Attn: {{ $invoice->tenant->contact_name ?: 'Accounts Payable' }}</div>
                <div>{{ $invoice->tenant->address ?: 'Address on file' }}</div>
                <div>{{ $invoice->tenant->contact_email }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Receipts -->
<div class="platform-card mb-4">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-credit-card text-primary me-2"></i>Payment Settlement Receipts</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                <tr>
                    <th class="ps-4">Receipt Reference</th>
                    <th>Provider / Channel</th>
                    <th>Amount</th>
                    <th>Settled Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody style="font-size: 13.5px;">
                @forelse($invoice->payments as $payment)
                    <tr>
                        <td class="ps-4 fw-semibold"><span class="code-pill">{{ $payment->payment_reference }}</span></td>
                        <td><span class="badge bg-light text-dark border">{{ $payment->payment_provider }}</span></td>
                        <td class="fw-bold text-dark">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                        <td class="text-muted">{{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : '—' }}</td>
                        <td><span class="badge bg-success-subtle text-success border border-success-subtle">Completed</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No payments recorded for this invoice yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.invoices.mark-paid', $invoice) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Record Settlement Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Payment Channel <span class="text-danger">*</span></label>
                        <select name="payment_provider" class="form-select" required>
                            <option value="manual_bank_transfer">Direct Bank Transfer / NEFT / RTGS</option>
                            <option value="cheque">Corporate Cheque</option>
                            <option value="upi">UPI / Online Gateway</option>
                            <option value="complimentary">Complimentary / Platform Waiver</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Transaction / UTR Reference</label>
                        <input type="text" name="payment_reference" class="form-control" placeholder="e.g. UTR123456789">
                        <div class="form-text small">Leave blank to auto-generate a PAY- receipt token.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Bank name, branch, cheque number, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm Payment Settlement</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

