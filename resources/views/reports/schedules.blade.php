@extends('layouts.app')

@section('title', 'Scheduled Reports Manager')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}" class="text-teal text-decoration-none">Reports Hub</a></li>
            <li class="breadcrumb-item active fw-medium text-dark">Scheduled Reports</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Scheduled Reports Manager</h4>
            <p class="text-muted small mb-0">Automate recurring dispatch of HRMS reports via Email and In-App notifications</p>
        </div>
        <div>
            <button class="btn btn-sm btn-brand" data-bs-toggle="modal" data-bs-target="#newScheduleModal">
                <i class="bi bi-plus-circle me-1"></i> New Scheduled Report
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert" style="font-size: 13px;">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert" style="font-size: 13px;">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Active Schedules Table -->
<div class="card card-custom mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-dark"><i class="bi bi-alarm me-2 text-teal"></i>Active Schedules ({{ $schedules->total() }})</span>
        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Artisan Runner: <code>reports:dispatch-scheduled</code></span>
    </div>
    <div class="card-body p-0">
        @if($schedules->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-calendar-x fs-1 text-secondary d-block mb-2"></i>
                <h6 class="fw-semibold">No Scheduled Reports Configured</h6>
                <p class="small text-muted mb-0">Click "New Scheduled Report" above to configure automated daily, weekly, or monthly report runs.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Schedule Name</th>
                            <th>Report Type</th>
                            <th>Frequency</th>
                            <th>Format</th>
                            <th>Recipients</th>
                            <th>Next Run</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $sch)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sch->name }}</div>
                                    <div class="text-muted small">Created by {{ $sch->creator?->name ?? 'Admin' }}</div>
                                </td>
                                <td><code>{{ $sch->report_type }}</code></td>
                                <td><span class="badge bg-light text-dark border">{{ $sch->frequency }}</span></td>
                                <td><span class="badge bg-secondary text-white text-uppercase" style="font-size: 10px;">{{ $sch->export_format }}</span></td>
                                <td class="text-muted small" style="max-width: 180px;">
                                    {{ implode(', ', $sch->recipients_json ?? []) }}
                                </td>
                                <td style="font-size: 12px;">
                                    {{ $sch->next_run_at ? $sch->next_run_at->format('d M Y, h:i A') : 'Pending' }}
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.reports.schedules.toggle', $sch) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs badge {{ $sch->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} border-0" style="cursor: pointer;">
                                            {{ $sch->is_active ? 'Active' : 'Paused' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form method="POST" action="{{ route('admin.reports.schedules.run-now', $sch) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-outline-teal" style="font-size: 11px; padding: 2px 8px; color: #0D9488; border-color: #0D9488;" title="Execute & Dispatch Now">
                                                <i class="bi bi-play-fill"></i> Run Now
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reports.schedules.destroy', $sch) }}" onsubmit="return confirm('Delete this schedule?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size: 11px; padding: 2px 8px;" title="Delete Schedule">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($schedules->hasPages())
                <div class="p-3 border-top">
                    {{ $schedules->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

<!-- New Schedule Modal -->
<div class="modal fade" id="newScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.reports.schedules.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="font-size: 15px; color: #0F172A;">Configure New Scheduled Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size: 13px;">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Schedule Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Monthly EPF / ECR Compliance File" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Target Report <span class="text-danger">*</span></label>
                    <select name="report_type" class="form-select form-select-sm" required>
                        @foreach($catalog as $catName => $reports)
                            <optgroup label="{{ $catName }}">
                                @foreach($reports as $c => $m)
                                    <option value="{{ $c }}">{{ $m['name'] }} ({{ $c }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-dark">Frequency <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select form-select-sm" required>
                            <option value="Daily">Daily (06:00 AM)</option>
                            <option value="Weekly">Weekly (Monday 06:00 AM)</option>
                            <option value="Monthly" selected>Monthly (1st of month)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-dark">Export Format <span class="text-danger">*</span></label>
                        <select name="export_format" class="form-select form-select-sm" required>
                            <option value="csv">CSV (Excel UTF-8)</option>
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="pdf">PDF Document</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Recipient Email Addresses <span class="text-danger">*</span></label>
                    <textarea name="recipients" class="form-control form-control-sm" rows="2" placeholder="Comma-separated emails, e.g. hr@vishal.internal, finance@vishal.internal" required>{{ auth()->user()->email }}</textarea>
                    <div class="form-text" style="font-size: 11px;">Recipients must be registered users with legitimate RBAC access.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-brand">Save & Schedule</button>
            </div>
        </form>
    </div>
</div>
@endsection

