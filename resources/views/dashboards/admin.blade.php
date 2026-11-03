@extends('layouts.app')

@section('title', 'HO Admin Dashboard')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Head Office Admin Hub <span class="d-none">HR & Head Office Admin Hub</span></h4>
        <p class="text-muted small mb-0">Active Organization: <strong style="color: var(--brand-accent);">{{ $activeCompany?->name }}</strong> ({{ $activeCompany?->code }})</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('locations.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-geo-alt me-1"></i> Locations
        </a>
        <a href="{{ route('departments.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> Departments
        </a>
        <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-brand">
            <i class="bi bi-shield-check me-1"></i> Audit Trail
        </a>
    </div>
</div>

<!-- Multi-Company Overview Banner -->
<div class="card card-custom mb-4" style="border-left: 4px solid var(--brand-accent);">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge bg-secondary mb-1" style="font-size: 10px;">MULTI-COMPANY DATA ISOLATION ACTIVE</span>
                <div class="fw-semibold text-dark">Currently filtering all organizational records for: {{ $activeCompany?->name }}</div>
                <div class="text-muted small">Switch company from the top bar to inspect other enterprise entities safely.</div>
            </div>
            <div class="d-flex gap-3">
                @foreach($companies as $comp)
                    <div class="text-center px-3 py-1 rounded bg-light border">
                        <div class="small fw-bold text-dark">{{ $comp->code }}</div>
                        <div style="font-size: 11px; color: #64748B;">{{ $comp->locations_count }} Locs • {{ $comp->departments_count }} Depts</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Phase 2 Workforce, Compliance & Assets Operational Grid -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('employees.index') }}" class="text-decoration-none">
            <div class="card card-custom p-3 h-100 hover-shadow">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Total Active Workforce</div>
                        <h3 class="fw-bold mb-0" style="color: #0F172A;">{{ $metrics['total_employees'] }}</h3>
                        <div class="text-muted" style="font-size: 11.5px;">{{ $activeCompany?->code }} personnel roster</div>
                    </div>
                    <div class="p-2 rounded bg-light text-primary">
                        <i class="bi bi-people fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('employees.index', ['ekyc_status' => 'Pending Verification']) }}" class="text-decoration-none">
            <div class="card card-custom p-3 h-100 hover-shadow">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">eKYC Pending / Grace</div>
                        <h3 class="fw-bold mb-0" style="color: #D97706;">{{ $metrics['ekyc_pending'] + $metrics['ekyc_grace_active'] }}</h3>
                        <div class="text-warning-emphasis" style="font-size: 11.5px;">{{ $metrics['ekyc_grace_active'] }} grace period active</div>
                    </div>
                    <div class="p-2 rounded bg-warning-subtle text-warning">
                        <i class="bi bi-shield-exclamation fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('blacklist.index') }}" class="text-decoration-none">
            <div class="card card-custom p-3 h-100 hover-shadow">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Central Blacklist</div>
                        <h3 class="fw-bold mb-0" style="color: #DC2626;">{{ $metrics['central_blacklist_active'] }}</h3>
                        <div class="text-danger" style="font-size: 11.5px;">{{ $metrics['pending_match_reviews'] }} pending match reviews</div>
                    </div>
                    <div class="p-2 rounded bg-danger-subtle text-danger">
                        <i class="bi bi-shield-slash fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-lg-3">
        <a href="{{ route('assets.index') }}" class="text-decoration-none">
            <div class="card card-custom p-3 h-100 hover-shadow">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-medium mb-1">Asset Fleet & Tools</div>
                        <h3 class="fw-bold mb-0" style="color: #0D9488;">{{ $metrics['total_assets'] }}</h3>
                        <div class="text-teal" style="font-size: 11.5px;">{{ $metrics['allocated_assets'] }} allocated in field</div>
                    </div>
                    <div class="p-2 rounded bg-teal-subtle text-teal">
                        <i class="bi bi-laptop fs-5"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Key Master Metrics Grid -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Locations & Plants</div>
                    <h3 class="fw-bold mb-0" style="color: #1E293B;">{{ $metrics['total_locations'] }}</h3>
                    <div class="text-muted" style="font-size: 11.5px;">{{ $metrics['total_sites'] }} Ground staff sites</div>
                </div>
                <div class="p-2 rounded bg-light text-primary">
                    <i class="bi bi-geo-alt fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Departments</div>
                    <h3 class="fw-bold mb-0" style="color: #1E293B;">{{ $metrics['total_departments'] }}</h3>
                    <div class="text-muted" style="font-size: 11.5px;">Operational Units</div>
                </div>
                <div class="p-2 rounded bg-light text-success">
                    <i class="bi bi-diagram-3 fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Employee Categories</div>
                    <h3 class="fw-bold mb-0" style="color: #1E293B;">{{ $metrics['total_categories'] }}</h3>
                    <div class="text-muted" style="font-size: 11.5px;">Staff, Worker, Ground, Exec</div>
                </div>
                <div class="p-2 rounded bg-light text-info">
                    <i class="bi bi-tags fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Contractor Agencies</div>
                    <h3 class="fw-bold mb-0" style="color: #1E293B;">{{ $metrics['total_contractors'] }}</h3>
                    <div class="text-muted" style="font-size: 11.5px;">Verified partners</div>
                </div>
                <div class="p-2 rounded bg-light text-warning">
                    <i class="bi bi-briefcase fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Immutable Audit Trail Activity -->
