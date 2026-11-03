@extends('layouts.app')

@section('title', 'Raw Biometric Punch Logs — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Raw Biometric Punch Staging</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Immutable raw punch transactions, duplicate fingerprint evaluation, sync latency & error logs.</p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('biometric.logs.reprocess') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-arrow-clockwise me-1"></i> Reprocess Unmapped Punches
            </button>
        </form>
        <a href="{{ route('biometric-devices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-cpu me-1"></i> Devices Registry
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('biometric.logs') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}" placeholder="Filter by date">
            </div>
            <div class="col-md-2">
                <select name="direction" class="form-select form-select-sm">
                    <option value="">All Directions</option>
                    <option value="IN" {{ request('direction') === 'IN' ? 'selected' : '' }}>IN</option>
                    <option value="OUT" {{ request('direction') === 'OUT' ? 'selected' : '' }}>OUT</option>
                    <option value="UNKNOWN" {{ request('direction') === 'UNKNOWN' ? 'selected' : '' }}>UNKNOWN</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Processed" {{ request('status') === 'Processed' ? 'selected' : '' }}>Processed</option>
                    <option value="Raw" {{ request('status') === 'Raw' ? 'selected' : '' }}>Raw</option>
                    <option value="Ignored_Duplicate" {{ request('status') === 'Ignored_Duplicate' ? 'selected' : '' }}>Ignored Duplicate</option>
                    <option value="Error" {{ request('status') === 'Error' ? 'selected' : '' }}>Error / Unmapped</option>
                    <option value="Reprocessed" {{ request('status') === 'Reprocessed' ? 'selected' : '' }}>Reprocessed</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="User ID, Code, Name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color: var(--brand-primary-dark);">Filter</button>
                <a href="{{ route('biometric.logs') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Punch Time (Original)</th>
                    <th>User ID / Employee</th>
                    <th>Direction</th>
                    <th>Ingestion Source & Device</th>
                    <th>Processing Status</th>
                    <th>Sync Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($punchLogs as $log)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><i class="bi bi-clock me-1 text-muted"></i>{{ $log->punch_time->format('d M Y, H:i:s') }}</div>
                            <span class="font-monospace text-muted" style="font-size: 10px;" title="Fingerprint SHA-256">
                                FP: {{ substr($log->punch_fingerprint, 0, 16) }}...
                            </span>
                        </td>
                        <td>
                            @if($log->employee)
                                <div class="fw-semibold text-dark">{{ $log->employee->full_name }}</div>
                                <small class="text-muted">{{ $log->employee->employee_code }} • Card: {{ $log->biometric_user_id }}</small>
                            @else
                                <div class="text-danger fw-semibold">Unresolved Employee</div>
                                <span class="badge bg-danger-subtle text-danger font-monospace" style="font-size: 11px;">ID: {{ $log->biometric_user_id }}</span>
                            @endif
                        </td>
                        <td>
                            @if($log->direction === 'IN')
                                <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-box-arrow-in-right me-1"></i> IN</span>
                            @elseif($log->direction === 'OUT')
                                <span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-box-arrow-right me-1"></i> OUT</span>
                            @else
                                <span class="badge bg-secondary text-white px-2 py-1">UNKNOWN</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium text-dark">{{ $log->source }}</div>
                            <small class="text-muted">{{ $log->device?->device_name ?? 'Webhook Direct' }}</small>
                        </td>
                        <td>
                            @if($log->processing_status === 'Processed')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Processed</span>
                            @elseif($log->processing_status === 'Reprocessed')
                                <span class="status-pill status-pill-info"><span class="dot"></span> Reprocessed</span>
                            @elseif($log->processing_status === 'Ignored_Duplicate')
                                <span class="badge bg-warning text-dark"><i class="bi bi-files me-1"></i> Duplicate</span>
                            @elseif($log->processing_status === 'Error')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Error</span>
                                @if($log->error_message)
                                    <div class="text-danger" style="font-size: 11px;">{{ $log->error_message }}</div>
                                @endif
                            @else
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Raw</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $log->sync_time->format('H:i:s') }}
                            <div style="font-size: 10px;">{{ $log->sync_time->diffInSeconds($log->punch_time) }}s lag</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-fingerprint d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No raw biometric punch logs recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($punchLogs->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $punchLogs->links() }}
        </div>
    @endif
</div>
@endsection

