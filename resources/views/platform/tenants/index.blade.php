@extends('layouts.platform')

@section('title', 'Customer Tenants | Platform Control Center')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Customer Tenant Organizations</h3>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Kanvtech SaaS Platform — Customer tenancy isolation and group company oversight</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary d-flex align-items-center gap-2 py-2 px-3 fw-medium" style="font-size: 13.5px; border-radius: 8px;">
            <i class="bi bi-plus-circle"></i>
            <span>Register New Tenant</span>
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-medium">Total Tenants</div>
                    <h4 class="fw-bold mb-0 text-dark mt-1">{{ $counts['total'] }}</h4>
                </div>
                <div class="p-2 rounded bg-light text-primary">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-medium">Active Tenants</div>
                    <h4 class="fw-bold mb-0 text-success mt-1">{{ $counts['active'] }}</h4>
                </div>
                <div class="p-2 rounded bg-success-subtle text-success">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-medium">Suspended</div>
                    <h4 class="fw-bold mb-0 text-warning mt-1">{{ $counts['suspended'] }}</h4>
                </div>
                <div class="p-2 rounded bg-warning-subtle text-warning">
                    <i class="bi bi-pause-circle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="platform-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small fw-medium">Inactive</div>
                    <h4 class="fw-bold mb-0 text-secondary mt-1">{{ $counts['inactive'] }}</h4>
                </div>
                <div class="p-2 rounded bg-secondary-subtle text-secondary">
                    <i class="bi bi-slash-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="platform-card p-3 mb-4">
    <form method="GET" action="{{ route('platform.tenants.index') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, tenant code, or email..." value="{{ request('search') }}" style="font-size: 13.5px;">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select" style="font-size: 13.5px;">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended Only</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-dark px-3" style="font-size: 13.5px;">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary px-3" style="font-size: 13.5px;">Reset</a>
            @endif
        </div>
    </form>
</div>

<!-- Tenants Table -->
<div class="platform-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                    <th class="ps-4">Tenant Code</th>
                    <th>Customer Name</th>
                    <th>Slug</th>
                    <th>Group Structure</th>
                    <th>Primary Contact</th>
                    <th>Status</th>
                    <th>Onboarded</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody style="font-size: 13.5px;">
                @forelse($tenants as $tenant)
                    <tr>
                        <td class="ps-4">
                            <span class="code-pill fw-semibold">{{ $tenant->tenant_code }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $tenant->name }}</div>
                            @if($tenant->address)
                                <div class="text-muted small text-truncate" style="max-width: 200px;">{{ $tenant->address }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="text-muted" style="font-size: 12px;">{{ $tenant->slug }}</code>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 12px;">
                                <i class="bi bi-building me-1 text-primary"></i>
                                {{ $tenant->companies_count }} {{ Str::plural('Company', $tenant->companies_count) }}
                            </span>
                        </td>
                        <td>
                            @if($tenant->contact_name || $tenant->contact_email)
                                <div class="fw-medium text-dark">{{ $tenant->contact_name ?: '—' }}</div>
                                <div class="text-muted small">{{ $tenant->contact_email ?: 'No email' }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($tenant->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11.5px;">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i> Active
                                </span>
                            @elseif($tenant->status === 'suspended')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size: 11.5px;">
                                    <i class="bi bi-exclamation-circle-fill me-1" style="font-size: 8px;"></i> Suspended
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 11.5px;">
                                    <i class="bi bi-slash-circle me-1" style="font-size: 8px;"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size: 12.5px;">
                            {{ $tenant->created_at ? $tenant->created_at->format('d M Y') : '—' }}
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Tenant Overview" style="font-size: 12px;">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('platform.tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit Metadata" style="font-size: 12px;">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="mb-2"><i class="bi bi-buildings fs-1 text-secondary opacity-50"></i></div>
                            <h6 class="fw-semibold text-dark">No Customer Tenants Found</h6>
                            <p class="small text-muted mb-3">No tenant records match your search or filter criteria.</p>
                            <a href="{{ route('platform.tenants.create') }}" class="btn btn-sm btn-primary">Register First Tenant</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenants->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $tenants->links() }}
        </div>
    @endif
</div>
@endsection

