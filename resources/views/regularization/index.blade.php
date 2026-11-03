@extends('layouts.app')

@section('title', 'Attendance Regularization — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Attendance Regularization</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Correct missing punches, late arrivals, on-duty travel & biometric device errors through multi-level approval workflows.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);" data-bs-toggle="modal" data-bs-target="#applyRegularizationModal">
            <i class="bi bi-pencil-square me-1"></i> Apply Regularization
        </button>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('regularization.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" {{ !request('status') ? 'selected' : '' }}>Pending Approvals (Manager / HR)</option>
                    <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved Requests</option>
                    <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected Requests</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('regularization.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Employee & Date</th>
                    <th>Correction Type</th>
                    <th>Requested Timings</th>
                    <th>Mandatory Reason</th>
                    <th>Approval Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($regularizations as $reg)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $reg->employee?->full_name }}</div>
                            <small class="text-muted">{{ $reg->employee?->employee_code }} • Date: <span class="fw-medium text-dark">{{ $reg->attendance_date->format('d M Y') }}</span></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $reg->regularization_type }}</span>
                            <div class="text-muted" style="font-size: 11px;">Original: {{ $reg->original_status }}</div>
                        </td>
                        <td>
                            <div><span class="text-muted small">IN:</span> <span class="fw-bold">{{ $reg->requested_in_time ? $reg->requested_in_time->format('H:i') : '--:--' }}</span></div>
                            <div><span class="text-muted small">OUT:</span> <span class="fw-bold">{{ $reg->requested_out_time ? $reg->requested_out_time->format('H:i') : '--:--' }}</span></div>
                        </td>
                        <td>
                            <div class="small text-dark" style="max-width: 250px;">{{ $reg->reason }}</div>
                            <small class="text-muted">Applied by: {{ $reg->applicant?->name }}</small>
                        </td>
                        <td>
                            @if($reg->status === 'Approved')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Approved</span>
                                <div class="text-muted small" style="font-size: 10px;">By HR: {{ $reg->hr?->name }}</div>
                            @elseif($reg->status === 'Pending Manager')
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending Manager</span>
                            @elseif($reg->status === 'Pending HR')
                                <span class="status-pill status-pill-info"><span class="dot"></span> Pending HR</span>
                                <div class="text-muted small" style="font-size: 10px;">Manager Approved</div>
                            @elseif($reg->status === 'Rejected')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Rejected</span>
                            @else
                                <span class="badge bg-secondary text-white">{{ $reg->status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <!-- Manager Decision -->
                            @if($reg->status === 'Pending Manager')
                                <form action="{{ route('regularization.manager', $reg->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="Approve">
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-lg"></i> Manager Pass
                                    </button>
                                </form>
                                <form action="{{ route('regularization.manager', $reg->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="Reject">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endif

                            <!-- HR Final Approval -->
                            @if(in_array($reg->status, ['Pending HR', 'Pending Manager']))
                                <form action="{{ route('regularization.hr', $reg->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="Approve">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-circle me-1"></i> HR Final Approve
                                    </button>
                                </form>
                                <form action="{{ route('regularization.hr', $reg->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="decision" value="Reject">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                </form>
                            @endif

                            @if($reg->status === 'Approved')
                                <span class="badge bg-success-subtle text-success">Muster Updated</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-pencil-square d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No attendance regularization requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($regularizations->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $regularizations->links() }}
        </div>
    @endif
</div>

<!-- Apply Regularization Modal -->
<div class="modal fade" id="applyRegularizationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('regularization.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Apply Attendance Regularization</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Select Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Choose Employee --</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Attendance Date</label>
                        <input type="date" name="attendance_date" class="form-control" max="{{ now()->toDateString() }}" value="{{ now()->subDay()->toDateString() }}" required>
                        <small class="text-muted">Regularization cannot be submitted for future dates.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Correction Type</label>
                        <select name="regularization_type" class="form-select" required>
                            <option value="Missing IN Punch">Missing IN Punch (Forgot Check-in)</option>
                            <option value="Missing OUT Punch">Missing OUT Punch (Forgot Check-out)</option>
                            <option value="Late Arrival">Late Arrival (Traffic / Official Duty)</option>
                            <option value="Early Departure">Early Departure (Approved Emergency)</option>
                            <option value="Business Travel / On Duty">Business Travel / On Duty (OD)</option>
                            <option value="Biometric Device Failure">Biometric Device Hardware Failure</option>
                            <option value="Attendance Correction">General Attendance Correction</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Requested Check-in</label>
                            <input type="datetime-local" name="requested_in_time" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Requested Check-out</label>
                            <input type="datetime-local" name="requested_out_time" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Mandatory Reason / Justification</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Provide clear explanation for regularization request..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

