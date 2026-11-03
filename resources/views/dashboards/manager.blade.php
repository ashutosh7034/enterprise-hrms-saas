@extends('layouts.app')

@section('title', 'Manager Self-Service (MSS)')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Manager Self-Service (MSS)</h4>
        <p class="text-muted small mb-0">Supervisor Team Operations Hub • <strong>{{ $activeCompany?->name }}</strong></p>
    </div>
    <div>
        <span class="badge bg-secondary p-2" style="font-size: 11px;">
            <i class="bi bi-person-check me-1"></i> Reporting Manager Mode
        </span>
    </div>
</div>

<!-- Team Presence Summary Strip (Real Database Driven) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Direct Reports</div>
                    <h3 class="fw-bold mb-0 text-dark">{{ $metrics['team_members'] }}</h3>
                    <div class="text-muted small">Assigned team staff</div>
                </div>
                <div class="p-2 rounded bg-light text-primary">
                    <i class="bi bi-people fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Present Today</div>
                    <h3 class="fw-bold mb-0 text-success">{{ $metrics['present_today'] }}</h3>
                    <div class="text-muted small">Active on shifts</div>
                </div>
                <div class="p-2 rounded bg-success-subtle text-success">
                    <i class="bi bi-person-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">On Approved Leave</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ $metrics['on_leave'] }}</h3>
                    <div class="text-muted small">Planned absence</div>
                </div>
                <div class="p-2 rounded bg-warning-subtle text-warning">
                    <i class="bi bi-calendar-event fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Pending Approvals</div>
                    <h3 class="fw-bold mb-0 text-primary">{{ $metrics['pending_approvals'] + $metrics['pending_regularizations'] }}</h3>
                    <div class="text-muted small">Leaves & regularizations</div>
                </div>
                <div class="p-2 rounded bg-info-subtle text-info">
                    <i class="bi bi-hourglass-split fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Team Leave Approvals -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-calendar-check me-2 text-teal"></i>Team Leave Requests Awaiting Action</span>
                <span class="badge bg-warning-subtle text-warning">{{ $teamLeaveRequests->count() }} Pending</span>
            </div>
            <div class="card-body p-0">
                @if($teamLeaveRequests->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check2-circle fs-2 text-success d-block mb-2"></i>
                        <div class="small fw-medium">No pending leave requests from your reporting team.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Team Member</th>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamLeaveRequests as $req)
                                    <tr>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $req->employee?->full_name }}</div>
                                            <div class="text-muted" style="font-size: 11px;">{{ $req->employee?->designation?->name }}</div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $req->leaveType?->code }}</span></td>
                                        <td style="font-size: 12px;">{{ $req->start_date?->format('d M') }} - {{ $req->end_date?->format('d M') }}</td>
                                        <td><strong>{{ $req->total_days }}</strong></td>
                                        <td>
                                            <a href="{{ route('leaves.approvals.index') }}" class="btn btn-xs btn-outline-primary" style="font-size: 11px; padding: 2px 8px;">Review</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Team Regularizations -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Attendance Regularizations</span>
                <span class="badge bg-info-subtle text-info">{{ $teamRegularizations->count() }} Requests</span>
            </div>
            <div class="card-body p-0">
                @if($teamRegularizations->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-clock-history fs-2 text-secondary d-block mb-2"></i>
                        <div class="small fw-medium">No pending regularization requests from your reporting team.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamRegularizations as $reg)
                                    <tr>
                                        <td class="fw-medium text-dark">{{ $reg->employee?->full_name }}</td>
                                        <td>{{ $reg->attendance_date?->format('d M Y') }}</td>
                                        <td class="text-truncate" style="max-width: 150px;">{{ $reg->reason }}</td>
                                        <td>
                                            <a href="{{ route('regularization.index') }}" class="btn btn-xs btn-outline-secondary" style="font-size: 11px; padding: 2px 8px;">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- My Reporting Team Roster -->
<div class="card card-custom mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-dark"><i class="bi bi-people-fill me-2 text-teal"></i>Reporting Team Members ({{ $teamMembers->count() }})</span>
        <a href="{{ route('admin.reports.show', 'MASTER_EMPLOYEE') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal">Team Directory Report</a>
    </div>
    <div class="card-body p-0">
        @if($teamMembers->isEmpty())
            <div class="p-4 text-center text-muted">
                <i class="bi bi-people fs-2 text-secondary d-block mb-2"></i>
                <div class="small fw-medium">No direct subordinates are currently mapped to your managerial profile.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Staff Code</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teamMembers as $member)
                            <tr>
                                <td><code>{{ $member->employee_code }}</code></td>
                                <td class="fw-medium text-dark">{{ $member->full_name }}</td>
                                <td>{{ $member->designation?->name ?? 'N/A' }}</td>
                                <td>{{ $member->department?->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $member->status === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $member->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
