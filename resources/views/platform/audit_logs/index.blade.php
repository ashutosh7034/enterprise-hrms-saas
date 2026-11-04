@extends('layouts.platform')

@section('title', 'Platform Audit Logs | Platform Control Center')

@section('content')
<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Platform Audit Logs</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Platform Audit Trail & Security Logs</h3>
        <p class="text-muted mb-0" style="font-size: 13.5px;">High-assurance, tamper-evident ledger of tenant provisioning, subscription lifecycle events, and administrative security operations.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-2 px-3" style="font-size: 12px; border-radius: 8px;">
            <i class="bi bi-shield-lock-fill me-1"></i> Immutability Guaranteed
        </span>
    </div>
</div>

<!-- Filters Card -->
<div class="platform-card p-3 mb-4">
    <form method="GET" action="{{ route('platform.audit-logs.index') }}" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label text-muted small fw-semibold mb-1">Search Keywords / IP</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Filter by action, user, or IP..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">Filter by Tenant</label>
            <select name="tenant_id" class="form-select form-select-sm">
                <option value="">All Customer Tenants</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->name }} ({{ $t->tenant_code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">Filter by Action</label>
            <select name="action" class="form-select form-select-sm">
                <option value="">All Actions</option>
                @foreach($distinctActions as $act)
                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>
                        {{ $act }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1" style="border-radius: 6px; font-weight: 500;">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'tenant_id', 'action']))
                <a href="{{ route('platform.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;" title="Reset Filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="platform-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.04em;">
                <tr>
                    <th class="ps-4">Timestamp</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Target Tenant</th>
                    <th>IP Address</th>
                    <th class="pe-4">Metadata Payload</th>
                </tr>
            </thead>
            <tbody style="font-size: 13px;">
                @forelse($logs as $log)
                    <tr>
                        <td class="ps-4 text-nowrap">
                            <div class="fw-semibold text-dark">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-muted small">{{ $log->created_at->format('h:i:s A') }}</div>
                        </td>
                        <td>
                            @if($log->platformUser)
                                <div class="fw-semibold text-dark">{{ $log->platformUser->name }}</div>
                                <div class="text-muted small">{{ $log->platformUser->email }}</div>
                            @else
                                <span class="text-muted fst-italic">System Daemon</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                                if (str_contains($log->action, 'CREATE') || str_contains($log->action, 'ACTIVATE')) {
                                    $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                } elseif (str_contains($log->action, 'UPDATE') || str_contains($log->action, 'EXTEND')) {
                                    $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                } elseif (str_contains($log->action, 'SUSPEND') || str_contains($log->action, 'CANCEL')) {
                                    $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }} px-2 py-1 font-monospace" style="font-size: 11px;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            @if($log->tenant)
                                <a href="{{ route('platform.tenants.show', $log->tenant) }}" class="fw-semibold text-decoration-none">
                                    {{ $log->tenant->name }}
                                </a>
                                <div class="text-muted small font-monospace">{{ $log->tenant->tenant_code }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="code-pill">{{ $log->ip_address ?: '127.0.0.1' }}</span>
                        </td>
                        <td class="pe-4">
                            @if(!empty($log->metadata))
                                <div class="text-truncate" style="max-width: 320px;" title="{{ json_encode($log->metadata) }}">
                                    @foreach($log->metadata as $k => $v)
                                        <span class="badge bg-light text-dark border me-1" style="font-size: 10.5px; font-weight: normal;">
                                            <strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v) : $v }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="bi bi-shield-check fs-1"></i></div>
                            <h6 class="fw-bold text-dark mb-1">No Audit Log Records Found</h6>
                            <p class="text-muted small mb-0">Platform administrative operations and security lifecycle events will be recorded here automatically.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-white border-top py-3 px-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
