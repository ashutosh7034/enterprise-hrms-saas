@extends('layouts.app')

@section('title', 'Shift Master — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Shift Master</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Configure working hours, overnight cross-day schedules, grace allowances & weekly offs.</p>
    </div>
    <div>
        <a href="{{ route('shifts.create') }}" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">
            <i class="bi bi-plus-circle me-1"></i> Add Shift Schedule
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('shifts.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by shift code or name..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-link text-muted">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Shift Code & Name</th>
                    <th>Schedule Timing</th>
                    <th>Breaks & Grace</th>
                    <th>Thresholds (Half / Full)</th>
                    <th>Applicable Scope</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shifts as $shift)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $shift->name }}</div>
                            <span class="badge bg-light text-secondary font-monospace" style="font-size: 11px;">{{ $shift->code }}</span>
                            @if($shift->is_overnight)
                                <span class="badge bg-info text-dark ms-1" style="font-size: 10px;">Overnight (+1 Day)</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><i class="bi bi-clock me-1 text-muted"></i>{{ $shift->formatted_timings }}</div>
                            <small class="text-muted">{{ intdiv($shift->planned_duration_minutes, 60) }}h {{ $shift->planned_duration_minutes % 60 }}m duration</small>
                        </td>
                        <td>
                            <div>Break: <span class="fw-medium">{{ $shift->break_duration_minutes }}m</span></div>
                            <small class="text-muted">Grace period: {{ $shift->grace_period_minutes }}m</small>
                        </td>
                        <td>
                            <div>Full Day: <span class="fw-medium text-success">{{ intdiv($shift->full_day_threshold_minutes, 60) }}h</span></div>
                            <small class="text-muted">Half Day: {{ intdiv($shift->half_day_threshold_minutes, 60) }}h</small>
                        </td>
                        <td>
                            <div style="font-size: 12px;">
                                @if($shift->location) <i class="bi bi-geo-alt text-muted"></i> {{ $shift->location->name }}<br> @endif
                                @if($shift->department) <i class="bi bi-diagram-3 text-muted"></i> {{ $shift->department->name }}<br> @endif
                                @if(!$shift->location && !$shift->department) <span class="text-muted">Company-wide</span> @endif
                            </div>
                        </td>
                        <td>
                            @if($shift->status === 'Active')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Active</span>
                            @else
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('shifts.edit', $shift->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('shifts.destroy', $shift->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Deactivate this shift schedule?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No shift schedules configured yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($shifts->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $shifts->links() }}
        </div>
    @endif
</div>
@endsection

