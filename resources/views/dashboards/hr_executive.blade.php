@extends('layouts.app')

@section('title', 'HR Executive Operations Hub')

@section('styles')
<style>
    .dashboard-container {
        padding-bottom: 2rem;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    @media (max-width: 1400px) {
        .kpi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    .kpi-card {
        background: #FFFFFF;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        padding: 1.25rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-decoration: none;
        color: inherit;
        overflow: hidden;
    }

    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
        border-color: #CBD5E1;
        color: inherit;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .kpi-card.kpi-blue::before { background: #3B82F6; }
    .kpi-card.kpi-green::before { background: #10B981; }
    .kpi-card.kpi-orange::before { background: #F59E0B; }
    .kpi-card.kpi-purple::before { background: #8B5CF6; }
    .kpi-card.kpi-teal::before { background: #0D9488; }
    .kpi-card.kpi-pink::before { background: #EC4899; }

    .kpi-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .kpi-title {
        font-size: 13px;
        font-weight: 600;
        color: #64748B;
        text-transform: capitalize;
        letter-spacing: -0.01em;
        margin-bottom: 0;
    }

    .kpi-icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .kpi-card.kpi-blue .kpi-icon-badge { background: #EFF6FF; color: #3B82F6; }
    .kpi-card.kpi-green .kpi-icon-badge { background: #ECFDF5; color: #10B981; }
    .kpi-card.kpi-orange .kpi-icon-badge { background: #FFFBEB; color: #F59E0B; }
    .kpi-card.kpi-purple .kpi-icon-badge { background: #F5F3FF; color: #8B5CF6; }
    .kpi-card.kpi-teal .kpi-icon-badge { background: #F0FDFA; color: #0D9488; }
    .kpi-card.kpi-pink .kpi-icon-badge { background: #FDF2F8; color: #EC4899; }

    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #0F172A;
        line-height: 1.1;
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
    }

    .kpi-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11.5px;
        color: #64748B;
        margin-top: 0.25rem;
    }

    .op-card {
        background: #FFFFFF;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .op-card .card-header {
        background: transparent;
        border-bottom: 1px solid #F1F5F9;
        padding: 1rem 1.25rem;
    }

    .op-card .card-body {
        padding: 1.25rem;
        flex: 1 1 auto;
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">

    <!-- Top Dashboard Header & Actions -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">
                HR Executive Operations Hub 
                <span class="d-none">HR Operations Hub</span>
            </h4>
            <p class="text-muted small mb-0">
                Active Organization: <strong style="color: #0D9488;">{{ $activeCompany?->name }}</strong> ({{ $activeCompany?->code }}) • Daily operations, queues & verification pipeline.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('leaves.approvals.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-calendar-check me-1"></i> Leave Queue
            </a>
            <a href="{{ route('regularization.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i> Regularizations
            </a>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports Hub
            </a>
            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-dark px-3">
                <i class="bi bi-person-plus me-1"></i> Onboard Employee
            </a>
        </div>
    </div>

    <!-- ROW 1: 6 Vibrant Operational KPI Cards -->
    <div class="kpi-grid">
        <!-- Active Workforce -->
        <a href="{{ route('employees.index') }}" class="kpi-card kpi-blue">
            <div>
                <div class="kpi-header">
                    <h6 class="kpi-title">Active Workforce</h6>
                    <div class="kpi-icon-badge">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="kpi-value">{{ $metrics['total_workforce'] }}</div>
            </div>
            <div class="kpi-footer">
                <span>{{ $metrics['onboarding_pipeline'] }} in onboarding pipeline</span>
                <i class="bi bi-arrow-right opacity-50"></i>
            </div>
        </a>

        <!-- Present Today -->
        <a href="{{ route('attendance.index') }}" class="kpi-card kpi-green">
            <div>
                <div class="kpi-header">
                    <h6 class="kpi-title">Present Today</h6>
                    <div class="kpi-icon-badge">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
                <div class="kpi-value">{{ $metrics['present_today'] }}</div>
            </div>
            <div class="kpi-footer">
                <span>{{ $metrics['absent_today'] }} absent recorded</span>
                <i class="bi bi-arrow-right opacity-50"></i>
            </div>
        </a>

        <!-- eKYC Pending / Grace -->
        <a href="{{ route('employees.index', ['ekyc_status' => 'Pending Verification']) }}" class="kpi-card kpi-orange">
            <div>
                <div class="kpi-header">
                    <h6 class="kpi-title">eKYC & Grace Active</h6>
                    <div class="kpi-icon-badge">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                </div>
                <div class="kpi-value">{{ $metrics['ekyc_pending_review'] }}</div>
            </div>
            <div class="kpi-footer">
                <span>Action required for compliance</span>
                <i class="bi bi-arrow-right opacity-50"></i>
            </div>
        </a>

        <!-- Missing Punches -->
        <a href="{{ route('attendance.index') }}" class="kpi-card kpi-pink">
            <div>
                <div class="kpi-header">
                    <h6 class="kpi-title">Missing Punches</h6>
                    <div class="kpi-icon-badge">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                </div>
                <div class="kpi-value">{{ $metrics['missing_punch_today'] }}</div>
            </div>
            <div class="kpi-footer">
                <span>Unresolved muster records</span>
                <i class="bi bi-arrow-right opacity-50"></i>
            </div>
        </a>

        <!-- Leave Approvals Pending -->
        <a href="{{ route('leaves.approvals.index') }}" class="kpi-card kpi-purple">
            <div>
                <div class="kpi-header">
                    <h6 class="kpi-title">Pending Leaves</h6>
                    <div class="kpi-icon-badge">
                        <i class="bi bi-envelope-paper-fill"></i>
                    </div>
                </div>
                <div class="kpi-value">{{ $metrics['leave_approvals_pending'] }}</div>
            </div>
            <div class="kpi-footer">
                <span>Awaiting HR signoff</span>
                <i class="bi bi-arrow-right opacity-50"></i>
            </div>
        </a>

        <!-- Regularizations Pending -->
        <a href="{{ route('regularization.index') }}" class="kpi-card kpi-teal">
            <div>
                <div class="kpi-header">
                    <h6 class="kpi-title">Regularizations</h6>
                    <div class="kpi-icon-badge">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div class="kpi-value">{{ $metrics['regularizations_pending'] }}</div>
            </div>
            <div class="kpi-footer">
                <span>Muster adjustments</span>
                <i class="bi bi-arrow-right opacity-50"></i>
            </div>
        </a>
    </div>

    <!-- ROW 2: Headcount by Department & Pending HR Approvals -->
    <div class="row g-4 mb-4">
        <!-- Chart: Headcount by Department -->
        <div class="col-lg-6">
            <div class="op-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill me-2 text-teal"></i>Headcount by Department</h6>
                        <small class="text-muted">Active personnel distribution across units</small>
                    </div>
                    <a href="{{ route('admin.reports.show', 'HEADCOUNT_DEPARTMENT') }}" class="btn btn-sm btn-link p-0 text-decoration-none" style="color: #0D9488; font-size: 12.5px;">
                        Full Report <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 260px;">
                        <canvas id="deptHeadcountChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approvals & Operational Queue -->
        <div class="col-lg-6">
            <div class="op-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Leave Approvals</h6>
                        <small class="text-muted">Applications waiting for HR verification</small>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                        {{ $pendingLeaves->count() }} In Queue
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($pendingLeaves->isEmpty())
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                            <div class="small fw-semibold text-dark">All leave queues cleared!</div>
                            <div class="small text-muted">No pending employee leave requests for HR review.</div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Dates</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingLeaves as $leave)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $leave->employee?->full_name }}</div>
                                                <div class="text-muted" style="font-size: 11px;">{{ $leave->employee?->department?->name }} • {{ $leave->employee?->employee_code }}</div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border">{{ $leave->leaveType?->code ?? 'LV' }}</span></td>
                                            <td style="font-size: 12px;">{{ $leave->start_date?->format('d M') }} - {{ $leave->end_date?->format('d M') }} ({{ $leave->total_days }}d)</td>
                                            <td class="text-end">
                                                <a href="{{ route('leaves.approvals.index') }}" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 11.5px;">Review</a>
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

    <!-- ROW 3: eKYC Verification Pipeline & Upcoming Probation Evaluations -->
    <div class="row g-4 mb-4">
        <!-- eKYC Verification Queue -->
        <div class="col-lg-6">
            <div class="op-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check me-2 text-primary"></i>eKYC Verification Pipeline</h6>
                        <small class="text-muted">Staff requiring statutory document compliance</small>
                    </div>
                    <a href="{{ route('admin.reports.show', 'EKYC_STATUS') }}" class="btn btn-sm btn-link p-0 text-decoration-none" style="color: #0D9488; font-size: 12.5px;">
                        Full Audit <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($pendingEkycEmployees->isEmpty())
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check2-all fs-2 text-success d-block mb-2"></i>
                            <div class="small fw-semibold text-dark">eKYC Fully Compliant</div>
                            <div class="small text-muted">All active employees have verified statutory proofs.</div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingEkycEmployees as $pe)
                                        <tr>
                                            <td><code class="fw-semibold">{{ $pe->employee_code }}</code></td>
                                            <td class="fw-medium text-dark">{{ $pe->full_name }}</td>
                                            <td class="text-muted">{{ $pe->department?->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ $pe->ekyc_status === 'Grace Period Active' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}" style="font-size: 11px;">
                                                    {{ $pe->ekyc_status }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('employees.show', $pe) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11.5px;">Verify</a>
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
            <div class="op-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-danger"></i>Probation Evaluations Due (Next 30 Days)</h6>
                        <small class="text-muted">Staff nearing end of probation assessment period</small>
                    </div>
                    <a href="{{ route('admin.reports.show', 'PROBATION_DUE') }}" class="btn btn-sm btn-link p-0 text-decoration-none" style="color: #0D9488; font-size: 12.5px;">
                        View Register <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($upcomingProbations->isEmpty())
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-calendar-check fs-2 text-secondary d-block mb-2 opacity-50"></i>
                            <div class="small fw-semibold text-dark">No Probations Ending Soon</div>
                            <div class="small text-muted">No probation reviews due in the next 30 days.</div>
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
                                            <td class="fw-semibold text-dark">{{ $up->full_name }}</td>
                                            <td class="text-muted">{{ $up->department?->name ?? 'N/A' }}</td>
                                            <td class="text-danger fw-semibold">{{ $up->probation_end_date?->format('d M Y') }}</td>
                                            <td class="text-muted">{{ $up->reportingManager?->full_name ?? 'N/A' }}</td>
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
                        borderRadius: 6,
                        hoverBackgroundColor: '#0F766E'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` ${context.parsed.y} employees`
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { precision: 0 },
                            grid: { color: '#F1F5F9' }
                        },
                        x: { 
                            grid: { display: false } 
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
