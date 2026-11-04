@extends('layouts.app')

@section('title', 'Subscription & License Entitlements')

@section('content')
<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 12px;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
        <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Subscription & Licensing</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.02em;">Organization Subscription & License</h4>
            @if($subscription)
                @if($subscription->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                @elseif($subscription->status === 'grace_period')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Grace Period</span>
                @elseif($subscription->status === 'expired')
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Expired</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">{{ ucfirst($subscription->status) }}</span>
                @endif
            @else
                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Unlicensed</span>
            @endif
        </div>
        <p class="text-muted small mb-0 mt-1">Tenant boundary: <strong>{{ $tenant->name }}</strong> (<code>{{ $tenant->tenant_code }}</code>) — Managed by Advait IT Solutions & Services.</p>
    </div>
</div>

@if(session('subscription_grace_warning'))
    <div class="alert alert-warning py-3 px-4 d-flex align-items-center gap-3 mb-4 rounded border-0 shadow-sm" style="background-color: #FFFBEB; color: #92400E;">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
        <div>
            <strong class="d-block mb-1">Subscription Grace Period Active:</strong>
            <span class="small">{{ session('subscription_grace_warning') }}</span>
        </div>
    </div>
@endif

@if($subscription)
    <!-- Quota Consumption Cards -->
    <div class="row g-3 mb-4">
        <!-- Employee Seats -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small fw-medium">Active Employee Headcount</div>
                        <h3 class="fw-bold text-dark mb-0">{{ number_format($activeEmployeesCount) }} <span class="fs-6 text-muted fw-normal">/ {{ number_format($employeeLimit) }} seats</span></h3>
                    </div>
                    <div class="p-2 rounded bg-light text-primary">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>License Quota Used</span>
                        <span class="fw-semibold">{{ $employeeUsagePercentage }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $employeeUsagePercentage >= 90 ? 'bg-danger' : ($employeeUsagePercentage >= 75 ? 'bg-warning' : 'bg-primary') }}" role="progressbar" style="width: {{ $employeeUsagePercentage }}%;"></div>
                    </div>
                    <div class="small text-muted mt-2">
                        Total active headcount across all legal companies in <strong>{{ $tenant->name }}</strong>.
                    </div>
                </div>
            </div>
        </div>

        <!-- Company Entities -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small fw-medium">Registered Legal Entities</div>
                        <h3 class="fw-bold text-dark mb-0">{{ number_format($companiesCount) }} <span class="fs-6 text-muted fw-normal">/ {{ number_format($companyLimit) }} entities</span></h3>
                    </div>
                    <div class="p-2 rounded bg-light text-indigo">
                        <i class="bi bi-buildings fs-4"></i>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Entity Quota Used</span>
                        <span class="fw-semibold">{{ $companyUsagePercentage }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $companyUsagePercentage >= 90 ? 'bg-danger' : ($companyUsagePercentage >= 75 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ $companyUsagePercentage }}%;"></div>
                    </div>
                    <div class="small text-muted mt-2">
                        Legal entities operating within this tenant boundary.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tier Overview & Entitlements -->
    <div class="card border-0 shadow-sm rounded-3 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">Current Tier: {{ $subscription->plan->name }} ({{ $subscription->plan->plan_code }})</h5>
                <p class="text-muted small mb-0">Subscription Code: <code>{{ $subscription->subscription_code }}</code></p>
            </div>
            <div class="text-end">
                <div class="text-muted small">Valid Until</div>
                <div class="fw-bold text-dark">{{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : '—' }}</div>
            </div>
        </div>

        <h6 class="fw-bold text-dark small text-uppercase mb-3">Module Feature Entitlements</h6>
        <div class="row g-3">
            @foreach($subscription->plan->features as $feature)
                <div class="col-md-4 col-sm-6">
                    <div class="p-3 rounded border {{ $feature->is_enabled ? 'bg-white' : 'bg-light opacity-50' }} d-flex align-items-center gap-2">
                        @if($feature->is_enabled)
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <div>
                                <div class="fw-semibold text-dark small">{{ $feature->feature_name }}</div>
                                <div class="text-success small" style="font-size: 11px;">Licensed & Enabled</div>
                            </div>
                        @else
                            <i class="bi bi-lock-fill text-secondary fs-5"></i>
                            <div>
                                <div class="fw-semibold text-muted small">{{ $feature->feature_name }}</div>
                                <div class="text-muted small" style="font-size: 11px;">Not in current plan</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Invoices & Billing History -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="p-4 border-bottom">
            <h5 class="fw-bold text-dark mb-0">Billing & Invoices</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light" style="font-size: 12px; text-transform: uppercase;">
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>Issued Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="pe-4">Receipt</th>
                    </tr>
                </thead>
                <tbody style="font-size: 13.5px;">
                    @forelse($subscription->invoices as $inv)
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">{{ $inv->invoice_number }}</td>
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
                            <td class="pe-4 text-muted small">
                                @php $pmt = $inv->payments->last(); @endphp
                                @if($pmt)
                                    <code>{{ $pmt->payment_reference }}</code> ({{ $pmt->payment_provider }})
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">No billing invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm rounded-3 p-5 text-center text-muted">
        <i class="bi bi-shield-slash fs-1 text-secondary mb-3"></i>
        <h5>No active subscription plan found</h5>
        <p class="small mb-0">Please contact Advait IT Solutions & Services platform administration to allocate an enterprise license.</p>
    </div>
@endif

<!-- Support & Tier Upgrade Information -->
<div class="card border-0 shadow-sm rounded-3 p-4 bg-light">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h6 class="fw-bold text-dark mb-1">Need to expand employee quotas or unlock additional modules?</h6>
            <p class="text-muted small mb-0">Contact Advait IT Solutions & Services account management for enterprise tier upgrades and custom SLA terms.</p>
        </div>
        <div>
            <a href="mailto:support@advaititsolutions.com" class="btn btn-outline-primary btn-sm px-3">
                <i class="bi bi-envelope me-1"></i> Contact SaaS Billing Support
            </a>
        </div>
    </div>
</div>
@endsection
