@extends('layouts.app')

@section('title', 'Plant Shutdown Details — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('plant-shutdowns.index') }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Shutdowns
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <div>
            <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">{{ $plantShutdown->title }}</h1>
            <p class="text-muted mb-0" style="font-size: 13.5px;">
                Code: <span class="fw-bold font-monospace text-dark">{{ $plantShutdown->shutdown_code }}</span> • Site: <span class="fw-medium text-dark">{{ $plantShutdown->location?->name }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($plantShutdown->status === 'Pending')
                <form action="{{ route('plant-shutdowns.approve', $plantShutdown->id) }}" method="POST" onsubmit="return confirm('Approve this plant shutdown and update daily muster for affected employees?');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Approve Shutdown
                    </button>
                </form>
            @endif

            @if(in_array($plantShutdown->status, ['Pending', 'Approved']))
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelShutdownModal">
                    <i class="bi bi-x-circle me-1"></i> Cancel Shutdown
                </button>
            @endif

            @if($plantShutdown->status === 'Approved')
                <button type="button" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);" data-bs-toggle="modal" data-bs-target="#nominateCrewModal">
                    <i class="bi bi-person-gear me-1"></i> Nominate Working Crew
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-body p-4">
                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Shutdown Metadata</h6>
                <div class="list-group list-group-flush border-top border-bottom my-3">
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Status</span>
                        <span class="fw-bold">{{ $plantShutdown->status }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Shutdown Type</span>
                        <span class="fw-semibold">{{ $plantShutdown->shutdown_type }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Start Date</span>
                        <span class="fw-semibold">{{ $plantShutdown->start_date->format('d M Y') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">End Date</span>
                        <span class="fw-semibold">{{ $plantShutdown->end_date->format('d M Y') }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Compensation</span>
                        <span class="badge bg-light text-dark border">{{ $plantShutdown->compensation_rule }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Requested By</span>
                        <span class="text-dark">{{ $plantShutdown->requester?->name }}</span>
                    </div>
                    @if($plantShutdown->approved_by)
                        <div class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Approved By</span>
                            <span class="text-success fw-medium">{{ $plantShutdown->approver?->name }}</span>
                        </div>
                    @endif
                    @if($plantShutdown->cancelled_by)
                        <div class="list-group-item d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Cancelled By</span>
                            <span class="text-danger fw-medium">{{ $plantShutdown->canceller?->name }}</span>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="text-muted small">Technical Reason:</div>
                    <div class="p-2 bg-light rounded small mt-1">{{ $plantShutdown->reason }}</div>
                </div>

                @if($plantShutdown->cancellation_reason)
                    <div class="alert alert-danger p-2 small mb-0">
                        <strong>Cancellation Justification:</strong> {{ $plantShutdown->cancellation_reason }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Nominated Working Crew -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tools me-2 text-warning"></i>Nominated Working / Maintenance Crew</h6>
                <span class="badge bg-light text-dark border">{{ $plantShutdown->crewNominations->count() }} staff nominated</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Work Date</th>
                            <th>Designated Duty</th>
                            <th>Compensation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plantShutdown->crewNominations as $crew)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $crew->employee?->full_name }}</div>
                                    <small class="text-muted">{{ $crew->employee?->employee_code }}</small>
                                </td>
                                <td>{{ $crew->work_date->format('d M Y') }}</td>
                                <td>{{ $crew->role_description ?: 'General Maintenance' }}</td>
                                <td><span class="badge bg-info-subtle text-dark border">{{ $crew->compensation_type }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No maintenance crew nominated yet for this shutdown window.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Affected Workforce Scope Preview -->
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people me-2 text-primary"></i>Impacted Workforce ({{ $affectedEmployees->count() }} Employees)</h6>
                <span class="badge bg-secondary text-white">Safe Attendance Guard Active</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th>Employee Code & Name</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Designation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($affectedEmployees->take(20) as $emp)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $emp->full_name }}</div>
                                    <small class="text-muted font-monospace">{{ $emp->employee_code }}</small>
                                </td>
                                <td>{{ $emp->department?->name }}</td>
                                <td>{{ $emp->category?->name }}</td>
                                <td>{{ $emp->designation?->title }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No active employees matched the defined scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($affectedEmployees->count() > 20)
                <div class="card-footer bg-white text-muted small text-center py-2">
                    Showing first 20 of {{ $affectedEmployees->count() }} impacted employees.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Cancel Shutdown Modal -->
<div class="modal fade" id="cancelShutdownModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('plant-shutdowns.cancel', $plantShutdown->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cancel Plant Shutdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Cancelling this shutdown will reverse the <code>Site Shutdown</code> attendance status and trigger automatic recalculation of affected workforce muster.</p>
                    <div class="mb-3">
                        <label class="form-label required">Cancellation Justification</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Provide mandatory justification for cancelling shutdown..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Nominate Maintenance Crew Modal -->
<div class="modal fade" id="nominateCrewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('plant-shutdowns.crew', $plantShutdown->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Nominate Maintenance Crew</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Select Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">-- Choose Employee at Site --</option>
                            @foreach($siteEmployees as $se)
                                <option value="{{ $se->id }}">{{ $se->full_name }} ({{ $se->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Work Date</label>
                        <input type="date" name="work_date" class="form-control" min="{{ $plantShutdown->start_date->format('Y-m-d') }}" max="{{ $plantShutdown->end_date->format('Y-m-d') }}" value="{{ $plantShutdown->start_date->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role / Maintenance Duty</label>
                        <input type="text" name="role_description" class="form-control" placeholder="e.g. Electrical substation monitoring & pump repair">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Compensation Type</label>
                        <select name="compensation_type" class="form-select" required>
                            <option value="Overtime Pay">Overtime Pay (1.5x / 2.0x)</option>
                            <option value="Comp-Off Credit">Comp-Off Credit (1.0 Day)</option>
                            <option value="Special Site Allowance">Special Site Allowance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Save Nomination</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

