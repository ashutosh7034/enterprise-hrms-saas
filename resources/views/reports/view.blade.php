@extends('layouts.app')

@section('title', $reportData['title'] . ' | HRMS Reports')

@section('content')
<div class="mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}" class="text-teal text-decoration-none">Reports Hub</a></li>
            <li class="breadcrumb-item text-muted">{{ $reportData['category'] }}</li>
            <li class="breadcrumb-item active fw-medium text-dark">{{ $reportData['title'] }}</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">{{ $reportData['title'] }}</h4>
            <p class="text-muted small mb-0">{{ $reportData['description'] }} • Generated {{ $reportData['generated_at'] }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
                <i class="bi bi-funnel me-1"></i> Filters
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-brand dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i> Export Data
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('admin.reports.export', array_merge(['type' => $type, 'format' => 'csv'], $filters)) }}">
                            <i class="bi bi-filetype-csv me-2 text-success"></i> Export as CSV (Excel UTF-8)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('admin.reports.export', array_merge(['type' => $type, 'format' => 'excel'], $filters)) }}">
                            <i class="bi bi-file-earmark-excel me-2 text-primary"></i> Export as Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('admin.reports.export', array_merge(['type' => $type, 'format' => 'pdf'], $filters)) }}">
                            <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> Export as PDF (A4)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:window.print();">
                            <i class="bi bi-printer me-2 text-secondary"></i> Print View
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Multi-Parameter Filter Panel -->
<div class="collapse {{ !empty(array_filter($filters)) ? 'show' : '' }} mb-4" id="filterPanel">
    <div class="card card-custom border p-3">
        <form method="GET" action="{{ route('admin.reports.show', $type) }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Designation</label>
                    <select name="designation_id" class="form-select form-select-sm">
                        <option value="">All Designations</option>
                        @foreach($designations as $desig)
                            <option value="{{ $desig->id }}" {{ ($filters['designation_id'] ?? '') == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Location / Site</label>
                    <select name="location_id" class="form-select form-select-sm">
                        <option value="">All Locations</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ ($filters['location_id'] ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Employee Status</label>
                    <select name="employment_status" class="form-select form-select-sm">
                        <option value="All" {{ ($filters['employment_status'] ?? '') == 'All' ? 'selected' : '' }}>All Statuses</option>
                        <option value="Active" {{ ($filters['employment_status'] ?? '') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Draft" {{ ($filters['employment_status'] ?? '') == 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Onboarding" {{ ($filters['employment_status'] ?? '') == 'Onboarding' ? 'selected' : '' }}>Onboarding</option>
                        <option value="Terminated" {{ ($filters['employment_status'] ?? '') == 'Terminated' ? 'selected' : '' }}>Terminated</option>
                        <option value="Resigned" {{ ($filters['employment_status'] ?? '') == 'Resigned' ? 'selected' : '' }}>Resigned</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Payroll Run (If applicable)</label>
                    <select name="payroll_run_id" class="form-select form-select-sm">
                        <option value="">All Batches</option>
                        @foreach($payrollRuns as $run)
                            <option value="{{ $run->id }}" {{ ($filters['payroll_run_id'] ?? '') == $run->id ? 'selected' : '' }}>
                                {{ $run->run_batch_number }} ({{ sprintf('%02d/%d', $run->month, $run->year) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-teal flex-grow-1" style="background-color: #0D9488; color: white;">
                        <i class="bi bi-filter me-1"></i> Apply
                    </button>
                    <a href="{{ route('admin.reports.show', $type) }}" class="btn btn-sm btn-light border">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Metric Indicators -->
@if(!empty($reportData['summary']))
    <div class="row g-3 mb-4">
        @foreach($reportData['summary'] as $label => $val)
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card card-custom p-3 border">
                    <div class="text-muted small fw-medium mb-1">{{ $label }}</div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $val }}</h5>
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Data Table Card -->
<div class="card card-custom">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-dark">
            <i class="bi bi-table me-2 text-teal"></i>Dataset Records ({{ count($reportData['rows']) }})
        </span>
        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Company Scoped</span>
    </div>
    <div class="card-body p-0">
        @if(empty($reportData['rows']))
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 text-secondary d-block mb-2"></i>
                <h6 class="fw-semibold">No Records Found</h6>
                <p class="small text-muted mb-0">No records matched the selected query parameters or date range.</p>
            </div>
        @else
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-hover table-striped align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 40px;">#</th>
                            @foreach($reportData['headers'] as $header)
                                <th class="text-nowrap">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['rows'] as $index => $row)
                            <tr>
                                <td class="text-muted" style="font-size: 11px;">{{ $index + 1 }}</td>
                                @foreach($row as $key => $cell)
                                    <td class="text-nowrap">
                                        @if(in_array($key, ['Status', 'eKYC Status', 'Compliance Flag', 'Clearance Status', 'Disbursement Status']))
                                            <span class="badge {{ in_array($cell, ['Active', 'Verified', 'Compliant', 'Cleared', 'Paid', 'Completed']) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                {{ $cell }}
                                            </span>
                                        @elseif(str_contains($key, 'Code') || str_contains($key, 'Tag') || str_contains($key, 'App #') || str_contains($key, 'Record ID'))
                                            <code>{{ $cell }}</code>
                                        @else
                                            {{ $cell }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