<!-- Enterprise Analytics Visualizations (Real Database Aggregations) -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-bar-chart-fill me-2 text-teal"></i>Headcount by Department</span>
                <a href="{{ route('admin.reports.show', 'HEADCOUNT_DEPARTMENT') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal">View Full Report</a>
            </div>
            <div class="card-body">
                <canvas id="adminDeptChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>6-Month Workforce Growth & Joiners</span>
                <a href="{{ route('admin.reports.show', 'NEW_JOINERS') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal">Joiners Register</a>
            </div>
            <div class="card-body">
                <canvas id="adminTrendChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-success"></i>
            <span>Recent Master Audit Trail (Immutable)</span>
        </div>
        <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-link text-decoration-none p-0" style="font-size: 12.5px; color: var(--brand-accent);">
            View Complete Audit Log &rarr;
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target Entity</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAudits as $log)
                        <tr>
                            <td class="code-font text-muted small">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $log->user_name }}</div>
                            </td>
                            <td>
                                @if($log->action === 'CREATE')
                                    <span class="status-pill status-pill-success"><span class="dot"></span>CREATE</span>
                                @elseif($log->action === 'UPDATE')
                                    <span class="status-pill status-pill-warning"><span class="dot"></span>UPDATE</span>
                                @elseif($log->action === 'DELETE')
                                    <span class="status-pill status-pill-danger"><span class="dot"></span>DELETE</span>
                                @elseif($log->action === 'COMPANY_SWITCH')
                                    <span class="status-pill status-pill-info"><span class="dot"></span>COMPANY_SWITCH</span>
                                @else
                                    <span class="status-pill status-pill-info"><span class="dot"></span>{{ $log->action }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border code-font">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                            </td>
                            <td class="code-font text-muted small">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            <td class="small text-muted">
                                @if($log->new_values)
                                    <code>{{ json_encode(array_slice($log->new_values, 0, 3)) }}</code>
                                @else
                                    <em>System recorded</em>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No audit activity recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Department Headcount Bar Chart
        const ctxDept = document.getElementById('adminDeptChart');
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

        // 6-Month Joiners Line Chart
        const ctxTrend = document.getElementById('adminTrendChart');
        if (ctxTrend) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: {!! json_encode($trendLabels) !!},
                    datasets: [{
                        label: 'New Joiners',
                        data: {!! json_encode($trendJoiners) !!},
                        borderColor: '#1E293B',
                        backgroundColor: 'rgba(30, 41, 59, 0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0D9488'
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

