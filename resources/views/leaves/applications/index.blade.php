@extends('layouts.app')

@section('title', 'Leave Applications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Applications</h1>
        <p class="text-muted small mb-0">Track leave requests, sandwich day calculations, and approval workflows.</p>
    </div>
    <div class="d-flex gap-2">
        @if(Auth::user()->isManager() || Auth::user()->isHr())
            <a href="{{ route('leaves.approvals.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-inbox me-1"></i> Approvals Queue
            </a>
        @endif
        <a href="{{ route('leaves.applications.create') }}" class="btn btn-accent">
            <i class="bi bi-calendar-plus me-1"></i> Apply for Leave
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('leaves.applications.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['Pending Manager', 'Pending HR', 'Approved', 'Rejected', 'Cancelled', 'Withdrawn'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="leave_type_id" class="form-select form-select-sm">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
            @if(request()->anyFilled(['status', 'leave_type_id']))
                <div class="col-md-2">
                    <a href="{{ route('leaves.applications.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
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
                    <th>App Number</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Duration</th>
                    <th>Days</th>
                    <th>Stage</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>
                            <a href="{{ route('leaves.applications.show', $app->id) }}" class="fw-bold text-decoration-none text-slate-800">
                                {{ $app->application_number }}
                            </a>
                            <div class="text-muted small">{{ $app->created_at->format('d M Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-slate-900">{{ $app->employee->first_name }} {{ $app->employee->last_name }}</div>
                            <span class="badge bg-light text-dark font-monospace">{{ $app->employee->employee_code }}</span>
                            <span class="text-muted small ms-1">{{ $app->employee->department?->name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace">{{ $app->leaveType->code }}</span>
                            <div class="text-muted small">{{ $app->leaveType->name }}</div>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $app->start_date->format('d M Y') }}</span>
                            <span class="text-muted small">to</span>
                            <span class="fw-semibold">{{ $app->end_date->format('d M Y') }}</span>
                            @if($app->is_half_day)
                                <span class="badge bg-info-subtle text-info small ms-1">{{ $app->half_day_type }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-slate-900">{{ $app->calculated_days }} d</div>
                            @if($app->calculated_days > $app->requested_days)
                                <span class="badge bg-warning-subtle text-warning-emphasis small" title="Sandwich days added">
                                    <i class="bi bi-layers"></i> Sandwich
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $app->current_stage }}</span>
                        </td>
                        <td>
                            @if($app->status === 'Approved')
                                <span class="status-pill status-pill-success"><span class="dot"></span>Approved</span>
                            @elseif(str_starts_with($app->status, 'Pending'))
                                <span class="status-pill status-pill-warning"><span class="dot"></span>{{ $app->status }}</span>
                            @elseif($app->status === 'Rejected')
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Rejected</span>
                            @elseif($app->status === 'Cancelled')
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Cancelled</span>
                            @else
                                <span class="status-pill status-pill-info"><span class="dot"></span>{{ $app->status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('leaves.applications.show', $app->id) }}" class="btn btn-sm btn-outline-secondary">
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar2-x fs-3 d-block mb-2"></i>
                            No leave applications found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $applications->links() }}
        </div>
    @endif
</div>
@endsection

