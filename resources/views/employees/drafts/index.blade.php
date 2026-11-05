@extends('layouts.app')

@section('title', 'Onboarding Drafts — Workforce Registration')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 13px;">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Workforce Directory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Onboarding Drafts</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history text-teal me-2"></i>Onboarding Drafts</h4>
                <span class="badge bg-teal text-white rounded-pill px-2.5 py-1" style="background-color: #0D9488; font-size: 11px;">
                    {{ $stats['active_drafts'] }} Active In-Progress
                </span>
            </div>
            <p class="text-muted mb-0 mt-1" style="font-size: 13.5px;">Safely auto-saved candidate dossiers. Resume onboarding at the exact step left off or discard abandoned drafts.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('employees.create') }}" class="btn btn-dark btn-sm px-3 fw-semibold shadow-xs">
                <i class="bi bi-person-plus me-1"></i> + New Candidate Onboarding
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold shadow-xs">
                <i class="bi bi-people me-1"></i> Active Workforce Directory
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Mini KPI Stat Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="card border rounded-3 shadow-xs p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Active Drafts</div>
                        <h4 class="fw-bold text-dark mb-0 mt-1">{{ $stats['active_drafts'] }}</h4>
                        <div class="text-muted" style="font-size: 11px;">Pending submission</div>
                    </div>
                    <div class="p-2.5 rounded-3 bg-teal-subtle text-teal" style="background: #F0FDFA; color: #0D9488;">
                        <i class="bi bi-pencil-square fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card border rounded-3 shadow-xs p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Submitted / Finalized</div>
                        <h4 class="fw-bold text-success mb-0 mt-1">{{ $stats['submitted'] }}</h4>
                        <div class="text-muted" style="font-size: 11px;">Converted to active roster</div>
                    </div>
                    <div class="p-2.5 rounded-3 bg-success-subtle text-success">
                        <i class="bi bi-check2-all fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="card border rounded-3 shadow-xs p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-medium">Total Ingested Dossiers</div>
                        <h4 class="fw-bold text-primary mb-0 mt-1">{{ $stats['total'] }}</h4>
                        <div class="text-muted" style="font-size: 11px;">Historical candidate forms</div>
                    </div>
                    <div class="p-2.5 rounded-3 bg-primary-subtle text-primary">
                        <i class="bi bi-file-earmark-person fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border rounded-3 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('employees.drafts.index') }}" class="row g-2 align-items-center">
                <div class="col-md-7 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Search by candidate name, phone, email, or draft ID..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Active Drafts Only</option>
                        <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Submitted Records</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                    </select>
                </div>
                <div class="col-md-2 col-6 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-semibold">
                        Filter
                    </button>
                    @if(request()->hasAny(['q', 'status']))
                        <a href="{{ route('employees.drafts.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Drafts Table -->
    <div class="card border rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                <thead class="bg-light text-secondary border-bottom">
                    <tr>
                        <th class="ps-3 py-3" style="width: 240px;">Candidate Name</th>
                        <th style="width: 150px;">Draft ID</th>
                        <th style="width: 170px;">Branch / Dept</th>
                        <th style="width: 180px;">Dossier Progress</th>
                        <th style="width: 130px;">Last Saved</th>
                        <th style="width: 140px;">Created By</th>
                        <th style="width: 110px;">Status</th>
                        <th class="text-end pe-3" style="width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drafts as $d)
                        <tr>
                            <!-- Candidate Name -->
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                                         style="width: 36px; height: 36px; font-size: 13px; background-color: #0D9488; flex-shrink: 0;">
                                        {{ strtoupper(substr($d->candidate_display_name, 0, 1) ?: 'C') }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $d->candidate_display_name }}</div>
                                        <small class="text-muted d-block" style="font-size: 11.5px;">
                                            {{ $d->phone ?: ($d->email ?: 'No contact tokens entered') }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- Draft ID -->
                            <td>
                                <span class="badge bg-light text-dark font-monospace border px-2 py-1 shadow-xs" style="font-size: 11.5px;">
                                    {{ $d->draft_id }}
                                </span>
                                <div class="text-muted" style="font-size: 10px; margin-top: 2px;">
                                    v{{ $d->version }}
                                </div>
                            </td>

                            <!-- Branch / Dept -->
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 160px;">
                                    {{ $d->location?->name ?? 'Unassigned Branch' }}
                                </div>
                                <small class="text-muted text-truncate d-block" style="max-width: 160px; font-size: 11.5px;">
                                    {{ $d->department?->name ?? 'Unassigned Dept' }}
                                </small>
                            </td>

                            <!-- Dossier Progress -->
                            <td>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10.5px;">
                                        Step {{ $d->current_step }} of 16
                                    </span>
                                    <span class="fw-bold text-dark" style="font-size: 11.5px;">{{ $d->completion_percentage }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar {{ $d->completion_percentage >= 80 ? 'bg-success' : ($d->completion_percentage >= 40 ? 'bg-primary' : 'bg-warning') }}" 
                                         role="progressbar" 
                                         style="width: {{ $d->completion_percentage }}%;" 
                                         aria-valuenow="{{ $d->completion_percentage }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </td>

                            <!-- Last Saved -->
                            <td>
                                <div class="text-dark fw-medium" style="font-size: 12.5px;">
                                    {{ $d->last_saved_at ? $d->last_saved_at->format('h:i A') : '—' }}
                                </div>
                                <small class="text-muted" style="font-size: 11px;">
                                    {{ $d->last_saved_at ? $d->last_saved_at->diffForHumans() : '' }}
                                </small>
                            </td>

                            <!-- Created By -->
                            <td>
                                <div class="text-dark fw-medium text-truncate" style="max-width: 130px; font-size: 12.5px;">
                                    {{ $d->creator?->name ?? 'System Admin' }}
                                </div>
                                <small class="text-muted" style="font-size: 11px;">
                                    {{ $d->created_at->format('d M Y') }}
                                </small>
                            </td>

                            <!-- Status -->
                            <td>
                                @if($d->status === 'submitted')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">
                                        <i class="bi bi-check-circle me-1"></i> Submitted
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill" style="font-size: 11px;">
                                        <i class="bi bi-clock me-1"></i> Draft
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="text-end pe-3">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    @if($d->status !== 'submitted')
                                        <a href="{{ route('employees.create', ['draft_id' => $d->draft_id]) }}" 
                                           class="btn btn-sm btn-teal text-white fw-semibold px-2.5 py-1 shadow-xs" 
                                           style="background-color: #0D9488; font-size: 12px;"
                                           title="Continue onboarding candidate">
                                            <i class="bi bi-arrow-right-circle me-1"></i> Continue
                                        </a>
                                    @else
                                        @if($d->employee_id)
                                            <a href="{{ route('employees.show', $d->employee_id) }}" class="btn btn-sm btn-outline-primary px-2.5 py-1" style="font-size: 12px;">
                                                <i class="bi bi-person-check me-1"></i> View Profile
                                            </a>
                                        @endif
                                    @endif

                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary px-2 py-1 view-draft-btn" 
                                            data-id="{{ $d->draft_id }}" 
                                            title="View Draft Summary">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    @if($d->status !== 'submitted')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger px-2 py-1 delete-draft-btn" 
                                                data-id="{{ $d->draft_id }}" 
                                                data-name="{{ $d->candidate_display_name }}"
                                                title="Delete In-Progress Draft">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-journal-x display-4 d-block mb-3 text-secondary opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">No Onboarding Drafts Found</h6>
                                <p class="text-muted small mb-3">When you enter candidate data in the onboarding wizard, drafts will automatically be saved and displayed here.</p>
                                <a href="{{ route('employees.create') }}" class="btn btn-sm btn-dark px-3 fw-semibold shadow-xs">
                                    <i class="bi bi-person-plus me-1"></i> Start New Dossier
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($drafts->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Showing {{ $drafts->firstItem() }} to {{ $drafts->lastItem() }} of {{ $drafts->total() }} drafts</span>
                {{ $drafts->links() }}
            </div>
        @endif
    </div>
</div>

<!-- View Draft Snapshot Modal -->
<div class="modal fade" id="draftSnapshotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2.5 px-3 border-bottom">
                <h6 class="modal-title fw-bold" id="draftModalTitle">
                    <i class="bi bi-journal-text text-teal me-2"></i>Draft Snapshot
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="draftModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-teal" role="status"></div>
                    <div class="small text-muted mt-2">Loading draft details...</div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <a href="#" id="modalResumeBtn" class="btn btn-sm btn-teal text-white px-3" style="background-color: #0D9488;">
                    <i class="bi bi-arrow-right-circle me-1"></i> Continue Editing
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDraftConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="deleteDraftForm" action="">
                @csrf
                @method('DELETE')
                <div class="modal-header py-2.5 px-3 border-bottom bg-danger text-white">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Draft Deletion
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-2">Are you sure you want to permanently delete this onboarding draft?</p>
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="fw-bold text-dark" id="deleteCandidateName">—</div>
                        <div class="small text-muted font-monospace" id="deleteDraftId">—</div>
                    </div>
                    <p class="text-danger small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i> This action cannot be undone. All entered draft information and temporary uploaded files will be permanently purged.
                    </p>
                </div>
                <div class="modal-footer py-2 px-3 border-top bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger px-3">
                        <i class="bi bi-trash me-1"></i> Yes, Delete Draft
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Delete Draft Modal Hook
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteDraftConfirmModal'));
    const deleteForm = document.getElementById('deleteDraftForm');
    const deleteNameEl = document.getElementById('deleteCandidateName');
    const deleteIdEl = document.getElementById('deleteDraftId');

    document.querySelectorAll('.delete-draft-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            deleteNameEl.textContent = name;
            deleteIdEl.textContent = id;
            deleteForm.action = `/employees/drafts/${id}`;
            deleteModal.show();
        });
    });

    // View Snapshot Modal Hook
    const snapshotModal = new bootstrap.Modal(document.getElementById('draftSnapshotModal'));
    const snapshotBody = document.getElementById('draftModalBody');
    const resumeBtn = document.getElementById('modalResumeBtn');

    document.querySelectorAll('.view-draft-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            snapshotBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-teal" role="status"></div>
                    <div class="small text-muted mt-2">Loading draft details...</div>
                </div>
            `;
            snapshotModal.show();

            fetch(`/employees/drafts/${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.draft) {
                        const d = data.draft;
                        resumeBtn.href = `/employees/create?draft_id=${d.draft_id}`;
                        snapshotBody.innerHTML = `
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border mb-3">
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">${d.candidate_name}</h5>
                                    <small class="text-muted font-monospace">${d.draft_id}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-teal text-white rounded-pill px-2.5 py-1" style="background:#0D9488;">Step ${d.current_step} of 16</span>
                                    <div class="small fw-bold text-dark mt-1">${d.completion_percentage}% Complete</div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-2.5 rounded border bg-white">
                                        <div class="small text-muted">Branch / Plant</div>
                                        <div class="fw-semibold text-dark">${d.location || 'Not Specified'}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2.5 rounded border bg-white">
                                        <div class="small text-muted">Department</div>
                                        <div class="fw-semibold text-dark">${d.department || 'Not Specified'}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2.5 rounded border bg-white">
                                        <div class="small text-muted">Phone Number</div>
                                        <div class="fw-semibold text-dark">${d.phone || '—'}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-2.5 rounded border bg-white">
                                        <div class="small text-muted">Email Address</div>
                                        <div class="fw-semibold text-dark">${d.email || '—'}</div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="p-2.5 rounded border bg-white">
                                        <div class="small text-muted">Attached Documents</div>
                                        <div class="fw-semibold text-dark">${(d.uploaded_files && d.uploaded_files.documents) ? d.uploaded_files.documents.length : 0} file(s) attached</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(() => {
                    snapshotBody.innerHTML = `<div class="alert alert-danger mb-0">Failed to load draft details.</div>`;
                });
        });
    });
});
</script>
@endpush
@endsection

