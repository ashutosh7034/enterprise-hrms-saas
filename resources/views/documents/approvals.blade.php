@extends('layouts.app')

@section('title', 'Document Authorization Queue')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Document Authorization Queue</h4>
            <p class="text-muted small mb-0">Review, approve, or reject pending regulatory and organizational employee letters</p>
        </div>
        <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Hub
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card card-custom border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-hourglass-split text-warning me-2"></i>Pending Documents ({{ $pendingDocuments->total() }})</h6>
        </div>
        <div class="card-body p-0">
            @if($pendingDocuments->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-shield-check fs-1 text-teal d-block mb-2"></i>
                    <p class="mb-0 fw-semibold text-dark">All clear! No documents pending authorization.</p>
                    <p class="small text-muted mb-0">All employee letters and forms have been processed.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small">Doc Number</th>
                                <th class="small">Document Title</th>
                                <th class="small">Employee Recipient</th>
                                <th class="small">Department</th>
                                <th class="small">Requested By</th>
                                <th class="small">Requested At</th>
                                <th class="small text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingDocuments as $doc)
                                <tr>
                                    <td class="font-monospace small fw-semibold text-dark">{{ $doc->document_number }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark small">{{ $doc->title }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $doc->category }}</div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">{{ $doc->employee?->full_name }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $doc->employee?->employee_code }}</div>
                                    </td>
                                    <td class="small">{{ $doc->employee?->department?->name ?? 'N/A' }}</td>
                                    <td class="small">{{ $doc->requester?->name ?? 'System' }}</td>
                                    <td class="small text-muted">{{ $doc->created_at?->format('d M Y, h:i A') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.documents.show', $doc) }}" class="btn btn-outline-secondary btn-sm" title="Review Content">
                                                <i class="bi bi-eye me-1"></i> Review
                                            </a>
                                            <form method="POST" action="{{ route('admin.documents.approve', $doc) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-teal btn-sm ms-1" title="Approve & Publish">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline-danger btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $doc->id }}" title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div class="modal fade text-start" id="rejectModal{{ $doc->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <form method="POST" action="{{ route('admin.documents.reject', $doc) }}">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Reject {{ $doc->document_number }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted">Provide the specific justification for rejecting this document draft:</p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                                                                <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Specify reason..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($pendingDocuments->hasPages())
                    <div class="p-3 border-top">
                        {{ $pendingDocuments->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

