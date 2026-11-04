@extends('layouts.platform')

@section('title', 'Platform Control Tower')

@section('content')
<!-- Dashboard Title Bar -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.025em; font-size: 24px;">Kanvtech HRMS SaaS Control Tower</h3>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Kanvtech — Enterprise SaaS Infrastructure & Licensing Engine</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 d-inline-flex align-items-center gap-2" style="font-size: 12px; border-radius: 8px;">
            <span class="platform-status-dot"></span>
            <span class="fw-semibold">Multi-Tenant SaaS Guard: Active</span>
        </span>
    </div>
</div>

<!-- Super Admin Identity & Session Card -->
<div class="platform-card p-4 mb-4" style="border-left: 4px solid #4F46E5;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%); font-size: 18px; flex-shrink: 0;">
                K
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <h5 class="fw-bold mb-0 text-dark" style="font-size: 16px; letter-spacing: -0.01em;">
                        Kanvtech Platform Admin <span class="text-muted fw-normal" style="font-size: 13px;">({{ $platformAdmin->name }})</span>
                    </h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10.5px; padding: 3px 8px;">
                        Platform Super Admin
                    </span>
                </div>
                <div class="text-muted d-flex align-items-center gap-3 flex-wrap" style="font-size: 12.5px;">
                    <span><i class="bi bi-envelope text-secondary me-1"></i> {{ $platformAdmin->email }}</span>
                    <span class="text-secondary opacity-50">•</span>
                    <span><i class="bi bi-hdd-network text-secondary me-1"></i> Session IP: <span class="code-pill">{{ request()->ip() }}</span></span>
                </div>
            </div>
        </div>
        <div class="text-start text-sm-end ps-3 border-start-sm">
            <div class="text-muted small fw-medium">Last Login Timestamp</div>
            <div class="fw-bold text-dark" style="font-size: 13.5px;">
                {{ $platformAdmin->last_login_at ? $platformAdmin->last_login_at->format('d M Y, h:i A') : 'Active Session' }}
            </div>
        </div>
    </div>
</div>

<!-- SaaS Overview Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Customer Tenants -->
    <div class="col-xl-3 col-sm-6">
        <div class="platform-card p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.04em;">Customer Tenants</div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #EEF2FF; color: #4F46E5;">
                        <i class="bi bi-buildings fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" style="color: #0F172A; font-size: 26px; letter-spacing: -0.02em;">{{ $metrics['total_tenants'] }}</h3>
                <div class="text-success small fw-medium d-flex align-items-center gap-1">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ $metrics['active_tenants'] }} Active Workspace{{ $metrics['active_tenants'] > 1 ? 's' : '' }}</span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="{{ route('platform.tenants.index') }}" class="text-primary text-decoration-none small fw-semibold d-inline-flex align-items-center gap-1">
                    <span>Manage Tenants</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Active Subscriptions -->
    <div class="col-xl-3 col-sm-6">
        <div class="platform-card p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.04em;">Active Subscriptions</div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F0FDF4; color: #16A34A;">
                        <i class="bi bi-key fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" style="color: #0F172A; font-size: 26px; letter-spacing: -0.02em;">{{ $metrics['active_subscriptions'] }}</h3>
                <div class="small fw-medium">
                    @if($metrics['grace_subscriptions'] > 0)
                        <span class="text-warning d-inline-flex align-items-center gap-1">
                            <i class="bi bi-clock-history"></i> {{ $metrics['grace_subscriptions'] }} in Grace Period
                        </span>
                    @else
                        <span class="text-success d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check2-circle"></i> Fully active licenses
                        </span>
                    @endif
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="{{ route('platform.subscriptions.index') }}" class="text-primary text-decoration-none small fw-semibold d-inline-flex align-items-center gap-1">
                    <span>View Subscriptions</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Commercial Plans -->
    <div class="col-xl-3 col-sm-6">
        <div class="platform-card p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.04em;">Commercial Plans</div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #F0FDFA; color: #0D9488;">
                        <i class="bi bi-layers fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" style="color: #0F172A; font-size: 26px; letter-spacing: -0.02em;">{{ $metrics['total_plans'] }}</h3>
                <div class="text-success small fw-medium d-flex align-items-center gap-1">
                    <i class="bi bi-tag-fill"></i>
                    <span>{{ $metrics['active_plans'] }} Active Plan Tiers</span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="{{ route('platform.plans.index') }}" class="text-primary text-decoration-none small fw-semibold d-inline-flex align-items-center gap-1">
                    <span>Manage Plans</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Total Billed Revenue -->
    <div class="col-xl-3 col-sm-6">
        <div class="platform-card p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.04em;">Total Billed Revenue</div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background-color: #FFF7ED; color: #EA580C;">
                        <i class="bi bi-currency-rupee fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1" style="color: #0F172A; font-size: 24px; letter-spacing: -0.02em;">₹{{ number_format($metrics['total_revenue'], 2) }}</h3>
                <div class="text-muted small fw-medium d-flex align-items-center gap-1">
                    <i class="bi bi-receipt"></i>
                    <span>{{ $metrics['paid_invoices'] }} Paid Invoice{{ $metrics['paid_invoices'] != 1 ? 's' : '' }}</span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="{{ route('platform.invoices.index') }}" class="text-primary text-decoration-none small fw-semibold d-inline-flex align-items-center gap-1">
                    <span>Invoices & Billing</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Subscriptions & Recent Invoices Grid -->
