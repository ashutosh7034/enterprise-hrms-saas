@extends('layouts.app')

@section('title', 'HR Executive Operations Hub')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">HR Executive Operations Hub</h4>
        <p class="text-muted small mb-0">Active Organization: <strong style="color: var(--brand-accent);">{{ $activeCompany?->name }}</strong> ({{ $activeCompany?->code }})</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports Hub
        </a>
        <a href="{{ route('employees.create') }}" class="btn btn-sm btn-brand">
            <i class="bi bi-person-plus me-1"></i> Onboard Employee
        </a>
    </div>
</div>

<!-- Daily Operational Pulse -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Active Workforce</div>
                    <h3 class="fw-bold mb-0 text-dark">{{ $metrics['total_workforce'] }}</h3>
                    <div class="text-muted small">Registered staff</div>
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
                    <div class="text-muted small">{{ $metrics['absent_today'] }} absent recorded</div>
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
                    <div class="text-muted small fw-medium mb-1">eKYC & Grace Active</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ $metrics['ekyc_pending_review'] }}</h3>
                    <div class="text-warning-emphasis small">Verification queue</div>
                </div>
                <div class="p-2 rounded bg-warning-subtle text-warning">
                    <i class="bi bi-shield-exclamation fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-danger">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Missing Punches</div>
                    <h3 class="fw-bold mb-0 text-danger">{{ $metrics['missing_punch_today'] }}</h3>
                    <div class="text-danger small">Requires attention</div>
                </div>
                <div class="p-2 rounded bg-danger-subtle text-danger">
                    <i class="bi bi-exclamation-triangle fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart: Headcount by Department -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-bar-chart-fill me-2 text-teal"></i>Headcount by Department</span>
                <a href="{{ route('admin.reports.show', 'HEADCOUNT_DEPARTMENT') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal">View Report</a>
            </div>
            <div class="card-body">
                <canvas id="deptHeadcountChart" style="max-height: 260px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Approvals & Operational Queue -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending HR Approvals</span>
                <span class="badge bg-warning-subtle text-warning">{{ $metrics['leave_approvals_pending'] + $metrics['regularizations_pending'] }} Requests</span>
            </div>
            <div class="card-body p-0">
                @if($pendingLeaves->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                        <div class="small fw-medium">All leave queues cleared! No pending requests for HR review.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingLeaves as $leave)
                                    <tr>
                                        <td>
                                            <div class="fw-medium text-dark">{{ $leave->employee?->full_name }}</div>
                                            <div class="text-muted" style="font-size: 11px;">{{ $leave->employee?->department?->name }}</div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $leave->leaveType?->code }}</span></td>
                                        <td style="font-size: 12px;">{{ $leave->start_date?->format('d M') }} - {{ $leave->end_date?->format('d M') }} ({{ $leave->total_days }}d)</td>
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
</div>

<div class="row g-4 mb-4">
    <!-- eKYC Verification Queue -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-shield-check me-2 text-primary"></i>eKYC Verification Pipeline</span>
                <a href="{{ route('admin.reports.show', 'EKYC_STATUS') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal">Full eKYC Audit</a>
            </div>
            <div class="card-body p-0">
                @if($pendingEkycEmployees->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check2-all fs-2 text-teal d-block mb-2"></i>
                        <div class="small fw-medium">All onboarded employees are fully compliant with verified eKYC!</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Staff Code</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingEkycEmployees as $pe)
                                    <tr>
                                        <td><code>{{ $pe->employee_code }}</code></td>
                                        <td class="fw-medium text-dark">{{ $pe->full_name }}</td>
                                        <td>{{ $pe->department?->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $pe->ekyc_status === 'Grace Period Active' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $pe->ekyc_status }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('employees.show', $pe) }}" class="btn btn-xs btn-outline-secondary" style="font-size: 11px; padding: 2px 8px;">Verify</a>
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

    <!-- Upcoming Probation Evaluations -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-clock-history me-2 text-danger"></i>Probation Evaluations Due (Next 30 Days)</span>
                <a href="{{ route('admin.reports.show', 'PROBATION_DUE') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal">View Due</a>
            </div>
            <div class="card-body p-0">
                @if($upcomingProbations->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-check fs-2 text-secondary d-block mb-2"></i>
                        <div class="small fw-medium">No probation reviews due in the next 30 days.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Probation End</th>
                                    <th>Manager</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingProbations as $up)
                                    <tr>
                                        <td class="fw-medium text-dark">{{ $up->full_name }}</td>
                                        <td>{{ $up->department?->name ?? 'N/A' }}</td>
                                        <td class="text-danger fw-semibold">{{ $up->probation_end_date?->format('d M Y') }}</td>
                                        <td>{{ $up->reportingManager?->full_name ?? 'N/A' }}</td>
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctxDept = document.getElementById('deptHeadcountChart');
        if (ctxDept) {
            new Chart(ctxDept, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($deptChartLabels) !!},
                    datasets: [{
                        label: 'Active Staff',
                        data: {!! json_encode($deptChartValues) !!},
                        backgroundColor: '#0D9488',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endsection
