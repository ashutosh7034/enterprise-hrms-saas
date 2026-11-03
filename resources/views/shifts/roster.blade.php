@extends('layouts.app')

@section('title', 'Employee Shift Roster — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Employee Shift Roster</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Schedule date-wise shifts, assign rotational rosters, manage weekly offs & holidays.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#recurringShiftModal">
            <i class="bi bi-arrow-repeat me-1"></i> Set Default Shift
        </button>
        <button type="button" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);" data-bs-toggle="modal" data-bs-target="#assignRosterModal">
            <i class="bi bi-calendar-plus me-1"></i> Assign Roster
        </button>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('shifts.roster') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Location</label>
                <select name="location_id" class="form-select form-select-sm">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Depts</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color: var(--brand-primary-dark);">Filter</button>
                <a href="{{ route('shifts.roster') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-bordered table-custom mb-0 text-center align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="text-start" style="min-width: 200px;">Employee</th>
                    @foreach($dates as $d)
                        @php $carbonD = \Carbon\Carbon::parse($d); @endphp
                        <th style="min-width: 105px;" class="{{ $carbonD->isWeekend() ? 'bg-light-subtle' : '' }}">
                            <div class="fw-bold">{{ $carbonD->format('D') }}</div>
                            <small class="text-muted">{{ $carbonD->format('d M') }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    <tr>
                        <td class="text-start bg-white">
                            <div class="fw-bold text-dark">{{ $emp->full_name }}</div>
                            <small class="text-muted">{{ $emp->employee_code }} • {{ $emp->department?->name }}</small>
                        </td>
                        @foreach($dates as $d)
                            @php
                                $key = $emp->id . '_' . $d;
                                $rost = $rosters->get($key)?->first();
                            @endphp
                            <td style="font-size: 11.5px;">
                                @if($rost)
                                    @if($rost->is_weekly_off)
                                        <span class="badge bg-secondary text-white">Weekly Off</span>
                                    @elseif($rost->is_holiday)
                                        <span class="badge bg-danger text-white">Holiday</span>
                                    @elseif($rost->shift)
                                        <span class="badge bg-primary text-white" title="{{ $rost->shift->formatted_timings }}">
                                            {{ $rost->shift->code }}
                                        </span>
                                        <div class="text-muted" style="font-size: 10px;">{{ substr($rost->shift->start_time, 0, 5) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted" style="font-size: 11px;">Default</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($dates) + 1 }}" class="py-5 text-muted text-center">
                            No employees found matching filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $employees->links() }}
        </div>
    @endif
</div>

<!-- Assign Date-Wise Roster Modal -->
<div class="modal fade" id="assignRosterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('shifts.roster.assign') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Assign Date-Wise Shift Roster</h5>
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
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">From Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">To Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift Assignment</label>
                        <select name="shift_id" class="form-select">
                            <option value="">-- No Shift (If Weekly Off / Holiday) --</option>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->formatted_timings }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_weekly_off" value="1" id="is_wo">
                                <label class="form-check-label" for="is_wo">Mark as Weekly Off</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_holiday" value="1" id="is_h">
                                <label class="form-check-label" for="is_h">Mark as Holiday</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Plant rotation batch A">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Save Roster</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Set Default Recurring Shift Modal -->
<div class="modal fade" id="recurringShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('shifts.roster.recurring') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Set Default Shift Schedule</h5>
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
                        <label class="form-label required">Default Shift</label>
                        <select name="shift_id" class="form-select" required>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->formatted_timings }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Effective From</label>
                            <input type="date" name="from_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Effective To (Optional)</label>
                            <input type="date" name="to_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Assign Default Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