<div class="row g-4 mb-4">
    <!-- Recent Subscriptions Table -->
    <div class="col-lg-6">
        <div class="platform-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-key text-primary fs-5"></i>
                        <h5 class="fw-bold text-dark mb-0" style="font-size: 15px;">Recent Subscriptions</h5>
                    </div>
                    <a href="{{ route('platform.subscriptions.index') }}" class="text-decoration-none small fw-semibold text-primary">
                        View All <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">
                            <tr>
                                <th>Tenant</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th class="text-end">Expires</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            @forelse($recentSubscriptions as $sub)
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        <a href="{{ route('platform.tenants.show', $sub->tenant) }}" class="text-decoration-none text-dark">
                                            {{ $sub->tenant->name }}
                                        </a>
                                    </td>
                                    <td><span class="code-pill">{{ $sub->plan->name }}</span></td>
                                    <td>
                                        @if($sub->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                        @elseif($sub->status === 'grace_period')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Grace Period</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">{{ ucfirst($sub->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-muted font-monospace small">
                                        {{ $sub->ends_at ? $sub->ends_at->format('d M Y') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No subscriptions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices Table -->
    <div class="col-lg-6">
        <div class="platform-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-primary fs-5"></i>
                        <h5 class="fw-bold text-dark mb-0" style="font-size: 15px;">Recent Invoices</h5>
                    </div>
                    <a href="{{ route('platform.invoices.index') }}" class="text-decoration-none small fw-semibold text-primary">
                        View All <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">
                            <tr>
                                <th>Invoice #</th>
                                <th>Tenant</th>
                                <th>Amount</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            @forelse($recentInvoices as $inv)
                                <tr>
                                    <td>
                                        <a href="{{ route('platform.invoices.show', $inv) }}" class="fw-semibold text-decoration-none font-monospace">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="text-dark">{{ $inv->tenant->name }}</td>
                                    <td class="fw-semibold">{{ $inv->currency }} {{ number_format($inv->amount, 2) }}</td>
                                    <td class="text-end">
                                        @if($inv->status === 'paid')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Paid</span>
                                        @elseif($inv->status === 'pending')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">{{ ucfirst($inv->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SaaS Transformation Staged Roadmap Banner -->
<div class="platform-card p-4">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1" style="color: #0F172A; font-size: 16px;">Multi-Tenant SaaS Evolution Status</h5>
            <p class="text-muted mb-0" style="font-size: 13px;">Sequential phase-by-phase implementation under Kanvtech ownership.</p>
        </div>
        <span class="badge bg-success-subtle text-success px-3 py-2 border border-success-subtle d-inline-flex align-items-center gap-1" style="font-size: 12px; border-radius: 8px;">
            <i class="bi bi-check2-circle"></i>
            <span class="fw-semibold">Phase 3: Licensing Engine Active</span>
        </span>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="p-3 rounded-3 border bg-light h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success" style="font-size: 10px;">COMPLETE</span>
                    <strong style="font-size: 13.5px; color: #0F172A;">Phase 1: Super Admin Guard</strong>
                </div>
                <p class="text-muted mb-0" style="font-size: 12px; line-height: 1.55;">
                    Isolated authentication guard, dedicated table, rate-limited login, and zero cross-tenant session leakage.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3 border bg-light h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success" style="font-size: 10px;">COMPLETE</span>
                    <strong style="font-size: 13.5px; color: #0F172A;">Phase 2: Multi-Tenant Architecture</strong>
                </div>
                <p class="text-muted mb-0" style="font-size: 12px; line-height: 1.55;">
                    Tenant isolation, group company boundaries, tenant active middleware, platform tenant CRUD, and audit logging.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3 border bg-light h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success" style="font-size: 10px;">COMPLETE</span>
                    <strong style="font-size: 13.5px; color: #0F172A;">Phase 3: Licensing & Subscriptions</strong>
                </div>
                <p class="text-muted mb-0" style="font-size: 12px; line-height: 1.55;">
                    Commercial plan tiers, module entitlements, employee quotas, validity & grace management, and billing records.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
