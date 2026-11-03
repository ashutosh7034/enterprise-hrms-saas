@extends('layouts.app')

@section('title', $document->document_number . ' - ' . $document->title)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="font-monospace small px-2 py-1 rounded bg-dark text-white fw-semibold">{{ $document->document_number }}</span>
                @if($document->status === 'Issued')
                    <span class="badge bg-success">Issued & Verified</span>
                @elseif($document->status === 'Pending Approval')
                    <span class="badge bg-warning text-dark">Pending Authorization</span>
                @elseif($document->status === 'Cancelled')
                    <span class="badge bg-secondary">Cancelled</span>
                @elseif($document->status === 'Rejected')
                    <span class="badge bg-danger">Rejected</span>
                @else
                    <span class="badge bg-light text-dark border">{{ $document->status }}</span>
                @endif
                <span class="badge bg-light text-muted border">{{ $document->category }}</span>
            </div>
            <h4 class="fw-bold mb-0" style="color: #0F172A;">{{ $document->title }}</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.documents.history') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            @if(in_array($document->status, ['Issued', 'Generated']))
                <a href="{{ route('admin.documents.download', $document) }}" class="btn btn-teal btn-sm shadow-sm">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            @endif
            @if($document->status === 'Pending Approval')
                <form method="POST" action="{{ route('admin.documents.approve', $document) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Approve & Issue
                    </button>
                </form>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-lg me-1"></i> Reject
                </button>
            @endif
            @if($document->status === 'Issued')
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-slash-circle me-1"></i> Cancel / Revoke
                </button>
            @endif
        </div>
    </div>

    <!-- Feedback Alerts -->
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

    <div class="row g-4">
        <!-- Document Snapshot Preview -->
        <div class="col-lg-8">
            <div class="card card-custom border-0 shadow-sm p-4 p-md-5" style="background-color: #ffffff; min-height: 800px; border-radius: 8px;">
                <div class="document-content-viewport" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; font-size: 14px; line-height: 1.6;">
                    {!! $document->content_snapshot_html !!}
                </div>
            </div>
        </div>

        <!-- Metadata & Audit Sidebar -->
        <div class="col-lg-4">
            <!-- Recipient Profile Box -->
            <div class="card card-custom p-3 border-0 shadow-sm mb-3">
                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-badge text-teal me-2"></i>Recipient Details</h6>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-light text-teal fw-bold d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 16px;">
                        {{ substr($document->employee?->first_name ?? 'E', 0, 1) }}
                    </div>
                    <div>
                        <div class="fw-bold text-dark">{{ $document->employee?->full_name }}</div>
                        <div class="small text-muted font-monospace">{{ $document->employee?->employee_code }}</div>
                    </div>
                </div>
                <ul class="list-unstyled small mb-0">
                    <li class="py-1 border-bottom d-flex justify-content-between">
                        <span class="text-muted">Designation:</span>
                        <span class="fw-semibold text-dark">{{ $document->employee?->designation?->name }}</span>
                    </li>
                    <li class="py-1 border-bottom d-flex justify-content-between">
                        <span class="text-muted">Department:</span>
                        <span class="fw-semibold text-dark">{{ $document->employee?->department?->name }}</span>
                    </li>
                    <li class="py-1 border-bottom d-flex justify-content-between">
                        <span class="text-muted">Location:</span>
                        <span class="fw-semibold text-dark">{{ $document->employee?->location?->name }}</span>
                    </li>
                    <li class="py-1 d-flex justify-content-between">
                        <span class="text-muted">Joining Date:</span>
                        <span class="fw-semibold text-dark">{{ $document->employee?->date_of_joining?->format('d M Y') ?? 'N/A' }}</span>
                    </li>
                </ul>
            </div>

            <!-- Verification & Security Token -->
            <div class="card card-custom p-3 border-0 shadow-sm mb-3">
                <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-shield-check text-teal me-2"></i>Verification & Security</h6>
                <div class="p-2 rounded bg-light border text-center mb-2">
                    <div class="small text-muted">Public Verification Token</div>
                    <div class="font-monospace fw-bold text-teal fs-6">{{ $document->verification_code }}</div>
                </div>
                <div class="small text-muted mb-2">
                    Anyone can verify this document's authenticity using the public verification URL:
                </div>
                <a href="{{ url('/verify-document/' . $document->verification_code) }}" target="_blank" class="small text-teal text-decoration-none d-block text-truncate">
                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ url('/verify-document/' . $document->verification_code) }}
                </a>
            </div>

            <!-- Audit Trail & Lifecycle Timestamps -->
            <div class="card card-custom p-3 border-0 shadow-sm">
                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-teal me-2"></i>Audit Lifecycle</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="py-1 border-bottom">
                        <div class="text-muted">Generated by:</div>
                        <div class="fw-semibold text-dark">{{ $document->generator?->name ?? 'System' }} on {{ $document->created_at?->format('d M Y, h:i A') }}</div>
                    </li>
                    @if($document->approved_by)
                        <li class="py-1 border-bottom">
                            <div class="text-muted">Authorized by:</div>
                            <div class="fw-semibold text-dark">{{ $document->approver?->name }} on {{ $document->approved_at?->format('d M Y, h:i A') }}</div>
                        </li>
                    @endif
                    @if($document->issued_at)
                        <li class="py-1 border-bottom">
                            <div class="text-muted">Issued to ESS:</div>
                            <div class="fw-semibold text-success">{{ $document->issued_at->format('d M Y, h:i A') }}</div>
                        </li>
                    @endif
                    @if($document->rejection_reason)
                        <li class="py-2 text-danger">
                            <div class="fw-bold">Rejection / Cancellation Reason:</div>
                            <div>{{ $document->rejection_reason }}</div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.documents.reject', $document) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Reject Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Please provide a specific justification for rejecting this document draft. This reason will be recorded permanently in the audit trail.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Specify discrepancies, policy violations, or corrections required..."></textarea>
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

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.documents.cancel', $document) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-slash-circle me-2"></i>Revoke / Cancel Issued Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Revoking an issued document will invalidate its public verification code and revoke it in the recipient's ESS portal.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Revocation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Reason for retracting or nullifying document..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Dismiss</button>
                    <button type="submit" class="btn btn-danger">Confirm Revocation</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

