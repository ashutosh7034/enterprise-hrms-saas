@extends('layouts.app')

@section('title', 'Enterprise Reports & Analytics Hub')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Enterprise Reports & Analytics Hub</h4>
        <p class="text-muted small mb-0">Centralized HRMS Regulatory, Operational & Financial Intelligence</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(auth()->user()->hasAnyRole(['ho_admin', 'hr_executive']))
            <a href="{{ route('admin.reports.bank-advice') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-bank me-1"></i> Bank Transfer Advice
            </a>
            <a href="{{ route('admin.reports.schedules') }}" class="btn btn-sm btn-outline-secondary position-relative">
                <i class="bi bi-alarm me-1"></i> Scheduled Reports
                @if($activeSchedulesCount > 0)
                    <span class="badge bg-teal ms-1" style="background-color: #0D9488;">{{ $activeSchedulesCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.reports.history') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i> Export History
            </a>
        @endif
    </div>
</div>

<!-- Search & Quick Metrics -->
<div class="card card-custom mb-4">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="reportSearchInput" class="form-control border-start-0 ps-0" placeholder="Search by report name, domain, or keyword (e.g. PF, Muster, Attrition)...">
                </div>
            </div>
            <div class="col-md-6 text-md-end text-muted small">
                <span>Domain Categories: <strong class="text-dark">{{ count($accessibleCatalog) }}</strong></span> • 
                <span>Total Accessible Reports: <strong class="text-dark">{{ array_sum(array_map('count', $accessibleCatalog)) }}</strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Categorized Report Catalogs -->
@foreach($accessibleCatalog as $category => $reports)
    <div class="mb-5 report-category-section">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h5 class="fw-bold mb-0" style="color: #0F172A;">{{ $category }}</h5>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 11px;">{{ count($reports) }} Reports</span>
        </div>
        <div class="row g-3">
            @foreach($reports as $code => $meta)
                <div class="col-md-6 col-lg-4 report-item-col" data-title="{{ strtolower($meta['name'] . ' ' . $meta['description'] . ' ' . $code) }}">
                    <div class="card card-custom h-100 p-3 hover-shadow border">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded d-flex align-items-center justify-content-center" style="background-color: #F1F5F9; width: 42px; height: 42px; flex-shrink: 0;">
                                <i class="bi {{ $meta['icon'] ?? 'bi-file-earmark-text' }} text-teal fs-5" style="color: #0D9488;"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="fw-bold mb-1 text-truncate" style="font-size: 14px; color: #0F172A;">{{ $meta['name'] }}</h6>
                                <p class="text-muted small mb-2 line-clamp-2" style="font-size: 12px; min-height: 36px; line-height: 1.4;">{{ $meta['description'] }}</p>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                    <span class="badge bg-light text-secondary border code-font" style="font-size: 10px;">{{ $code }}</span>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.reports.show', $code) }}" class="btn btn-xs btn-outline-teal" style="font-size: 11.5px; padding: 2px 8px; color: #0D9488; border-color: #0D9488;">
                                            <i class="bi bi-eye me-1"></i> View
                                        </a>
                                        <a href="{{ route('admin.reports.export', ['type' => $code, 'format' => 'csv']) }}" class="btn btn-xs btn-outline-secondary" style="font-size: 11.5px; padding: 2px 8px;" title="Quick CSV Export">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

<!-- Recent Export History Banner -->
@if(auth()->user()->hasAnyRole(['ho_admin', 'hr_executive']) && $recentExports->isNotEmpty())
    <div class="card card-custom mt-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-dark"><i class="bi bi-clock-history me-2 text-teal"></i>Recent Export Activity</span>
            <a href="{{ route('admin.reports.history') }}" class="btn btn-sm btn-link p-0 text-teal text-decoration-none">Full Export History &rarr;</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Report Type</th>
                            <th>Format</th>
                            <th>Rows</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Exported At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentExports as $exp)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $exp->report_type }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $exp->file_format }}</span></td>
                                <td>{{ number_format($exp->row_count) }}</td>
                                <td>{{ $exp->file_size_kb }} KB</td>
                                <td><span class="badge bg-success-subtle text-success">{{ $exp->status }}</span></td>
                                <td class="text-muted">{{ $exp->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('reportSearchInput');
        const reportCols = document.querySelectorAll('.report-item-col');
        const sections = document.querySelectorAll('.report-category-section');

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();

            reportCols.forEach(col => {
                const title = col.getAttribute('data-title') || '';
                if (!query || title.includes(query)) {
                    col.style.display = '';
                } else {
                    col.style.display = 'none';
                }
            });

            // Hide empty category sections
            sections.forEach(sec => {
                const visible = sec.querySelectorAll('.report-item-col:not([style*="display: none"])');
                sec.style.display = visible.length > 0 ? '' : 'none';
            });
        });
    });
</script>
@endsection

