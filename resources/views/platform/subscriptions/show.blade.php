@extends('layouts.platform')

@section('title', 'Subscription ' . $subscription->subscription_code . ' | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.subscriptions.index') }}" class="text-decoration-none">Subscriptions</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $subscription->subscription_code }}</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.02em;">Subscription {{ $subscription->subscription_code }}</h3>
                @if($subscription->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6">Active</span>
                @elseif($subscription->status === 'grace_period')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fs-6">Grace Period</span>
                @elseif($subscription->status === 'expired')
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-6">Expired</span>
                @elseif($subscription->status === 'suspended')
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fs-6">Suspended</span>
                @else
                    <span class="badge bg-light text-dark border px-2 py-1 fs-6">{{ ucfirst($subscription->status) }}</span>
                @endif
            </div>
            <div class="text-muted small mt-1">
                Tenant: <a href="{{ route('platform.tenants.show', $subscription->tenant) }}" class="fw-semibold text-decoration-none">{{ $subscription->tenant->name }}</a>
                <span class="code-pill ms-1">{{ $subscription->tenant->tenant_code }}</span>
            </div>
        </div>

        <!-- Quick Lifecycle Actions -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(in_array($subscription->status, ['trial', 'pending']))
                <form method="POST" action="{{ route('platform.subscriptions.activate', $subscription) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm px-3">Activate Subscription</button>
                </form>
            @endif

            @if($subscription->status === 'suspended')
                <form method="POST" action="{{ route('platform.subscriptions.reactivate', $subscription) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm px-3">Reactivate Subscription</button>
                </form>
            @elseif($subscription->status !== 'cancelled')
                <button type="button" class="btn btn-outline-warning text-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#suspendModal">
                    Suspend
                </button>
            @endif

            <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#extendModal">
                Extend Validity
            </button>

            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#renewModal">
                Renew Subscription
            </button>

            @if($subscription->status !== 'cancelled')
                <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    Cancel
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Subscription Details -->
    <div class="col-lg-6">
        <div class="platform-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Subscription Terms</h5>
            <dl class="row mb-0" style="font-size: 13.5px;">
                <dt class="col-sm-4 text-muted fw-normal">Plan Tier</dt>
                <dd class="col-sm-8 fw-semibold text-dark">{{ $subscription->plan->name }} ({{ $subscription->plan->plan_code }})</dd>

                <dt class="col-sm-4 text-muted fw-normal">Billing Interval</dt>
                <dd class="col-sm-8 text-capitalize">{{ $subscription->plan->billing_interval }} ({{ $subscription->plan->currency }} {{ number_format($subscription->plan->price, 2) }})</dd>

                <dt class="col-sm-4 text-muted fw-normal">Start Date</dt>
                <dd class="col-sm-8 text-dark">{{ $subscription->starts_at ? $subscription->starts_at->format('d M Y') : '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Expiration Date</dt>
                <dd class="col-sm-8 fw-semibold text-dark">{{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Grace Period Ends</dt>
                <dd class="col-sm-8 text-muted">{{ $subscription->grace_period_ends_at ? $subscription->grace_period_ends_at->format('d M Y') : '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Renewal Mode</dt>
                <dd class="col-sm-8 text-capitalize">{{ $subscription->renewal_type }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Notes</dt>
                <dd class="col-sm-8 text-muted">{{ $subscription->notes ?: 'No notes recorded.' }}</dd>
            </dl>
        </div>
    </div>

    <!-- Quotas & Entitlements -->
    <div class="col-lg-6">
        <div class="platform-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-sliders text-primary me-2"></i>Resource Quotas & Modules</h5>
            
            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border text-center">
                        <div class="text-muted small">Employee Limit</div>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($subscription->plan->employee_limit) }}</h4>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border text-center">
                        <div class="text-muted small">Legal Company Limit</div>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($subscription->plan->company_limit) }}</h4>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-dark small text-uppercase mb-2">Entitled Modules</h6>
            <div class="d-flex flex-wrap gap-1">
                @foreach($subscription->plan->features as $feature)
                    @if($feature->is_enabled)
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                            <i class="bi bi-check2 me-1"></i>{{ $feature->feature_name }}
                        </span>
                    @else
                        <span class="badge bg-light text-muted border py-1 px-2 text-decoration-line-through opacity-50">
                            {{ $feature->feature_name }}
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Invoices & Payment Ledger -->
<div class="platform-card mb-4">
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-receipt text-primary me-2"></i>Billing Invoices & Payments</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                <tr>
                    <th class="ps-4">Invoice #</th>
                    <th>Issued At</th>
                    <th>Due At</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment Reference</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody style="font-size: 13.5px;">
                @forelse($subscription->invoices as $inv)
                    <tr>
                        <td class="ps-4 fw-semibold"><a href="{{ route('platform.invoices.show', $inv) }}" class="text-decoration-none">{{ $inv->invoice_number }}</a></td>
                        <td class="text-muted">{{ $inv->issued_at ? $inv->issued_at->format('d M Y') : '—' }}</td>
                        <td class="text-muted">{{ $inv->due_at ? $inv->due_at->format('d M Y') : '—' }}</td>
                        <td class="fw-bold text-dark">{{ $inv->currency }} {{ number_format($inv->amount, 2) }}</td>
                        <td>
                            @if($inv->status === 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                            @endif
                        </td>
                        <td>
                            @php $latestPayment = $inv->payments->last(); @endphp
                            @if($latestPayment)
                                <span class="code-pill">{{ $latestPayment->payment_reference }}</span>
                                <div class="text-muted small">{{ $latestPayment->payment_provider }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('platform.invoices.show', $inv) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No invoices generated for this subscription.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Subscription Audit Trail -->
<div class="platform-card">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Subscription Audit Trail</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                <tr>
                    <th class="ps-4">Timestamp</th>
                    <th>Action</th>
                    <th>Platform Operator</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody style="font-size: 13px;">
                @forelse($auditLogs as $log)
                    <tr>
                        <td class="ps-4 text-muted">{{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}</td>
                        <td><span class="code-pill">{{ $log->action }}</span></td>
                        <td class="fw-semibold text-dark">{{ $log->platformUser ? $log->platformUser->name : 'System' }}</td>
                        <td class="text-muted">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No audit log records for this subscription.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Extend Validity -->
<div class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.subscriptions.extend', $subscription) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Extend Subscription Validity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Days to Extend</label>
                    <input type="number" name="days" class="form-control" value="30" min="1" max="365" required>
                    <div class="form-text small">Adds specified days to the current end date and resets grace period.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Extend Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Renew Subscription -->
<div class="modal fade" id="renewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.subscriptions.renew', $subscription) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Renew Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Renews subscription for one interval cycle based on <strong>{{ $subscription->plan->name }}</strong> ({{ $subscription->plan->billing_interval }}).</p>
                    <div class="form-check p-2 bg-light border rounded">
                        <input class="form-check-input ms-1 me-2" type="checkbox" name="is_paid" value="1" id="renew_is_paid" checked>
                        <label class="form-check-label fw-semibold text-dark cursor-pointer" for="renew_is_paid">
                            Mark renewal invoice as paid immediately
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirm Renewal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Suspend Subscription -->
<div class="modal fade" id="suspendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.subscriptions.suspend', $subscription) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-warning">Suspend Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-semibold">Reason for Suspension</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Non-payment, contract breach, or tenant request..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm">Confirm Suspension</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cancel Subscription -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('platform.subscriptions.cancel', $subscription) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Cancel Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger small fw-semibold mb-2">Warning: This will terminate the subscription license for this tenant.</p>
                    <label class="form-label small fw-semibold">Cancellation Reason</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Reason for cancellation..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Cancellation</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

