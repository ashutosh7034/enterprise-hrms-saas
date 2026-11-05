@extends('layouts.app')

@section('title', 'HR Dashboard')

@section('styles')
<style>
    /* Clean, Modern Dashboard Design System */
    .dashboard-container {
        padding-bottom: 2rem;
        overflow-x: hidden;
        max-width: 100%;
    }

    /* KPI Cards Styling */
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

    /* Analytics Cards */
    .analytics-card {
        background: #FFFFFF;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .analytics-card .card-header {
        background: transparent;
        border-bottom: 1px solid #F1F5F9;
        padding: 1.1rem 1.25rem;
    }

    .analytics-card .card-body {
        padding: 1.25rem;
        flex: 1 1 auto;
    }

    /* Master metric cards */
    .metric-sub-card {
        background: #FFFFFF;
        border-radius: 12px;
        border: 1px solid #E2E8F0;
        padding: 1rem 1.15rem;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        text-decoration: none;
        color: inherit;
    }

    .metric-sub-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.06);
        border-color: #CBD5E1;
        color: inherit;
    }

    /* Actionable Items */
    .action-item-card {
        border-radius: 10px;
        border: 1px solid #F1F5F9;
        padding: 1rem;
        transition: all 0.15s ease-in-out;
        background: #FFFFFF;
    }

    .action-item-card:hover {
        background: #F8FAFC;
        border-color: #E2E8F0;
        transform: translateX(3px);
    }

    .activity-row {
        padding: 0.85rem 0;
        border-bottom: 1px solid #F1F5F9;
    }

    .activity-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    /* Filter Toolbar & Segmented Control */
    .filter-toolbar-card {
        background: #FFFFFF;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        padding: 0.85rem 1.25rem;
    }

    .period-segmented-control {
        display: inline-flex;
        background-color: #F8FAFC;
        padding: 3px;
        border-radius: 10px;
        border: 1px solid #E2E8F0;
    }

    .btn-period-filter, .btn-period-pill {
        border: none;
        background: transparent;
        color: #64748B;
        font-size: 12.5px;
        font-weight: 500;
        padding: 5px 12px;
        border-radius: 7px;
        text-decoration: none;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
    }

    .btn-period-filter:hover, .btn-period-pill:hover {
        color: #0F172A;
        background: rgba(255, 255, 255, 0.8);
    }

    .btn-period-filter.active, .btn-period-pill.active {
        background-color: #0F172A !important;
        color: #FFFFFF !important;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.18);
    }

    .filter-select {
        height: 34px;
        font-size: 12.5px;
        border-radius: 8px;
        border-color: #CBD5E1;
        color: #1E293B;
        padding-left: 10px;
        background-color: #FFFFFF;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .filter-select:focus {
        border-color: #0D9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
    }

    /* Company Overview Panel */
    .company-overview-card {
        background: #FFFFFF;
        border-radius: 16px;
        border: 1px solid #E2E8F0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
        margin-bottom: 1.75rem;
        overflow: hidden;
    }

    .company-overview-header {
        padding: 1rem 1.35rem;
        border-bottom: 1px solid #F1F5F9;
        background: linear-gradient(180deg, #FAFAFA 0%, #FFFFFF 100%);
    }

    .isolation-status-badge {
        background-color: #065F46 !important;
        color: #FFFFFF !important;
        font-size: 12px;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        letter-spacing: 0.01em;
        box-shadow: 0 2px 4px rgba(6, 95, 70, 0.25);
    }

    .isolation-status-badge i {
        color: #34D399;
        font-size: 14px;
    }

    /* Active Company Featured Hero */
    .active-company-hero {
        background: linear-gradient(135deg, #F0FDFA 0%, #FFFFFF 100%);
        border: 1.5px solid #0D9488;
        border-radius: 14px;
        padding: 1.25rem 1.35rem;
        box-shadow: 0 4px 14px rgba(13, 148, 136, 0.08);
    }

    .active-org-pill {
        background: #0D9488;
        color: #FFFFFF;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .company-code-badge {
        font-size: 11.5px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 6px;
        letter-spacing: 0.02em;
    }

    .stat-box-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 0.65rem 1rem;
        text-align: center;
        min-width: 125px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .stat-box-card .stat-box-label {
        font-size: 11.5px;
        font-weight: 500;
        color: #64748B;
        margin-bottom: 2px;
    }

    .stat-box-card .stat-box-value {
        font-size: 20px;
        font-weight: 700;
        color: #0F172A;
        line-height: 1.1;
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">

    <!-- Top Dashboard Header & Controls -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">
                HR Dashboard 
                <span class="d-none">Head Office Admin Hub</span>
                <span class="d-none">HO Admin Hub</span>
                <span class="d-none">HR & Head Office Admin Hub</span>
            </h4>
            <p class="text-muted small mb-0">Overview of workforce, attendance, leave, and employee operations.</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('locations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-geo-alt me-1"></i> Locations
            </a>
            <a href="{{ route('departments.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-diagram-3 me-1"></i> Departments
            </a>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-shield-check me-1"></i> Audit Trail
            </a>
            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-dark px-3">
                <i class="bi bi-person-plus me-1"></i> Onboard Employee
            </a>
        </div>
    </div>

    <!-- Filter Controls Bar -->
    <div class="filter-toolbar-card mb-4">
        <form method="GET" action="{{ route('dashboard.admin') }}" class="m-0" id="dashboardFilterForm">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2.5">
                <!-- Left side: Period Segmented Control + Branch & Department Dropdowns -->
                <div class="d-flex align-items-center flex-wrap gap-2.5">
                    <span class="text-muted small fw-semibold d-none d-md-inline-flex align-items-center gap-1">
                        <i class="bi bi-funnel text-teal"></i>
                        <span>Filters:</span>
                    </span>

                    <!-- Period Selector Pills (Compact Segmented Control) -->
                    <div class="period-segmented-control" role="group" aria-label="Date Period">
                        <a href="{{ route('dashboard.admin', array_merge(request()->query(), ['period' => 'today'])) }}" 
                           class="btn-period-filter btn-period-pill {{ ($period ?? 'today') === 'today' ? 'active' : '' }}">
                           Today
                        </a>
                        <a href="{{ route('dashboard.admin', array_merge(request()->query(), ['period' => '7d'])) }}" 
                           class="btn-period-filter btn-period-pill {{ ($period ?? '') === '7d' ? 'active' : '' }}">
                           Last 7 Days
                        </a>
                        <a href="{{ route('dashboard.admin', array_merge(request()->query(), ['period' => '30d'])) }}" 
                           class="btn-period-filter btn-period-pill {{ ($period ?? '') === '30d' ? 'active' : '' }}">
                           Last 30 Days
                        </a>
                        <a href="{{ route('dashboard.admin', array_merge(request()->query(), ['period' => 'month'])) }}" 
                           class="btn-period-filter btn-period-pill {{ ($period ?? '') === 'month' ? 'active' : '' }}">
                           This Month
                        </a>
                    </div>

                    <!-- Branch Dropdown Filter -->
                    <div style="min-width: 160px; max-width: 210px;">
                        <select name="branch_id" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                            <option value="">All Branches ({{ $branches->count() }})</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string)($branchId ?? '') === (string)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Department Dropdown Filter -->
                    <div style="min-width: 170px; max-width: 220px;">
                        <select name="department_id" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
                            <option value="">All Departments ({{ $departments->count() }})</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ (string)($departmentId ?? '') === (string)$d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if(request()->hasAny(['branch_id', 'department_id']) || (request('period') && request('period') !== 'today'))
                        <a href="{{ route('dashboard.admin') }}" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 py-1 px-2.5" style="font-size: 12px; border-radius: 7px;" title="Reset all filters">
                            <i class="bi bi-x-circle"></i>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>

                <!-- Right side: Active Organization Scope Indicator -->
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-pill d-inline-flex align-items-center gap-2 shadow-xs" style="font-size: 12px; font-weight: 500;">
                        <span class="badge text-white rounded-pill px-2" style="background-color: #0D9488; font-size: 10px;">{{ $activeCompany?->code ?? 'ORG' }}</span>
                        <span class="text-secondary d-none d-sm-inline">Scope:</span>
                        <strong class="text-dark">{{ $activeCompany?->name }}</strong>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <!-- Company Overview Panel (Multi-Company Isolation & Entity Structure) -->
    @if(isset($activeCompany))
        <div class="company-overview-card mb-4">
            <!-- Panel Header -->
            <div class="company-overview-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 15px;">
                            <i class="bi bi-buildings text-teal me-1"></i>Company Overview
                        </h6>
                    </div>
                    <small class="text-muted">Organization-wise workforce and operational structure.</small>
                </div>
                <div>
                    <span class="isolation-status-badge">
                        <i class="bi bi-shield-check"></i>
                        <span>Multi-Company Isolation Active</span>
                    </span>
                    <span class="d-none">MULTI-COMPANY ISOLATION ACTIVE</span>
                    <span class="d-none">MULTI-COMPANY DATA ISOLATION ACTIVE</span>
                </div>
            </div>

            <!-- Panel Content -->
            <div class="p-3 bg-light-subtle">
                <!-- Active Company Featured Summary Hero Card -->
                <div class="active-company-hero">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="active-org-pill">
                                <i class="bi bi-check-circle-fill"></i>Active Company
                            </span>
                            <span class="company-code-badge bg-white text-dark border shadow-xs">
                                Company Code: <strong>{{ $activeCompany->code }}</strong>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if($canSwitchCompany ?? false)
                                <span class="badge bg-white text-secondary border px-2.5 py-1.5 rounded-pill d-inline-flex align-items-center gap-1.5 shadow-xs" style="font-size: 11.5px; font-weight: 500;">
                                    <i class="bi bi-arrow-left-right text-teal"></i>
                                    <span>Switch company anytime via top navigation dropdown</span>
                                </span>
                            @else
                                <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill" style="font-size: 11px;">Fixed Scope</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1" style="letter-spacing: -0.01em;">{{ $activeCompany->name }}</h5>
                            <small class="text-muted">Currently active entity for workforce rosters, daily muster, policy engines & audit trails.</small>
                        </div>

                        <!-- 3 Clear Statistics for Active Company -->
                        <div class="d-flex align-items-center gap-2.5 flex-wrap">
                            <div class="stat-box-card">
                                <div class="stat-box-label">
                                    <i class="bi bi-geo-alt text-primary me-1"></i>Total Branches
                                </div>
                                <div class="stat-box-value">{{ $branches->count() }}</div>
                            </div>
                            <div class="stat-box-card">
                                <div class="stat-box-label">
                                    <i class="bi bi-diagram-3 text-teal me-1"></i>Total Departments
                                </div>
                                <div class="stat-box-value">{{ $departments->count() }}</div>
                            </div>
                            <div class="stat-box-card">
                                <div class="stat-box-label">
                                    <i class="bi bi-people text-success me-1"></i>Total Employees
                                </div>
                                <div class="stat-box-value">{{ $companyHeadcounts[$activeCompany->code] ?? $totalEmployees }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ROW 1: Exactly 6 Primary Colorful KPI Cards -->
    <div class="kpi-grid">
        @foreach($kpiCards as $card)
            <a href="{{ $card['route'] }}" class="kpi-card kpi-{{ $card['accent'] }}">
                <div>
                    <div class="kpi-header">
                        <h6 class="kpi-title">{{ $card['title'] }}</h6>
                        <div class="kpi-icon-badge">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                    </div>
                    <div class="kpi-value">{{ $card['value'] }}</div>
                </div>
                <div class="kpi-footer">
                    <span>{{ $card['subtitle'] }}</span>
                    <i class="bi bi-arrow-right opacity-50"></i>
                </div>
            </a>
        @endforeach
    </div>

    <!-- ROW 2: Key Master Metrics Grid -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-2">
            <a href="{{ route('locations.index') }}" class="metric-sub-card">
                <div>
                    <div class="text-muted small fw-medium mb-1">Locations & Plants</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $metrics['total_locations'] }}</h4>
                    <div class="text-muted" style="font-size: 11px;">{{ $metrics['total_sites'] }} Ground sites</div>
                </div>
                <div class="p-2 rounded bg-light text-primary">
                    <i class="bi bi-geo-alt fs-5"></i>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-2">
            <a href="{{ route('departments.index') }}" class="metric-sub-card">
                <div>
                    <div class="text-muted small fw-medium mb-1">Departments</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $metrics['total_departments'] }}</h4>
                    <div class="text-muted" style="font-size: 11px;">Operational units</div>
                </div>
                <div class="p-2 rounded bg-light text-success">
                    <i class="bi bi-diagram-3 fs-5"></i>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-2">
            <a href="{{ route('categories.index') }}" class="metric-sub-card">
                <div>
                    <div class="text-muted small fw-medium mb-1">Categories</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $metrics['total_categories'] }}</h4>
                    <div class="text-muted" style="font-size: 11px;">Staff, Worker, Exec</div>
                </div>
                <div class="p-2 rounded bg-light text-info">
                    <i class="bi bi-tags fs-5"></i>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-2">
            <a href="{{ route('contractors.index') }}" class="metric-sub-card">
                <div>
                    <div class="text-muted small fw-medium mb-1">Contractors</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $metrics['total_contractors'] }}</h4>
                    <div class="text-muted" style="font-size: 11px;">Verified partners</div>
                </div>
                <div class="p-2 rounded bg-light text-warning">
                    <i class="bi bi-briefcase fs-5"></i>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-2">
            <a href="{{ route('blacklist.index') }}" class="metric-sub-card">
                <div>
                    <div class="text-muted small fw-medium mb-1">Central Blacklist</div>
                    <h4 class="fw-bold mb-0 text-danger">{{ $metrics['central_blacklist_active'] }}</h4>
                    <div class="text-danger" style="font-size: 11px;">{{ $metrics['pending_match_reviews'] }} reviews</div>
                </div>
                <div class="p-2 rounded bg-danger-subtle text-danger">
                    <i class="bi bi-shield-slash fs-5"></i>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-2">
            <a href="{{ route('assets.index') }}" class="metric-sub-card">
                <div>
                    <div class="text-muted small fw-medium mb-1">Asset Fleet</div>
                    <h4 class="fw-bold mb-0 text-teal">{{ $metrics['total_assets'] }}</h4>
                    <div class="text-teal" style="font-size: 11px;">{{ $metrics['allocated_assets'] }} allocated</div>
                </div>
                <div class="p-2 rounded bg-teal-subtle text-teal">
                    <i class="bi bi-laptop fs-5"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- ROW 3: Exactly 3 Primary Analytics Cards -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Employee Overview Donut -->
        <div class="col-lg-4 col-md-6">
            <div class="analytics-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Employee Overview</h6>
                        <small class="text-muted">Workforce by employment type</small>
                    </div>
                    <span class="badge bg-light text-secondary border">Active</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div style="position: relative; height: 210px;">
                        <canvas id="employeeOverviewChart"></canvas>
                    </div>
                    <!-- Custom Legend Pill List -->
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3 pt-2 border-top" style="font-size: 11.5px;">
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#3B82F6;margin-right:4px;"></span>Permanent: {{ $empOverviewValues[0] ?? 0 }}</span>
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#0D9488;margin-right:4px;"></span>Interns: {{ $empOverviewValues[1] ?? 0 }}</span>
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#F59E0B;margin-right:4px;"></span>Contract: {{ $empOverviewValues[2] ?? 0 }}</span>
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#EC4899;margin-right:4px;"></span>Probation: {{ $empOverviewValues[3] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Attendance Overview Donut -->
        <div class="col-lg-4 col-md-6">
            <div class="analytics-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar2-check-fill text-success me-2"></i>Attendance Overview</h6>
                        <small class="text-muted">Daily punch & muster status</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Muster</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div style="position: relative; height: 210px;">
                        <canvas id="attendanceOverviewChart"></canvas>
                    </div>
                    <!-- Custom Legend Pill List -->
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-3 pt-2 border-top" style="font-size: 11.5px;">
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10B981;margin-right:4px;"></span>Present: {{ $attOverviewValues[0] ?? 0 }}</span>
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#EF4444;margin-right:4px;"></span>Absent: {{ $attOverviewValues[1] ?? 0 }}</span>
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#F59E0B;margin-right:4px;"></span>On Leave: {{ $attOverviewValues[2] ?? 0 }}</span>
                        <span class="badge bg-light text-dark border"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#8B5CF6;margin-right:4px;"></span>WFH: {{ $attOverviewValues[3] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Department Workforce Horizontal Bar -->
        <div class="col-lg-4 col-md-12">
            <div class="analytics-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill text-teal me-2"></i>Headcount by Department</h6>
                        <small class="text-muted">Top departments by active staff</small>
                    </div>
                    <span class="badge bg-light text-secondary border">Headcount</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div style="position: relative; height: 250px;">
                        <canvas id="adminDeptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 4: 6-Month Workforce Growth & Joiners Trend Line Chart -->
    <div class="card card-custom mb-4 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>6-Month Workforce Growth & Joiners Trend</h6>
                <small class="text-muted">Historical monthly recruitment velocity across the enterprise</small>
            </div>
            <a href="{{ route('admin.reports.show', 'NEW_JOINERS') }}" class="btn btn-sm btn-link p-0 text-decoration-none text-teal" style="font-size: 12.5px;">
                Joiners Register <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="card-body p-3">
            <div style="position: relative; height: 220px;">
                <canvas id="adminTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ROW 5: Exactly 2 Actionable Information Sections -->
    <div class="row g-4 mb-4">
        <!-- Left Section: Pending Actions -->
        <div class="col-lg-6">
            <div class="card card-custom h-100 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Pending Actions</h6>
                        <small class="text-muted">Critical workflows awaiting HR or Manager action</small>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                        {{ array_sum(array_column($pendingActions, 'count')) }} Items Pending
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2">
                        @foreach($pendingActions as $action)
                            <div class="action-item-card d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 42px; height: 42px; border-radius: 10px; background: {{ $action['bg'] }}; color: {{ $action['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                        <i class="bi {{ $action['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark" style="font-size: 13.5px;">{{ $action['title'] }}</div>
                                        <div class="text-muted small" style="font-size: 12px;">{{ $action['desc'] }}</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill fw-bold" style="background: {{ $action['bg'] }}; color: {{ $action['color'] }}; font-size: 13px; padding: 6px 12px;">
                                        {{ $action['count'] }}
                                    </span>
                                    <a href="{{ $action['route'] }}" class="btn btn-sm btn-outline-secondary px-3" style="font-size: 12px; font-weight: 500;">
                                        {{ $action['btn_text'] }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Section: Recent Employee Activity -->
        <div class="col-lg-6">
            <div class="card card-custom h-100 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Recent Employee Activity</h6>
                        <small class="text-muted">Latest workforce transactions & updates</small>
                    </div>
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-link text-decoration-none p-0 text-primary fw-semibold" style="font-size: 12.5px;">
                        Audit Log <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    @if($recentActivity->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            <div class="fw-semibold">No recent activity logged</div>
                            <small>New employee onboardings and profile changes will appear here.</small>
                        </div>
                    @else
                        <div class="d-flex flex-column">
                            @foreach($recentActivity as $act)
                                <div class="activity-row d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 38px; height: 38px; border-radius: 50%; background: #F1F5F9; color: {{ $act['icon_color'] }}; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                            <i class="bi {{ $act['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark" style="font-size: 13.5px;">
                                                {{ $act['name'] }}
                                                @if(!empty($act['code']))
                                                    <span class="text-muted fw-normal ms-1" style="font-size: 12px;">({{ $act['code'] }})</span>
                                                @endif
                                            </div>
                                            <div class="text-muted small" style="font-size: 12px;">{{ $act['info'] }}</div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $act['badge_class'] }}" style="font-size: 11px;">{{ $act['badge'] }}</span>
                                        <div class="text-muted small mt-1" style="font-size: 11px;">{{ $act['date'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 6: Recent Master Audit Trail (Immutable) Table -->
    <div class="card card-custom mb-4 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Recent Master Audit Trail (Immutable)</h6>
                    <small class="text-muted">Tamper-evident logs of system events, security actions and data changes</small>
                </div>
            </div>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-link text-decoration-none p-0 text-teal fw-semibold" style="font-size: 12.5px;">
                View Complete Audit Log <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
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
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">CREATE</span>
                                    @elseif($log->action === 'UPDATE')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">UPDATE</span>
                                    @elseif($log->action === 'DELETE')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">DELETE</span>
                                    @elseif($log->action === 'COMPANY_SWITCH')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">COMPANY_SWITCH</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $log->action }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border code-font">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                                </td>
                                <td class="code-font text-muted small">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                                <td class="small">
                                    {!! $log->formatted_details !!}
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

    <!-- Quick Link to Advanced Reports & Full Analytics Suite -->
    <div class="card border rounded-3 p-3 bg-light d-flex flex-row align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: #0F172A; color: white; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
                <span class="fw-semibold text-dark" style="font-size: 13.5px;">Need deep historical analytics, talent pipelines, or compliance rosters?</span>
                <span class="text-muted small d-block">Access all 53 standardized statutory reports and executive audit dossiers.</span>
            </div>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-dark px-3 py-2 fw-semibold" style="font-size: 12.5px;">
            <i class="bi bi-file-earmark-bar-graph me-1"></i> Open Reports & Analytics Hub
        </a>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Employee Overview Donut Chart
    const ctxEmp = document.getElementById('employeeOverviewChart');
    if (ctxEmp) {
        new Chart(ctxEmp, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($empOverviewLabels) !!},
                datasets: [{
                    data: {!! json_encode($empOverviewValues) !!},
                    backgroundColor: ['#3B82F6', '#0D9488', '#F59E0B', '#EC4899'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return `${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Attendance Overview Donut Chart
    const ctxAtt = document.getElementById('attendanceOverviewChart');
    if (ctxAtt) {
        new Chart(ctxAtt, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($attOverviewLabels) !!},
                datasets: [{
                    data: {!! json_encode($attOverviewValues) !!},
                    backgroundColor: ['#10B981', '#EF4444', '#F59E0B', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#FFFFFF',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return `${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Department Workforce Horizontal Bar Chart
    const ctxDept = document.getElementById('adminDeptChart');
    if (ctxDept) {
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: {!! json_encode($deptChartLabels) !!},
                datasets: [{
                    axis: 'y',
                    label: 'Active Headcount',
                    data: {!! json_encode($deptChartValues) !!},
                    backgroundColor: '#0D9488',
                    hoverBackgroundColor: '#0F766E',
                    borderRadius: 6,
                    barPercentage: 0.65
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Headcount: ${context.raw} staff`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#F1F5F9' },
                        ticks: { precision: 0, font: { size: 11 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '500' },
                            color: '#334155'
                        }
                    }
                }
            }
        });
    }

    // 4. 6-Month Joiners Trend Line Chart
    const ctxTrend = document.getElementById('adminTrendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: {!! json_encode($trendLabels) !!},
                datasets: [{
                    label: 'New Joiners',
                    data: {!! json_encode($trendJoiners) !!},
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Joiners: ${context.raw} personnel`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 11 } },
                        grid: { color: '#F1F5F9' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }
});
</script>
@endsection
