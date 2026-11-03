@extends('layouts.app')

@section('title', 'Holiday Calendar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Holiday Master & Site Calendar</h1>
        <p class="text-muted small mb-0">Company-wide, site-specific, and ground-staff holiday schedules for {{ $year }}.</p>
    </div>
    @if(Auth::user()->isHr())
        <a href="{{ route('leaves.holidays.create') }}" class="btn btn-accent">
            <i class="bi bi-calendar-plus me-1"></i> Add Holiday
        </a>
    @endif
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('leaves.holidays.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = (int)date('Y') - 1; $y <= (int)date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Holiday Types</option>
                    <option value="National Holiday" {{ request('type') === 'National Holiday' ? 'selected' : '' }}>National Holiday</option>
                    <option value="Festival Holiday" {{ request('type') === 'Festival Holiday' ? 'selected' : '' }}>Festival Holiday</option>
                    <option value="Site-Specific Holiday" {{ request('type') === 'Site-Specific Holiday' ? 'selected' : '' }}>Site-Specific Holiday</option>
                    <option value="Regional Holiday" {{ request('type') === 'Regional Holiday' ? 'selected' : '' }}>Regional Holiday</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search holiday title...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Date & Day</th>
                    <th>Holiday Name</th>
                    <th>Type</th>
                    <th>Applicable Scope</th>
                    <th>Ground Staff</th>
                    <th>Paid</th>
                    <th>Status</th>
                    @if(Auth::user()->isHr())
                        <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($holidays as $holiday)
                    <tr>
                        <td>
                            <div class="fw-bold text-slate-800">{{ $holiday->date->format('d M Y') }}</div>
                            <span class="text-muted small">{{ $holiday->date->format('l') }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-slate-900">{{ $holiday->name }}</div>
                            @if($holiday->description)
                                <div class="text-muted small">{{ $holiday->description }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $holiday->holiday_type }}</span>
                        </td>
                        <td>
                            @if($holiday->location)
                                <span class="badge bg-primary-subtle text-primary"><i class="bi bi-geo-alt"></i> {{ $holiday->location->name }}</span>
                            @elseif($holiday->department)
                                <span class="badge bg-info-subtle text-info"><i class="bi bi-diagram-3"></i> {{ $holiday->department->name }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">All Locations</span>
                            @endif
                        </td>
                        <td>
                            @if($holiday->is_ground_staff_applicable)
                                <span class="text-success small fw-semibold"><i class="bi bi-check-circle"></i> Yes</span>
                            @else
                                <span class="text-muted small"><i class="bi bi-x-circle"></i> Excluded</span>
                            @endif
                        </td>
                        <td>
                            @if($holiday->is_paid)
                                <span class="badge bg-success-subtle text-success">Paid</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            @if($holiday->status === 'Active')
                                <span class="status-pill status-pill-success"><span class="dot"></span>Active</span>
                            @else
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Inactive</span>
                            @endif
                        </td>
                        @if(Auth::user()->isHr())
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('leaves.holidays.edit', $holiday->id) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('leaves.holidays.destroy', $holiday->id) }}" method="POST" onsubmit="return confirm('Delete this holiday?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                            No holidays configured for year {{ $year }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($holidays->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $holidays->links() }}
        </div>
    @endif
</div>
@endsection

