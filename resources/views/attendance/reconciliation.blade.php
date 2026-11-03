@extends('layouts.app')

@section('title', 'HO Attendance Compliance & Reconciliation — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">HO Attendance Compliance & Reconciliation Hub</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Reconcile supervisor intimations, hardware biometric raw logs & final attendance muster prior to payroll closing.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('attendance.index', ['date' => $date]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-calendar-check me-1"></i> View Daily Muster
        </a>
    </div>
</div>

<!-- Date Selector -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('attendance.reconciliation') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Reconciliation Target Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color: var(--brand-primary-dark);">Load Summary</button>
            </div>
        </form>
    </div>
</div>

<!-- 9-Metric KPI Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card card-custom p-3 border-start border-4 border-primary">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 10.5px;">Expected Workforce</div>
            <div class="h4 mb-0 fw-bold text-dark mt-1">{{ $summary['expected_workforce'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card card-custom p-3 border-start border-4 border-success">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 10.5px;">Employees Worked</div>
            <div class="h4 mb-0 fw-bold text-success mt-1">{{ $summary['worked_employees'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card card-custom p-3 border-start border-4 border-warning">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 10.5px;">Missing Punches</div>
            <div class="h4 mb-0 fw-bold text-warning mt-1">{{ $summary['missing_punch_count'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card card-custom p-3 border-start border-4 border-info">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 10.5px;">Duplicates Filtered</div>
            <div class="h4 mb-0 fw-bold text-info mt-1">{{ $summary['duplicate_punches_count'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card card-custom p-3 border-start border-4 border-danger">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 10.5px;">Device / Sync Errors</div>
            <div class="h4 mb-0 fw-bold text-danger mt-1">{{ $summary['unmapped_punches_count'] }}</div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card card-custom p-3 border-start border-4 border-secondary">
            <div class="text-muted small text-uppercase fw-bold" style="font-size: 10.5px;">Site Shutdowns</div>
            <div class="h4 mb-0 fw-bold text-secondary mt-1">{{ $summary['shutdown_employees'] }}</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Active Discrepancies Radar -->
    <div class="col-lg-5">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Audit Discrepancy Radar</h6>
            </div>
            <div class="card-body p-3">
                @if(count($summary['discrepancies']) > 0)
                    <div class="d-flex flex-column gap-2">
                        @foreach($summary['discrepancies'] as $disc)
                            <div class="p-3 rounded border-start border-4 {{ $disc['severity'] === 'Critical' ? 'border-danger bg-danger-subtle text-danger' : ($disc['severity'] === 'Danger' ? 'border-danger bg-light' : 'border-warning bg-light') }}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold fs-7">{{ $disc['type'] }}</span>
                                    <span class="badge {{ $disc['severity'] === 'Critical' ? 'bg-danger text-white' : ($disc['severity'] === 'Danger' ? 'bg-danger text-white' : 'bg-warning text-dark') }}" style="font-size: 10px;">{{ $disc['severity'] }}</span>
                                </div>
                                <div class="small text-muted mb-0">{{ $disc['description'] }}</div>
                                @if(isset($disc['employee_name']))
                                    <div class="small text-dark fw-medium mt-1">{{ $disc['employee_name'] }} ({{ $disc['employee_code'] }})</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-success">
                        <i class="bi bi-shield-check d-block mb-2" style="font-size: 36px;"></i>
                        <span class="fw-bold">Zero discrepancies detected.</span>
                        <div class="text-muted small">Supervisor intimation and biometric punches align 100%.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Exception Resolution Queue -->
    <div class="col-lg-7">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clipboard2-check me-2 text-primary"></i>Attendance Exception Clearance Queue</h6>
                <span class="badge bg-light text-dark border">{{ $exceptionRecords->total() }} exceptions</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Punches</th>
                            <th>Reconciliation</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exceptionRecords as $rec)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $rec->employee?->full_name }}</div>
                                    <small class="text-muted">{{ $rec->employee?->employee_code }} • {{ $rec->employee?->department?->name }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $rec->status === 'Missing Punch' ? 'bg-warning text-dark' : ($rec->status === 'Site Shutdown' ? 'bg-secondary' : 'bg-danger text-white') }}">
                                        {{ $rec->status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="small font-monospace">{{ $rec->total_punches }} punch(es)</span>
                                </td>
                                <td>
                                    @if($rec->is_reconciled)
                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check2"></i> Reconciled</span>
                                        <div class="text-muted" style="font-size: 10px;">{{ $rec->reconciliation_notes }}</div>
                                    @else
                                        <span class="badge bg-light text-muted border">Unreconciled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reconcileModal_{{ $rec->id }}">
                                        <i class="bi bi-check2-circle"></i> Resolve
                                    </button>

                                    <!-- Reconcile Modal -->
                                    <div class="modal fade text-start" id="reconcileModal_{{ $rec->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('attendance.reconcile', $rec->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Reconcile Attendance Exception</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted small">Record HO audit remarks to clear exception for <strong>{{ $rec->employee?->full_name }}</strong> on {{ $rec->attendance_date->format('d M Y') }}.</p>
                                                        <div class="mb-3">
                                                            <label class="form-label required">Audit Resolution Remarks</label>
                                                            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Verified site supervisor manual log. Employee on site duty." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Mark Reconciled</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No attendance exceptions requiring resolution for {{ $date }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($exceptionRecords->hasPages())
                <div class="card-footer bg-white border-top-0 py-2">
                    {{ $exceptionRecords->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

