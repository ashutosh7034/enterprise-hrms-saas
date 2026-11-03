@extends('layouts.app')

@section('title', 'Central Blacklist & Whitelist')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Central Cross-Company Blacklist & Whitelist</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Unified trust registry protecting Vishal Enterprise & Vrishal Eng. Pvt. Ltd. from severe misconduct, fraud, and absconding.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('blacklist.match-reviews') }}" class="btn btn-outline-secondary position-relative">
            <i class="bi bi-fingerprint me-1"></i> Identity Match Queue
            @if($pendingReviewsCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $pendingReviewsCount }}
                </span>
            @endif
        </a>
        <a href="{{ route('blacklist.create') }}" class="btn btn-danger">
            <i class="bi bi-shield-slash-fill me-1"></i> Propose Blacklist Entry
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Active Enforced Blacklist</div>
                    <div style="font-size: 24px; font-weight: 700; color: #991B1B;">{{ $activeCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #FEE2E2; color: #DC2626; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-shield-x fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Pending HO Authorization</div>
                    <div style="font-size: 24px; font-weight: 700; color: #B45309;">{{ $pendingCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Whitelisted / Cleared</div>
                    <div style="font-size: 24px; font-weight: 700; color: #065F46;">{{ $whitelistedCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Pending Identity Matches</div>
                    <div style="font-size: 24px; font-weight: 700; color: #4338CA;">{{ $pendingReviewsCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #E0E7FF; color: #4F46E5; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-person-exclamation fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('blacklist.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, PAN, phone, Aadhaar masked, prior code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="reason_category" class="form-select form-select-sm">
                    <option value="">All Reason Categories</option>
                    <option value="Serious Misconduct" {{ request('reason_category') === 'Serious Misconduct' ? 'selected' : '' }}>Serious Misconduct</option>
                    <option value="Financial / Asset Misconduct" {{ request('reason_category') === 'Financial / Asset Misconduct' ? 'selected' : '' }}>Financial / Asset Misconduct</option>
                    <option value="Absconding" {{ request('reason_category') === 'Absconding' ? 'selected' : '' }}>Absconding</option>
                    <option value="Policy Termination" {{ request('reason_category') === 'Policy Termination' ? 'selected' : '' }}>Policy Termination</option>
                    <option value="Fraudulent Documents" {{ request('reason_category') === 'Fraudulent Documents' ? 'selected' : '' }}>Fraudulent Documents</option>
                    <option value="Identity Fraud" {{ request('reason_category') === 'Identity Fraud' ? 'selected' : '' }}>Identity Fraud</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="approval_status" class="form-select form-select-sm">
                    <option value="">All Approval Statuses</option>
                    <option value="Approved" {{ request('approval_status') === 'Approved' ? 'selected' : '' }}>Approved (Active)</option>
                    <option value="Pending Review" {{ request('approval_status') === 'Pending Review' ? 'selected' : '' }}>Pending Review</option>
                    <option value="Rejected" {{ request('approval_status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-brand btn-sm flex-fill">Filter</button>
                @if(request()->anyFilled(['search', 'reason_category', 'approval_status']))
                    <a href="{{ route('blacklist.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Central Registry Table -->
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span style="font-weight: 600;">Cross-Company Registry Records</span>
        <span class="badge bg-light text-dark border">Enforced across VE & VRE</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Individual Details</th>
                    <th>Identity Identifiers</th>
                    <th>Prior Entity & Code</th>
                    <th>Category & Evidence</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #0F172A;">{{ $rec->name }}</div>
                            <div class="text-muted" style="font-size: 12px;">
                                DOB: {{ $rec->date_of_birth ? $rec->date_of_birth->format('d M Y') : 'Not on file' }}
                            </div>
                        </td>
                        <td>
                            <div class="code-font" style="font-size: 12px;">PAN: {{ $rec->pan_number ?? '—' }}</div>
                            <div class="code-font text-muted" style="font-size: 11.5px;">Aadhaar: {{ $rec->aadhaar_masked ?? '—' }}</div>
                            <div class="text-muted" style="font-size: 11.5px;">Phone: {{ $rec->phone ?? '—' }}</div>
                        </td>
                        <td>
                            @if($rec->previousCompany)
                                <span class="badge bg-light text-dark border">{{ $rec->previousCompany->code }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            @if($rec->previous_employee_code)
                                <div class="code-font text-muted" style="font-size: 11px;">{{ $rec->previous_employee_code }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger border mb-1">{{ $rec->reason_category }}</span>
                            <div class="text-muted text-truncate" style="max-width: 250px; font-size: 12px;" title="{{ $rec->evidence_notes }}">
                                {{ $rec->evidence_notes }}
                            </div>
                            @if($rec->requestedBy)
                                <div class="text-muted mt-1" style="font-size: 11px;">
                                    <i class="bi bi-person me-1"></i>Req by: {{ $rec->requestedBy->name }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if(!$rec->is_active && $rec->whitelisted_at)
                                <span class="status-pill status-pill-success" title="Whitelisted by {{ $rec->whitelistedBy?->name }}: {{ $rec->whitelist_justification }}">
                                    <span class="dot"></span> Whitelisted
                                </span>
                            @elseif($rec->approval_status === 'Approved')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Blacklisted</span>
                            @elseif($rec->approval_status === 'Pending Review')
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending Approval</span>
                            @elseif($rec->approval_status === 'Rejected')
                                <span class="status-pill status-pill-info"><span class="dot"></span> Rejected</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if(auth()->user()->isHoAdmin())
                                    @if($rec->approval_status === 'Pending Review')
                                        <form method="POST" action="{{ route('blacklist.approve', $rec->id) }}" class="d-inline" onsubmit="return confirm('Authorize this candidate onto the Central Blacklist?');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Approve Blacklist">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" title="Reject Request" onclick="openRejectModal({{ $rec->id }})">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    @endif

                                    @if($rec->is_active && $rec->approval_status === 'Approved')
                                        <button type="button" class="btn btn-outline-success btn-sm" title="Whitelist / Clear" onclick="openWhitelistModal({{ $rec->id }}, '{{ addslashes($rec->name) }}')">
                                            <i class="bi bi-shield-check me-1"></i> Whitelist
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-check display-6 d-block mb-2 text-success"></i>
                            <div style="font-size: 15px; font-weight: 500;">Central Blacklist is clear of entries matching search</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $records->links() }}
        </div>
    @endif
</div>

<!-- Modal: Whitelist Individual -->
<div class="modal fade" id="whitelistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="whitelistForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-success"><i class="bi bi-shield-check me-1"></i> Whitelist / Clear Individual</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size: 13px; color: #475569;">
                        Clearing <strong><span id="whitelistName"></span></strong> will allow candidate to pass onboarding checks across all company entities.
                        The record is permanently retained in the audit archive.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Mandatory Clearance Justification <span class="text-danger">*</span></label>
                        <textarea name="whitelist_justification" rows="3" class="form-control" placeholder="Provide authorized justification, legal exoneration, settlement, or executive pardon rationale..." required minlength="10"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm Whitelist Clearance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reject Request -->
<div class="modal fade" id="rejectRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectRequestForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Reject Blacklist Proposal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" rows="3" class="form-control" placeholder="Explain why the proposal does not meet criteria for central blacklisting..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Reject Proposal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openWhitelistModal(recordId, name) {
        const form = document.getElementById('whitelistForm');
        form.action = '/blacklist/' + recordId + '/whitelist';
        document.getElementById('whitelistName').innerText = name;
        const modal = new bootstrap.Modal(document.getElementById('whitelistModal'));
        modal.show();
    }

    function openRejectModal(recordId) {
        const form = document.getElementById('rejectRequestForm');
        form.action = '/blacklist/' + recordId + '/reject';
        const modal = new bootstrap.Modal(document.getElementById('rejectRequestModal'));
        modal.show();
    }
</script>
@endsection

