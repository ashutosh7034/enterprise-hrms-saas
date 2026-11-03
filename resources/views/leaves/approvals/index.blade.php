@extends('layouts.app')

@section('title', 'Leave Approvals Queue')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Approvals Hub</h1>
        <p class="text-muted small mb-0">Review pending team leave requests, inspect sandwich impact, and execute approval decisions.</p>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Duration</th>
                    <th>Payable Days</th>
                    <th>Reason</th>
                    <th>Current Stage</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingApplications as $app)
                    <tr>
                        <td>
                            <a href="{{ route('leaves.applications.show', $app->id) }}" class="fw-bold text-decoration-none text-slate-800">
                                {{ $app->application_number }}
                            </a>
                            <div class="text-muted small">{{ $app->created_at->diffForHumans() }}</div>
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
                            <span class="fw-semibold">{{ $app->start_date->format('d M') }}</span> - 
                            <span class="fw-semibold">{{ $app->end_date->format('d M Y') }}</span>
                            @if($app->is_half_day)
                                <span class="badge bg-info-subtle text-info small ms-1">{{ $app->half_day_type }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-slate-900">{{ $app->calculated_days }} d</span>
                            @if($app->calculated_days > $app->requested_days)
                                <div class="badge bg-warning-subtle text-warning-emphasis smaller">Sandwich (+{{ $app->calculated_days - $app->requested_days }}d)</div>
                            @endif
                        </td>
                        <td>
                            <div class="text-truncate text-muted small" style="max-width: 200px;" title="{{ $app->reason }}">{{ $app->reason }}</div>
                        </td>
                        <td>
                            <span class="status-pill status-pill-warning"><span class="dot"></span>{{ $app->status }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('leaves.applications.show', $app->id) }}" class="btn btn-sm btn-accent">
                                Review & Decide
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-check-all fs-2 text-success d-block mb-2"></i>
                            All caught up! No pending leave applications requiring your approval.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pendingApplications->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $pendingApplications->links() }}
        </div>
    @endif
</div>
@endsection

