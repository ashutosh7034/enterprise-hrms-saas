@extends('layouts.app')

@section('title', 'Overtime Approvals — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Overtime Approvals</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Review calculated overtime minutes, evaluate block rounding & approve values for Phase 5 Payroll export.</p>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('overtime.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="Pending" {{ request('status', 'Pending') === 'Pending' ? 'selected' : '' }}>Pending Approvals</option>
                    <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved Records</option>
                    <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected Records</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color: var(--brand-primary-dark);">Filter</button>
                <a href="{{ route('overtime.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>OT Date & Employee</th>
                    <th>Shift & Day Type</th>
                    <th>Raw Calculated</th>
                    <th>Configured Rounding</th>
                    <th>Approved Minutes</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $rec->employee?->full_name }}</div>
                            <small class="text-muted">{{ $rec->employee?->employee_code }} • Date: <span class="fw-medium text-dark">{{ $rec->ot_date->format('d M Y') }}</span></small>
                        </td>
                        <td>
                            <div>{{ $rec->shift?->name ?? 'Standard Shift' }}</div>
                            <span class="badge {{ $rec->day_type === 'Shutdown' ? 'bg-secondary' : ($rec->day_type === 'Weekly Off' ? 'bg-info text-dark' : 'bg-light text-dark border') }}" style="font-size: 10.5px;">
                                {{ $rec->day_type }} Day
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ intdiv($rec->raw_calculated_minutes, 60) }}h {{ $rec->raw_calculated_minutes % 60 }}m</div>
                            <small class="text-muted">Exact: {{ $rec->raw_calculated_minutes }} mins</small>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ intdiv($rec->rounded_ot_minutes, 60) }}h {{ $rec->rounded_ot_minutes % 60 }}m</div>
                            <small class="text-muted">15-min Block: {{ $rec->rounded_ot_minutes }}m</small>
                        </td>
                        <td>
                            @if($rec->status === 'Approved')
                                <div class="fw-bold text-success">{{ intdiv($rec->approved_minutes, 60) }}h {{ $rec->approved_minutes % 60 }}m</div>
                                <small class="text-muted">By: {{ $rec->approver?->name }}</small>
                            @else
                                <span class="text-muted small">Pending approval</span>
                            @endif
                        </td>
                        <td>
                            @if($rec->status === 'Approved')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Approved</span>
                            @elseif($rec->status === 'Rejected')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Rejected</span>
                                @if($rec->rejection_reason)
                                    <div class="text-danger small" style="font-size: 11px;">{{ $rec->rejection_reason }}</div>
                                @endif
                            @else
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($rec->status === 'Pending')
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveOtModal_{{ $rec->id }}">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectOtModal_{{ $rec->id }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>

                                <!-- Approve Modal -->
                                <div class="modal fade text-start" id="approveOtModal_{{ $rec->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('overtime.approve', $rec->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Approve Overtime Minutes</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted small">Confirm or adjust approved overtime minutes for <strong>{{ $rec->employee?->full_name }}</strong> on {{ $rec->ot_date->format('d M Y') }}.</p>
                                                    <div class="mb-3">
                                                        <label class="form-label required">Approved Minutes</label>
                                                        <input type="number" name="approved_minutes" class="form-control" value="{{ $rec->rounded_ot_minutes }}" required>
                                                        <small class="text-muted">Calculated rounded: {{ $rec->rounded_ot_minutes }} minutes</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Manager Remarks (Optional)</label>
                                                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Critical furnace batch completion verified"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Approve Overtime</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade text-start" id="rejectOtModal_{{ $rec->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('overtime.reject', $rec->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Reject Overtime</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label required">Rejection Reason</label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Provide mandatory justification for rejecting overtime..." required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">Processed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-hourglass d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No overtime records found matching filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $records->links() }}
        </div>
    @endif
</div>
@endsection

