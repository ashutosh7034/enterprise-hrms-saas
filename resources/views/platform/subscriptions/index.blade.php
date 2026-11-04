@extends('layouts.platform')

@section('title', 'Subscriptions | Platform Control Center')

@section('content')
<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Subscriptions</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Tenant Subscriptions & Licenses</h3>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Monitor active subscriptions, validity lifecycles, grace periods, and tier provisioning.</p>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Total Subscriptions</div>
            <h4 class="fw-bold mb-0 text-dark">{{ $counts['total'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Active Licenses</div>
            <h4 class="fw-bold mb-0 text-success">{{ $counts['active'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Grace Period</div>
            <h4 class="fw-bold mb-0 text-warning">{{ $counts['grace_period'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Expired / Suspended</div>
            <h4 class="fw-bold mb-0 text-danger">{{ $counts['expired'] + $counts['suspended'] }}</h4>
        </div>
    </div>
</div>

<!-- Subscriptions Table -->
<div class="platform-card mb-4">
    <div class="p-4 border-bottom">
        <form method="GET" action="{{ route('platform.subscriptions.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by subscription code or tenant..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="grace_period" {{ request('status') === 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="plan_id" class="form-select form-select-sm">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('platform.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                <tr>
                    <th class="ps-4">Subscription Code</th>
                    <th>Customer Tenant</th>
                    <th>Plan Tier</th>
                    <th>Starts</th>
                    <th>Ends</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size: 13.5px;">
                @forelse($subscriptions as $sub)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('platform.subscriptions.show', $sub) }}" class="fw-semibold text-decoration-none">
                                {{ $sub->subscription_code }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $sub->tenant->name }}</div>
                            <span class="code-pill">{{ $sub->tenant->tenant_code }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $sub->plan->name }}</span>
                            <div class="text-muted small mt-1">Quota: {{ number_format($sub->plan->employee_limit) }} Emps</div>
                        </td>
                        <td class="text-muted" style="font-size: 12.5px;">{{ $sub->starts_at ? $sub->starts_at->format('d M Y') : '—' }}</td>
                        <td class="text-dark" style="font-size: 12.5px;">
                            {{ $sub->ends_at ? $sub->ends_at->format('d M Y') : '—' }}
                            @if($sub->grace_period_ends_at && $sub->isGracePeriod())
                                <div class="text-warning small">Grace to: {{ $sub->grace_period_ends_at->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            @if($sub->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                            @elseif($sub->status === 'grace_period')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Grace Period</span>
                            @elseif($sub->status === 'expired')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Expired</span>
                            @elseif($sub->status === 'suspended')
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Suspended</span>
                            @else
                                <span class="badge bg-light text-dark border px-2 py-1">{{ ucfirst($sub->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('platform.subscriptions.show', $sub) }}" class="btn btn-sm btn-outline-primary py-1 px-3">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No subscriptions match the selected criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscriptions->hasPages())
        <div class="p-3 border-top">
            {{ $subscriptions->links() }}
        </div>
    @endif
</div>
@endsection

