@extends('layouts.app')

@section('title', 'Document & Letter Engine')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Letter & Form Print Engine</h4>
            <p class="text-muted small mb-0">Automated corporate communications, legal HR templates, PDF publishing & verification</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-file-earmark-ruled me-1"></i> Template Master
            </a>
            <a href="{{ route('admin.documents.generate') }}" class="btn btn-teal btn-sm px-3 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Generate Document
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-custom p-3 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Active Templates</div>
                        <h3 class="fw-bold my-1" style="color: #0F172A;">{{ number_format($stats['total_templates']) }}</h3>
                        <div class="small text-muted">Letters & Statutory Forms</div>
                    </div>
                    <div class="rounded-3 p-3 text-teal bg-light">
                        <i class="bi bi-file-earmark-text fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-custom p-3 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Generated</div>
                        <h3 class="fw-bold my-1" style="color: #0F172A;">{{ number_format($stats['total_generated']) }}</h3>
                        <div class="small text-muted">All-time issued & drafts</div>
                    </div>
                    <div class="rounded-3 p-3 text-primary bg-light">
                        <i class="bi bi-printer fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-custom p-3 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Pending Approval</div>
                        <h3 class="fw-bold my-1 {{ $stats['pending_approval'] > 0 ? 'text-warning' : 'text-muted' }}">
                            {{ number_format($stats['pending_approval']) }}
                        </h3>
                        <div class="small text-muted">Awaiting HR / HO sign-off</div>
                    </div>
                    <div class="rounded-3 p-3 text-warning bg-light">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-custom p-3 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Issued & Published</div>
                        <h3 class="fw-bold my-1 text-success">{{ number_format($stats['issued_documents']) }}</h3>
                        <div class="small text-muted">Active in ESS & Verification</div>
                    </div>
                    <div class="rounded-3 p-3 text-success bg-light">
                        <i class="bi bi-patch-check fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Pending Approvals Widget -->
        <div class="col-lg-5">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-exclamation text-warning me-2"></i>Pending Authorizations</h6>
                    <a href="{{ route('admin.documents.approvals') }}" class="small text-decoration-none">View All ({{ $stats['pending_approval'] }})</a>
                </div>
                <div class="card-body p-0">
                    @if($pendingApprovals->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check2-all fs-1 text-teal d-block mb-2"></i>
                            <p class="mb-0 small">No documents currently pending authorization.</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($pendingApprovals as $doc)
                                <div class="list-group-item px-3 py-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <span class="badge bg-light text-dark border font-monospace me-1">{{ $doc->document_number }}</span>
                                            <span class="fw-semibold text-dark">{{ $doc->title }}</span>
                                        </div>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        Employee: <strong>{{ $doc->employee?->full_name }}</strong> ({{ $doc->employee?->employee_code }}) &bull; {{ $doc->employee?->department?->name }}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted">Requested by {{ $doc->requester?->name ?? 'System' }}</span>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.documents.show', $doc) }}" class="btn btn-outline-secondary btn-sm">Review</a>
                                            <form method="POST" action="{{ route('admin.documents.approve', $doc) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-teal btn-sm ms-1">Approve</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Issued Documents Widget -->
        <div class="col-lg-7">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-teal me-2"></i>Recent Generated Documents</h6>
                    <a href="{{ route('admin.documents.history') }}" class="small text-decoration-none">Full History &rarr;</a>
                </div>
                <div class="card-body p-0">
                    @if($recentGenerated->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-excel fs-1 text-muted d-block mb-2"></i>
                            <p class="mb-0 small">No documents generated yet.</p>
                            <a href="{{ route('admin.documents.generate') }}" class="btn btn-sm btn-teal mt-2">Generate First Document</a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="small">Doc No</th>
                                        <th class="small">Document Title</th>
                                        <th class="small">Employee</th>
                                        <th class="small">Status</th>
                                        <th class="small text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentGenerated as $doc)
                                        <tr>
                                            <td class="font-monospace small fw-semibold">{{ $doc->document_number }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark small">{{ $doc->title }}</div>
                                                <div class="text-muted" style="font-size: 11px;">{{ $doc->category }}</div>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold">{{ $doc->employee?->full_name }}</div>
                                                <div class="text-muted" style="font-size: 11px;">{{ $doc->employee?->employee_code }}</div>
                                            </td>
                                            <td>
                                                @if($doc->status === 'Issued')
                                                    <span class="badge bg-success">Issued</span>
                                                @elseif($doc->status === 'Pending Approval')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($doc->status === 'Cancelled')
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @elseif($doc->status === 'Rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-light text-dark border">{{ $doc->status }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.documents.show', $doc) }}" class="btn btn-light btn-sm text-secondary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.documents.download', $doc) }}" class="btn btn-light btn-sm text-teal" title="Download PDF">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

