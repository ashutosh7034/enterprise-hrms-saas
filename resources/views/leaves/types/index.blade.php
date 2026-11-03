@extends('layouts.app')

@section('title', 'Leave Types Master')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Types Master</h1>
        <p class="text-muted small mb-0">Configure company-specific leave entitlements, accrual policies, and constraints.</p>
    </div>
    <a href="{{ route('leaves.types.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-circle me-1"></i> New Leave Type
    </a>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('leaves.types.index') }}" method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search by leave code or name...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
            @if(request()->anyFilled(['search', 'status']))
                <div class="col-md-2">
                    <a href="{{ route('leaves.types.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Code & Name</th>
                    <th>Type</th>
                    <th>Annual Quota</th>
                    <th>Accrual Rate</th>
                    <th>Sandwich Policy</th>
                    <th>Carry-Forward</th>
                    <th>Notice Period</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveTypes as $type)
                    <tr>
                        <td>
                            <div class="fw-semibold text-slate-800">{{ $type->name }}</div>
                            <span class="badge bg-light text-dark font-monospace">{{ $type->code }}</span>
                        </td>
                        <td>
                            @if($type->is_paid)
                                <span class="badge bg-success-subtle text-success">Paid</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Unpaid</span>
                            @endif
                            @if($type->is_compoff)
                                <span class="badge bg-info-subtle text-info">Comp-Off</span>
                            @endif
                        </td>
                        <td>{{ $type->annual_entitlement }} days</td>
                        <td>
                            @if($type->is_accrual_enabled)
                                <span>{{ $type->monthly_accrual_rate }} / mo</span>
                                <div class="text-muted small">({{ $type->accrual_frequency }})</div>
                            @else
                                <span class="text-muted">None</span>
                            @endif
                        </td>
                        <td>
                            @if($type->is_sandwich_enabled)
                                <span class="status-pill status-pill-warning"><span class="dot"></span>Enabled</span>
                            @else
                                <span class="text-muted small">Disabled</span>
                            @endif
                        </td>
                        <td>
                            @if($type->is_carry_forward_enabled)
                                <span>Max {{ $type->carry_forward_limit }} d</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            @if($type->is_applicable_in_notice_period)
                                <span class="badge bg-success-subtle text-success">Allowed</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Restricted</span>
                            @endif
                        </td>
                        <td>
                            @if($type->status === 'Active')
                                <span class="status-pill status-pill-success"><span class="dot"></span>Active</span>
                            @else
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('leaves.types.edit', $type->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-folder-x fs-3 d-block mb-2"></i>
                            No leave types configured for active company. Click "New Leave Type" to set one up.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaveTypes->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $leaveTypes->links() }}
        </div>
    @endif
</div>
@endsection

