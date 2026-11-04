@extends('layouts.platform')

@section('title', 'Invoices & Billing | Platform Control Center')

@section('content')
<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Invoices</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Subscription Invoices & Billing Records</h3>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Audit financial invoices, payment receipts, and collection settlement logs.</p>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Total Invoices</div>
            <h4 class="fw-bold mb-0 text-dark">{{ $counts['total'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Paid Invoices</div>
            <h4 class="fw-bold mb-0 text-success">{{ $counts['paid'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Pending Collection</div>
            <h4 class="fw-bold mb-0 text-warning">{{ $counts['pending'] }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="text-muted small fw-medium mb-1">Total Settled Revenue</div>
            <h4 class="fw-bold mb-0 text-primary">₹{{ number_format($counts['total_revenue'], 2) }}</h4>
        </div>
    </div>
</div>

<!-- Invoices Table -->
<div class="platform-card mb-4">
    <div class="p-4 border-bottom">
        <form method="GET" action="{{ route('platform.invoices.index') }}" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by invoice number or tenant..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('platform.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                <tr>
                    <th class="ps-4">Invoice #</th>
                    <th>Customer Tenant</th>
                    <th>Plan</th>
                    <th>Issued At</th>
                    <th>Due At</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody style="font-size: 13.5px;">
                @forelse($invoices as $inv)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('platform.invoices.show', $inv) }}" class="fw-semibold text-decoration-none">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $inv->tenant->name }}</div>
                            <span class="code-pill">{{ $inv->tenant->tenant_code }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $inv->subscription->plan->name ?? '—' }}</span>
                        </td>
                        <td class="text-muted" style="font-size: 12.5px;">{{ $inv->issued_at ? $inv->issued_at->format('d M Y') : '—' }}</td>
                        <td class="text-muted" style="font-size: 12.5px;">{{ $inv->due_at ? $inv->due_at->format('d M Y') : '—' }}</td>
                        <td class="fw-bold text-dark">{{ $inv->currency }} {{ number_format($inv->amount, 2) }}</td>
                        <td>
                            @if($inv->status === 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Paid</span>
                            @elseif($inv->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Pending</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">{{ ucfirst($inv->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('platform.invoices.show', $inv) }}" class="btn btn-sm btn-outline-primary py-1 px-3">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No invoices match the selected criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div class="p-3 border-top">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection

