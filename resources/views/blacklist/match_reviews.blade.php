@extends('layouts.app')

@section('title', 'Identity Match Review Queue')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('blacklist.index') }}">Central Blacklist</a></li>
                <li class="breadcrumb-item active" aria-current="page">Identity Match Reviews</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Identity Match Review Queue</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Fuzzy and token match alerts flagged during onboarding against the Central Blacklist registry.</p>
    </div>
    <a href="{{ route('blacklist.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-shield-slash me-1"></i> Central Registry
    </a>
</div>

<!-- Filters -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('blacklist.match-reviews') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Review Statuses</option>
                    <option value="Pending Review" {{ request('status') === 'Pending Review' ? 'selected' : '' }}>Pending Review</option>
                    <option value="Confirmed Match" {{ request('status') === 'Confirmed Match' ? 'selected' : '' }}>Confirmed Match</option>
                    <option value="False Positive" {{ request('status') === 'False Positive' ? 'selected' : '' }}>False Positive</option>
                    <option value="Requires More Information" {{ request('status') === 'Requires More Information' ? 'selected' : '' }}>Requires More Information</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="match_type" class="form-select form-select-sm">
                    <option value="">All Match Types</option>
                    <option value="Confirmed Match" {{ request('match_type') === 'Confirmed Match' ? 'selected' : '' }}>Confirmed Match (100% Token Match)</option>
                    <option value="Potential Match" {{ request('match_type') === 'Potential Match' ? 'selected' : '' }}>Potential Match (Fuzzy Levenshtein)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-brand btn-sm w-100">Filter Queue</button>
            </div>
            @if(request()->anyFilled(['status', 'match_type']))
                <div class="col-md-2">
                    <a href="{{ route('blacklist.match-reviews') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Queue Table -->
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span style="font-weight: 600;">Match Incidents ({{ $reviews->total() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Candidate / Employee</th>
                    <th>Matched Blacklist Entry</th>
                    <th>Match Type & Score</th>
                    <th>Evaluation Details</th>
                    <th>Resolution Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $rev)
                    <tr>
                        <td class="code-font fw-bold">#{{ $rev->id }}</td>
                        <td>
                            @if($rev->employee)
                                <a href="{{ route('employees.show', $rev->employee->id) }}" class="fw-bold text-decoration-none">
                                    {{ $rev->employee->full_name }}
                                </a>
                                <div class="code-font text-muted" style="font-size: 11.5px;">{{ $rev->employee->employee_code }} ({{ $rev->employee->company->code }})</div>
                            @else
                                <span class="text-muted">Direct Application</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-danger">{{ $rev->blacklistRecord?->name ?? 'Record Archival' }}</div>
                            <div class="text-muted" style="font-size: 11.5px;">
                                Category: {{ $rev->blacklistRecord?->reason_category ?? 'Misconduct' }}
                                @if($rev->blacklistRecord?->previousCompany)
                                    ({{ $rev->blacklistRecord->previousCompany->code }})
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($rev->match_type === 'Confirmed Match')
                                <span class="badge bg-danger-subtle text-danger border">
                                    <i class="bi bi-shield-x me-1"></i> Confirmed ({{ $rev->match_score }}%)
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Potential ({{ $rev->match_score }}%)
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size: 12px; color: #475569;">
                                {{ $rev->match_details['reason'] ?? 'Candidate attribute correlation flagged by engine' }}
                            </div>
                        </td>
                        <td>
                            @if($rev->status === 'Pending Review')
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending Review</span>
                            @elseif($rev->status === 'Confirmed Match')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Confirmed Blacklist</span>
                            @elseif($rev->status === 'False Positive')
                                <span class="status-pill status-pill-success"><span class="dot"></span> False Positive (Cleared)</span>
                            @else
                                <span class="status-pill status-pill-info"><span class="dot"></span> {{ $rev->status }}</span>
                            @endif
                            @if($rev->reviewer)
                                <div class="text-muted mt-1" style="font-size: 11px;">
                                    By: {{ $rev->reviewer->name }} ({{ $rev->reviewed_at?->format('d M Y') }})
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($rev->status === 'Pending Review')
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openResolveModal({{ $rev->id }}, '{{ addslashes($rev->employee?->full_name ?? 'Candidate') }}')">
                                    Resolve Case
                                </button>
                            @else
                                <span class="text-muted" style="font-size: 12px;">Closed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-fingerprint display-6 d-block mb-2 text-secondary"></i>
                            <div style="font-size: 15px; font-weight: 500;">No identity match reviews in queue</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reviews->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $reviews->links() }}
        </div>
    @endif
</div>

<!-- Modal: Resolve Review -->
<div class="modal fade" id="resolveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="resolveForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Resolve Identity Match: <span id="candidateNameSpan"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Investigation Finding <span class="text-danger">*</span></label>
                        <select name="resolution" class="form-select form-select-sm" required>
                            <option value="">Select Resolution</option>
                            <option value="False Positive">False Positive — Different individual (Allow candidate)</option>
                            <option value="Confirmed Match">Confirmed Match — Verified same individual (Terminate / Offboard)</option>
                            <option value="Requires More Information">Requires More Information / Physical Hearing</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Resolution Remarks & Justification <span class="text-danger">*</span></label>
                        <textarea name="resolution_remarks" rows="3" class="form-control" placeholder="Provide investigation details, verification documentation examined..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand btn-sm">Confirm Resolution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openResolveModal(reviewId, candidateName) {
        const form = document.getElementById('resolveForm');
        form.action = '/blacklist/reviews/' + reviewId + '/resolve';
        document.getElementById('candidateNameSpan').innerText = candidateName;
        const modal = new bootstrap.Modal(document.getElementById('resolveModal'));
        modal.show();
    }
</script>
@endsection

