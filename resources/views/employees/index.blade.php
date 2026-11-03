@extends('layouts.app')

@section('title', 'Workforce Directory')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Workforce Directory</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Manage personnel onboarding, active gate passes, and eKYC verification compliance.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.create') }}" class="btn btn-accent d-flex align-items-center gap-2">
            <i class="bi bi-person-plus-fill"></i>
            <span>Onboard New Candidate</span>
        </a>
    </div>
</div>

<!-- Search & Filters -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Search by name, EMP code, Gate Pass, phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
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
            <div class="col-md-2">
                <select name="ekyc_status" class="form-select form-select-sm">
                    <option value="">All eKYC Statuses</option>
                    <option value="Pending Verification" {{ request('ekyc_status') == 'Pending Verification' ? 'selected' : '' }}>Pending Verification</option>
                    <option value="Verified" {{ request('ekyc_status') == 'Verified' ? 'selected' : '' }}>Verified</option>
                    <option value="Grace Period Active" {{ request('ekyc_status') == 'Grace Period Active' ? 'selected' : '' }}>Grace Period Active</option>
                    <option value="Verification Failed" {{ request('ekyc_status') == 'Verification Failed' ? 'selected' : '' }}>Verification Failed</option>
                    <option value="Exceptionally Verified" {{ request('ekyc_status') == 'Exceptionally Verified' ? 'selected' : '' }}>Exceptionally Verified</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-brand btn-sm flex-fill">Filter</button>
                @if(request()->anyFilled(['search', 'department_id', 'location_id', 'ekyc_status', 'employment_status']))
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Employees Table -->
<div class="card card-custom">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Active Roster ({{ $employees->total() }} Employees)</span>
        <span class="badge bg-light text-dark border code-font">Company: {{ $activeCompany->code }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Dept & Designation</th>
                    <th>Site / Location</th>
                    <th>Gate Pass #</th>
                    <th>eKYC Status</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 34px; height: 34px; border-radius: 50%; background: #E2E8F0; color: #334155; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                    {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('employees.show', $emp->id) }}" class="text-decoration-none" style="font-weight: 600; color: #1E293B;">
                                        {{ $emp->full_name }}
                                    </a>
                                    <div class="text-muted code-font" style="font-size: 11.5px;">{{ $emp->employee_code }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $emp->designation?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size: 12px;">{{ $emp->department?->name ?? '—' }}</div>
                        </td>
                        <td>
                            <div>{{ $emp->location?->name ?? '—' }}</div>
                            @if($emp->location?->is_ground_staff)
                                <span class="badge bg-warning-subtle text-warning-emphasis border" style="font-size: 10px;">Ground Staff</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->gate_pass_number)
                                <span class="badge bg-light text-dark border code-font">{{ $emp->gate_pass_number }}</span>
                            @else
                                <span class="text-muted" style="font-size: 12px;">None</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->ekyc_status === 'Verified')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Verified</span>
                            @elseif($emp->ekyc_status === 'Exceptionally Verified')
                                <span class="status-pill status-pill-info"><span class="dot"></span> Exception Override</span>
                            @elseif($emp->ekyc_status === 'Grace Period Active')
                                <span class="status-pill status-pill-warning" title="Expires: {{ $emp->ekyc_grace_period_ends_at?->format('d M Y') }}">
                                    <span class="dot"></span> Grace Active ({{ max(0, now()->diffInDays($emp->ekyc_grace_period_ends_at, false)) }}d left)
                                </span>
                            @elseif($emp->ekyc_status === 'Verification Failed')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Failed (3/3)</span>
                            @else
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">{{ $emp->onboarding_channel }}</span>
                            @if($emp->contractor)
                                <div class="text-muted" style="font-size: 11px;">{{ $emp->contractor->name }}</div>
                            @endif
                        </td>
                        <td>
                            @if($emp->employment_status === 'Active')
                                <span class="badge bg-success-subtle text-success border">Active</span>
                            @elseif($emp->employment_status === 'Probation')
                                <span class="badge bg-info-subtle text-info-emphasis border">Probation</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">{{ $emp->employment_status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item" href="{{ route('employees.show', $emp->id) }}"><i class="bi bi-eye me-2 text-primary"></i>View Dossier</a></li>
                                    <li><a class="dropdown-item" href="{{ route('employees.edit', $emp->id) }}"><i class="bi bi-pencil me-2 text-secondary"></i>Edit Profile</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-people display-6 d-block mb-2 text-secondary"></i>
                            <div style="font-size: 15px; font-weight: 500;">No employees found matching filter criteria</div>
                            <div style="font-size: 13px;">Onboard a new candidate or adjust active filters.</div>
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
@endsection

