@extends('layouts.app')

@section('title', 'Employee Code Master List — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 13px;">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Employee Master</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Code Master List</li>
                </ol>
            </nav>
            <h4 class="mb-1 fw-bold text-dark"><i class="bi bi-upc text-primary me-2"></i>Employee Code Master List</h4>
            <p class="text-muted mb-0" style="font-size: 13.5px;">Master register of all assigned workforce identifiers, generation methodologies, and sequential audit traces.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.employee-codes.export', request()->all()) }}" class="btn btn-outline-success btn-sm px-3 fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Master CSV
            </a>
            <a href="{{ route('admin.employee-codes.index') }}" class="btn btn-primary btn-sm px-3 fw-semibold">
                <i class="bi bi-gear me-1"></i> Configure Patterns
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border rounded-3 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.employee-codes.master') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search Code, Name..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="location_id" class="form-select form-select-sm">
                        <option value="">All Branches / Sites</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
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
                    <select name="code_generation_method" class="form-select form-select-sm">
                        <option value="">All Generation Methods</option>
                        <option value="Automatic" {{ request('code_generation_method') === 'Automatic' ? 'selected' : '' }}>Automatic Sequence</option>
                        <option value="Manual" {{ request('code_generation_method') === 'Manual' ? 'selected' : '' }}>Manual Override</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="employment_type" class="form-select form-select-sm">
                        <option value="">All Employment Types</option>
                        @foreach(['Permanent', 'Intern', 'Contract', 'Probationary', 'Trainee'] as $t)
                            <option value="{{ $t }}" {{ request('employment_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Apply Filter"><i class="bi bi-filter"></i></button>
                    @if(request()->anyFilled(['search', 'location_id', 'department_id', 'code_generation_method', 'employment_type']))
                        <a href="{{ route('admin.employee-codes.master') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Code Table -->
    <div class="card border rounded-3 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light text-secondary">
                    <tr>
                        <th style="width: 140px;">Employee Code</th>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Company</th>
                        <th>Branch</th>
                        <th>Department</th>
                        <th>Employment Type</th>
                        <th>Date of Joining</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Registered Date</th>
                        <th class="text-end" style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td>
                                <span class="badge bg-dark-subtle text-dark border font-monospace px-2 py-1 fs-6">
                                    {{ $emp->employee_code }}
                                </span>
                            </td>
                            <td class="text-muted fw-semibold">#{{ $emp->id }}</td>
                            <td>
                                <a href="{{ route('employees.show', $emp->id) }}" class="fw-semibold text-dark text-decoration-none">
                                    {{ $emp->full_name }}
                                </a>
                            </td>
                            <td>{{ $emp->company?->name }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $emp->location?->name ?? 'Head Office' }}</span>
                            </td>
                            <td>{{ $emp->department?->name ?? 'General' }}</td>
                            <td>
                                @if($emp->employee_type === 'Intern')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Intern</span>
                                @elseif($emp->employee_type === 'Permanent')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Permanent</span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $emp->employee_type }}</span>
                                @endif
                            </td>
                            <td>{{ $emp->date_of_joining?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($emp->status === 'Active')
                                    <span class="badge bg-success text-white">Active</span>
                                @else
                                    <span class="badge bg-secondary">{{ $emp->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($emp->code_generation_method === 'Manual')
                                    <span class="badge bg-purple-subtle text-purple border" title="Manual Admin Allocation">Manual</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">Automatic</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $emp->created_at?->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('employees.show', [$emp->id, '#lifecycle']) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="View Lifecycle">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2 text-secondary opacity-50"></i>
                                <div class="fw-semibold">No Employee Code Records Found</div>
                                <div class="small">Try adjusting search parameters or filter criteria.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} records</small>
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

