@extends('layouts.app')

@section('title', 'Daily Attendance Muster — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Daily Attendance Muster</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Deterministic daily attendance calculation, paired punch evaluation, overtime & shutdown impact.</p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('attendance.recalculate') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-arrow-clockwise me-1"></i> Recalculate Muster ({{ \Carbon\Carbon::parse($date)->format('d M') }})
            </button>
        </form>
        <a href="{{ route('attendance.reconciliation') }}" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">
            <i class="bi bi-shield-check me-1"></i> HO Compliance Hub
        </a>
    </div>
</div>

<!-- Status Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card card-custom p-3 border-start border-4 border-success">
            <div class="text-muted small text-uppercase fw-bold">Present</div>
            <div class="h3 mb-0 fw-bold text-success mt-1">{{ $statusCounts['Present'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom p-3 border-start border-4 border-info">
            <div class="text-muted small text-uppercase fw-bold">Half Day</div>
            <div class="h3 mb-0 fw-bold text-info mt-1">{{ $statusCounts['Half Day'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom p-3 border-start border-4 border-danger">
            <div class="text-muted small text-uppercase fw-bold">Absent</div>
            <div class="h3 mb-0 fw-bold text-danger mt-1">{{ $statusCounts['Absent'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom p-3 border-start border-4 border-warning">
            <div class="text-muted small text-uppercase fw-bold">Missing Punch</div>
            <div class="h3 mb-0 fw-bold text-warning mt-1">{{ $statusCounts['Missing Punch'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom p-3 border-start border-4 border-secondary">
            <div class="text-muted small text-uppercase fw-bold">Site Shutdown</div>
            <div class="h3 mb-0 fw-bold text-secondary mt-1">{{ ($statusCounts['Site Shutdown'] ?? 0) + ($statusCounts['Worked During Shutdown'] ?? 0) }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card card-custom p-3 border-start border-4 border-primary">
            <div class="text-muted small text-uppercase fw-bold">Weekly Off / OD</div>
            <div class="h3 mb-0 fw-bold text-primary mt-1">{{ ($statusCounts['Weekly Off'] ?? 0) + ($statusCounts['On Duty'] ?? 0) + ($statusCounts['Regularized'] ?? 0) }}</div>
        </div>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('attendance.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['Present', 'Half Day', 'Absent', 'Missing Punch', 'Site Shutdown', 'Worked During Shutdown', 'Weekly Off', 'Regularized', 'On Duty'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="location_id" class="form-select form-select-sm">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employee code, name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color: var(--brand-primary-dark);">Filter</button>
                <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Employee Details</th>
                    <th>Shift & Timing</th>
                    <th>First IN / Last OUT</th>
                    <th>Duration & Break</th>
                    <th>Deviations (Late / Early)</th>
                    <th>Overtime</th>
                    <th>Muster Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $rec->employee?->full_name }}</div>
                            <small class="text-muted">{{ $rec->employee?->employee_code }} • {{ $rec->employee?->department?->name }}</small>
                        </td>
                        <td>
                            @if($rec->shift)
                                <div class="fw-semibold text-dark">{{ $rec->shift->name }}</div>
                                <small class="text-muted font-monospace">{{ $rec->shift->formatted_timings }}</small>
                            @else
                                <span class="text-muted small">Standard Shift</span>
                            @endif
                        </td>
                        <td>
                            <div><span class="text-muted small">IN:</span> <span class="fw-bold text-dark">{{ $rec->first_in ? $rec->first_in->format('H:i') : '--:--' }}</span></div>
                            <div><span class="text-muted small">OUT:</span> <span class="fw-bold text-dark">{{ $rec->last_out ? $rec->last_out->format('H:i') : '--:--' }}</span></div>
                            <small class="badge bg-light text-muted border" style="font-size: 10px;">{{ $rec->total_punches }} punches</small>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $rec->formatted_work_duration }}</div>
                            <small class="text-muted">Total span: {{ $rec->formatted_total_duration }} (Break: {{ $rec->break_minutes }}m)</small>
                        </td>
                        <td>
                            @if($rec->late_arrival_minutes > 0)
                                <span class="badge bg-danger-subtle text-danger" style="font-size: 10.5px;">Late: {{ $rec->late_arrival_minutes }}m</span><br>
                            @endif
                            @if($rec->early_departure_minutes > 0)
                                <span class="badge bg-warning-subtle text-dark" style="font-size: 10.5px;">Early: {{ $rec->early_departure_minutes }}m</span>
                            @endif
                            @if($rec->late_arrival_minutes == 0 && $rec->early_departure_minutes == 0)
                                <span class="text-success small"><i class="bi bi-check-circle me-1"></i>On Time</span>
                            @endif
                        </td>
                        <td>
                            @if($rec->overtime_minutes > 0)
                                <span class="badge bg-success-subtle text-success fw-bold" style="font-size: 11px;">
                                    +{{ intdiv($rec->overtime_minutes, 60) }}h {{ $rec->overtime_minutes % 60 }}m
                                </span>
                            @else
                                <span class="text-muted small">0m</span>
                            @endif
                        </td>
                        <td>
                            @if($rec->status === 'Present')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Present</span>
                            @elseif($rec->status === 'Half Day')
                                <span class="status-pill status-pill-info"><span class="dot"></span> Half Day</span>
                            @elseif($rec->status === 'Absent')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Absent</span>
                            @elseif($rec->status === 'Missing Punch')
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Missing Punch</span>
                            @elseif($rec->status === 'Site Shutdown')
                                <span class="badge bg-secondary text-white">Site Shutdown</span>
                            @elseif($rec->status === 'Worked During Shutdown')
                                <span class="badge bg-primary text-white">Worked in Shutdown</span>
                            @elseif($rec->status === 'Weekly Off')
                                <span class="badge bg-light text-secondary border">Weekly Off</span>
                            @elseif($rec->status === 'Regularized')
                                <span class="badge bg-purple text-white" style="background-color: #7C3AED;">Regularized</span>
                            @elseif($rec->status === 'On Duty')
                                <span class="badge bg-info text-dark">On Duty (OD)</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('attendance.show', $rec->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar2-x d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No attendance records calculated for {{ $date }}. Click "Recalculate Muster" to process raw punch staging.
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

