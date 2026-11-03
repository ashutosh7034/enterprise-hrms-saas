@extends('layouts.app')

@section('title', 'Report Export History Audit Trail')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}" class="text-teal text-decoration-none">Reports Hub</a></li>
            <li class="breadcrumb-item active fw-medium text-dark">Export History</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Report Export History & Audit Trail</h4>
            <p class="text-muted small mb-0">Immutable compliance record of all exported datasets, actors, row counts, and IP addresses</p>
        </div>
        <div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Hub
            </a>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-dark"><i class="bi bi-clock-history me-2 text-teal"></i>Export Logs ({{ $exports->total() }})</span>
        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Immutable Audit Storage</span>
    </div>
    <div class="card-body p-0">
        @if($exports->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-file-earmark-arrow-down fs-1 text-secondary d-block mb-2"></i>
                <h6 class="fw-semibold">No Report Exports Recorded</h6>
                <p class="small text-muted mb-0">When reports are exported via CSV, Excel, or PDF, an audit trail entry will be logged here.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>User / Actor</th>
                            <th>Report Type</th>
                            <th>Format</th>
                            <th>Row Count</th>
                            <th>File Size</th>
                            <th>IP Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exports as $log)
                            <tr>
                                <td class="code-font text-muted small">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                    <div class="text-muted" style="font-size: 11px;">{{ $log->user?->email }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border code-font">{{ $log->report_type }}</span></td>
                                <td><span class="badge bg-secondary text-white text-uppercase" style="font-size: 10px;">{{ $log->file_format }}</span></td>
                                <td><strong>{{ number_format($log->row_count) }}</strong></td>
                                <td>{{ $log->file_size_kb }} KB</td>
                                <td class="code-font text-muted small">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ $log->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($exports->hasPages())
                <div class="p-3 border-top">
                    {{ $exports->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

