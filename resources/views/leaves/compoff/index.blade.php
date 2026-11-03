@extends('layouts.app')

@section('title', 'Comp-Off Administration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Comp-Off Accrual & Availing</h1>
        <p class="text-muted small mb-0">Credit compensatory off for verified holiday or weekly-off duty with 60-day expiry enforcement.</p>
    </div>
    <a href="{{ route('leaves.compoff.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-circle me-1"></i> Claim Comp-Off
    </a>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Work Date</th>
                    <th>Source Duty Type</th>
                    <th>Work Duration</th>
                    <th>Days Credited</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    @if(Auth::user()->isHr())
                        <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <div class="fw-semibold text-slate-900">{{ $rec->employee->first_name }} {{ $rec->employee->last_name }}</div>
                            <span class="badge bg-light text-dark font-monospace">{{ $rec->employee->employee_code }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $rec->qualifying_work_date->format('d M Y') }}</div>
                            <span class="text-muted small">{{ $rec->qualifying_work_date->format('l') }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $rec->source_type }}</span>
                        </td>
                        <td>{{ round($rec->eligible_work_minutes / 60, 1) }} hrs ({{ $rec->eligible_work_minutes }} mins)</td>
                        <td>
                            <span class="fw-bold text-teal">+{{ $rec->credited_days }} day</span>
                        </td>
                        <td>
                            <span class="small {{ $rec->isExpired() ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $rec->expiry_date->format('d M Y') }}
                            </span>
                        </td>
                        <td>
                            @if($rec->status === 'Approved')
                                <span class="status-pill status-pill-success"><span class="dot"></span>Approved</span>
                            @elseif($rec->status === 'Pending Approval')
                                <span class="status-pill status-pill-warning"><span class="dot"></span>Pending Approval</span>
                            @elseif($rec->status === 'Expired')
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Expired</span>
                            @else
                                <span class="status-pill status-pill-info"><span class="dot"></span>{{ $rec->status }}</span>
                            @endif
                        </td>
                        @if(Auth::user()->isHr())
                            <td class="text-end">
                                @if($rec->status === 'Pending Approval')
                                    <div class="btn-group btn-group-sm">
                                        <form action="{{ route('leaves.compoff.approve', $rec->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $rec->id }}">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade text-start" id="rejectModal{{ $rec->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('leaves.compoff.reject', $rec->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title font-bold text-danger">Reject Comp-Off Claim</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <label class="form-label small fw-semibold">Mandatory Rejection Reason <span class="text-danger">*</span></label>
                                                        <textarea name="reason" class="form-control" rows="3" required placeholder="Why is this comp-off claim rejected?"></textarea>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-danger btn-sm px-3">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">{{ $rec->approver?->name }}</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>
                            No comp-off records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection

