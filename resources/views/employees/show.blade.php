@extends('layouts.app')

@section('title', "{$employee->full_name} ({$employee->employee_code})")

@section('content')
<!-- Header Card -->
<div class="card card-custom mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #0D9488; color: #FFFFFF; font-weight: 700; font-size: 20px; display: flex; align-items: center; justify-content: center;">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0" style="font-weight: 700; color: #0F172A;">{{ $employee->full_name }}</h4>
                        <span class="badge bg-light text-dark border code-font">{{ $employee->employee_code }}</span>
                        @if($employee->employment_status === 'Active')
                            <span class="status-pill status-pill-success"><span class="dot"></span> Active</span>
                        @elseif($employee->employment_status === 'Probation')
                            <span class="status-pill status-pill-info"><span class="dot"></span> Probation</span>
                        @else
                            <span class="status-pill status-pill-warning"><span class="dot"></span> {{ $employee->employment_status }}</span>
                        @endif
                    </div>
                    <div class="text-muted mt-1" style="font-size: 13px;">
                        <span>{{ $employee->designation?->name ?? 'Designation Unassigned' }}</span> • 
                        <span>{{ $employee->department?->name ?? 'Department Unassigned' }}</span> • 
                        <span><i class="bi bi-geo-alt"></i> {{ $employee->location?->name ?? 'Site Unassigned' }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#updateGatePassModal">
                    <i class="bi bi-pass me-1"></i> Update Gate Pass
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ekycModal">
                    <i class="bi bi-shield-check me-1"></i> Run eKYC Check
                </button>
                @if(auth()->user()->isHoAdmin() && !in_array($employee->ekyc_status, ['Verified', 'Exceptionally Verified']))
                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#exceptionModal">
                        <i class="bi bi-award me-1"></i> HO Exception Override
                    </button>
                @endif
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-brand btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit Dossier
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
            <i class="bi bi-person me-1"></i> Profile & Job Info
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="ekyc-tab" data-bs-toggle="tab" data-bs-target="#ekyc" type="button" role="tab">
            <i class="bi bi-shield-check me-1"></i> Documents & eKYC
            @if($employee->ekyc_status === 'Verified' || $employee->ekyc_status === 'Exceptionally Verified')
                <span class="badge bg-success ms-1" style="font-size: 10px;">Verified</span>
            @elseif($employee->ekyc_status === 'Grace Period Active')
                <span class="badge bg-warning text-dark ms-1" style="font-size: 10px;">Grace Active</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="gatepass-tab" data-bs-toggle="tab" data-bs-target="#gatepass" type="button" role="tab">
            <i class="bi bi-pass me-1"></i> Gate Pass History
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab">
            <i class="bi bi-laptop me-1"></i> Allocated Assets ({{ $employee->assetAllocations->where('status', 'Active')->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="fines-tab" data-bs-toggle="tab" data-bs-target="#fines" type="button" role="tab">
            <i class="bi bi-receipt me-1"></i> Damages & Recovery
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i> Prior Internal Tenures
        </button>
    </li>
</ul>

<!-- Tab Contents -->
<div class="tab-content" id="employeeTabsContent">
    
    <!-- Tab 1: Profile & Job Info -->
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white" style="font-weight: 600;">Personal Information</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Full Name</td>
                                <td style="font-weight: 600;">{{ $employee->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gender</td>
                                <td>{{ $employee->gender }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date of Birth</td>
                                <td>{{ $employee->date_of_birth ? $employee->date_of_birth->format('d M Y') : '—' }} ({{ $employee->date_of_birth ? $employee->date_of_birth->age . ' yrs' : '—' }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mobile Phone</td>
                                <td>{{ $employee->phone }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email Address</td>
                                <td>{{ $employee->email ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Permanent Address</td>
                                <td>
                                    {{ $employee->address ?? '—' }}
                                    @if($employee->city), {{ $employee->city }} @endif
                                    @if($employee->state), {{ $employee->state }} @endif
                                    @if($employee->pincode) - {{ $employee->pincode }} @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white" style="font-weight: 600;">Job & Statutory Details</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Legal Entity</td>
                                <td style="font-weight: 600;">{{ $employee->company->name }} ({{ $employee->company->code }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date of Joining</td>
                                <td>{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Department</td>
                                <td>{{ $employee->department?->name ?? '—' }} ({{ $employee->department?->code ?? '' }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Designation</td>
                                <td>{{ $employee->designation?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Employee Category</td>
                                <td>{{ $employee->category?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Onboarding Channel</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $employee->onboarding_channel }}</span>
                                    @if($employee->contractor)
                                        <div class="text-muted mt-1" style="font-size: 11.5px;">Vendor: <strong>{{ $employee->contractor->name }}</strong></div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">PAN Number</td>
                                <td class="code-font text-uppercase">{{ $employee->pan_number ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Aadhaar (UIDAI)</td>
                                <td class="code-font">{{ $employee->aadhaar_masked ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gate Pass Number</td>
                                <td class="code-font">{{ $employee->gate_pass_number ?? 'No Active Pass' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Documents & eKYC -->
    <div class="tab-pane fade" id="ekyc" role="tabpanel">
        <!-- Status Banner -->
        @if($employee->ekyc_status === 'Verified')
            <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                <div>
                    <h6 class="mb-0 fw-bold">Identity Verified</h6>
                    <p class="mb-0" style="font-size: 13px;">Employee statutory identity has been verified and meets enterprise KYC compliance standards.</p>
                </div>
            </div>
        @elseif($employee->ekyc_status === 'Exceptionally Verified')
            <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
                <i class="bi bi-award-fill fs-3 text-info"></i>
                <div>
                    <h6 class="mb-0 fw-bold">Exceptionally Verified (HO Admin Authorization)</h6>
                    <p class="mb-0" style="font-size: 13px;">
                        Approved by HO Admin. Justification: "<em>{{ $employee->ekyc_exception_remarks }}</em>"
                    </p>
                </div>
            </div>
        @elseif($employee->ekyc_status === 'Grace Period Active')
            <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">Grace Period Active</h6>
                        <p class="mb-0" style="font-size: 13px;">
                            Verification failed on prior attempt. Resubmission grace period active until <strong>{{ $employee->ekyc_grace_period_ends_at?->format('d M Y') }}</strong>
                            ({{ max(0, now()->diffInDays($employee->ekyc_grace_period_ends_at, false)) }} days remaining).
                        </p>
                    </div>
                </div>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#ekycModal">Re-verify Now</button>
            </div>
        @elseif($employee->ekyc_status === 'Verification Failed')
            <div class="alert alert-danger d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-x-octagon-fill fs-3 text-danger"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">Maximum Verification Attempts Exceeded (3/3)</h6>
                        <p class="mb-0" style="font-size: 13px;">Standard eKYC attempts have been exhausted. Requires HO Admin manual exception override or regulatory off-boarding.</p>
                    </div>
                </div>
                @if(auth()->user()->isHoAdmin())
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#exceptionModal">HO Override</button>
                @endif
            </div>
        @endif

        <div class="row g-4 mb-4">
            <!-- Upload New Document Form -->
            <div class="col-md-5">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white" style="font-weight: 600;">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Verified Document
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('documents.store', $employee->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 500;">Document Type <span class="text-danger">*</span></label>
                                <select name="document_type" class="form-select form-select-sm" required>
                                    <option value="">Select Document Type</option>
                                    <option value="Aadhaar Card">Aadhaar Card</option>
                                    <option value="PAN Card">PAN Card</option>
                                    <option value="Photograph">Passport Photograph</option>
                                    <option value="Bank Account Proof">Bank Account Proof / Cheque</option>
                                    <option value="Education Certificate">Education Certificate</option>
                                    <option value="Experience Certificate">Experience Certificate</option>
                                    <option value="Police Verification">Police Verification Certificate</option>
                                    <option value="Medical Fitness Certificate">Medical Fitness Certificate</option>
                                    <option value="Other">Other Supporting Document</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 500;">Document / Reference Number</label>
                                <input type="text" name="document_number" class="form-control form-control-sm code-font" placeholder="e.g. DOC-99482">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 500;">Attach File (PDF, JPG, PNG - Max 5MB) <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-weight: 500;">Remarks / Purpose</label>
                                <textarea name="remarks" rows="2" class="form-control form-control-sm" placeholder="Notes on document validity..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-brand btn-sm w-100">
                                <i class="bi bi-upload me-1"></i> Upload to Secure Storage
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Uploaded Documents Repository -->
            <div class="col-md-7">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span style="font-weight: 600;">Uploaded Dossier Documents</span>
                        <span class="badge bg-light text-dark border">{{ $employee->documents->count() }} Files</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->documents as $doc)
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600;">{{ $doc->document_type }}</div>
                                            <div class="text-muted code-font" style="font-size: 11px;">
                                                {{ $doc->file_name }} ({{ number_format($doc->file_size / 1024, 1) }} KB)
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border code-font">v{{ $doc->current_version }}</span>
                                        </td>
                                        <td>
                                            @if($doc->verification_status === 'Verified')
                                                <span class="status-pill status-pill-success"><span class="dot"></span> Verified</span>
                                            @elseif($doc->verification_status === 'Rejected')
                                                <span class="status-pill status-pill-danger" title="{{ $doc->rejection_reason }}"><span class="dot"></span> Rejected</span>
                                            @else
                                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="btn btn-outline-secondary" title="Secure Preview">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-outline-secondary" title="Download">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                @if(auth()->user()->isHr() || auth()->user()->isHoAdmin())
                                                    <button type="button" class="btn btn-outline-success" title="Verify Document" onclick="verifyDoc({{ $doc->id }}, 'verify')">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" title="Reject Document" onclick="openRejectDocModal({{ $doc->id }})">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No documents uploaded yet. Use the upload form to attach identification proofs.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- eKYC Attempt Audit History -->
        <div class="card card-custom">
            <div class="card-header bg-white" style="font-weight: 600;">
                <i class="bi bi-clock-history me-1"></i> eKYC Verification Attempt Audit History
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Attempt #</th>
                            <th>Mode</th>
                            <th>Result</th>
                            <th>Failure / Override Reason</th>
                            <th>Resubmission Due</th>
                            <th>Verifier / Operator</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->ekycAttempts as $attempt)
                            <tr>
                                <td class="code-font" style="font-weight: 600;">#{{ $attempt->attempt_number }}</td>
                                <td>{{ $attempt->verification_mode }}</td>
                                <td>
                                    @if($attempt->result === 'Success')
                                        <span class="status-pill status-pill-success"><span class="dot"></span> Success</span>
                                    @elseif($attempt->result === 'Exception Approved')
                                        <span class="status-pill status-pill-info"><span class="dot"></span> Exception Approved</span>
                                    @else
                                        <span class="status-pill status-pill-danger"><span class="dot"></span> Failed</span>
                                    @endif
                                </td>
                                <td>{{ $attempt->failure_reason ?? $attempt->remarks ?? '—' }}</td>
                                <td>
                                    @if($attempt->resubmission_due_date)
                                        <span class="code-font">{{ $attempt->resubmission_due_date->format('d M Y') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $attempt->verifier?->name ?? 'System Automated' }}</td>
                                <td class="code-font text-muted" style="font-size: 11.5px;">{{ $attempt->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No verification attempts executed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Gate Pass History -->
    <div class="tab-pane fade" id="gatepass" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span style="font-weight: 600;">Gate Pass Issuance & Modification History</span>
                <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#updateGatePassModal">
                    <i class="bi bi-pass me-1"></i> Change / Reissue Pass
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Previous Pass #</th>
                            <th>New Pass #</th>
                            <th>Reason for Change</th>
                            <th>Authorized By</th>
                            <th>Changed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->gatePassHistories as $gph)
                            <tr>
                                <td class="code-font">{{ $gph->old_gate_pass_number ?? 'Initial Assignment' }}</td>
                                <td class="code-font fw-bold">{{ $gph->new_gate_pass_number ?? 'Deactivated / None' }}</td>
                                <td>{{ $gph->change_reason }}</td>
                                <td>{{ $gph->user?->name ?? 'System Admin' }}</td>
                                <td class="code-font text-muted" style="font-size: 11.5px;">{{ $gph->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No gate pass modification history recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Allocated Assets -->
    <div class="tab-pane fade" id="assets" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span style="font-weight: 600;">Allocated Tools & Equipment</span>
                <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">Browse Asset Inventory</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Asset Code</th>
                            <th>Category & Name</th>
                            <th>Issue Date</th>
                            <th>Condition at Issue</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->assetAllocations as $alloc)
                            <tr>
                                <td class="code-font fw-bold">
                                    <a href="{{ route('assets.show', $alloc->asset_id) }}" class="text-decoration-none">
                                        {{ $alloc->asset->asset_code }}
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $alloc->asset->name }}</div>
                                    <div class="text-muted" style="font-size: 12px;">{{ $alloc->asset->category->name }}</div>
                                </td>
                                <td class="code-font">{{ $alloc->allocated_date->format('d M Y') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $alloc->condition_at_issue }}</span></td>
                                <td>
                                    @if($alloc->status === 'Active')
                                        <span class="status-pill status-pill-success"><span class="dot"></span> In Possession</span>
                                    @elseif($alloc->status === 'Returned')
                                        <span class="status-pill status-pill-info"><span class="dot"></span> Returned ({{ $alloc->return_date?->format('d M Y') }})</span>
                                    @elseif($alloc->status === 'Damaged')
                                        <span class="status-pill status-pill-danger"><span class="dot"></span> Damaged</span>
                                    @else
                                        <span class="status-pill status-pill-warning"><span class="dot"></span> {{ $alloc->status }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($alloc->status === 'Active')
                                        <button class="btn btn-outline-danger btn-sm" onclick="openReturnModal({{ $alloc->id }}, '{{ $alloc->asset->asset_code }}')">
                                            <i class="bi bi-box-arrow-in-left me-1"></i> Return Asset
                                        </button>
                                    @else
                                        <span class="text-muted" style="font-size: 12px;">Closed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No assets allocated to this employee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 5: Damages & Recovery -->
    <div class="tab-pane fade" id="fines" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span style="font-weight: 600;">Asset Damage Incidents & Recovery Ledger</span>
                <a href="{{ route('damage-assessments.create') }}" class="btn btn-brand btn-sm">Assess Damage Incident</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Asset</th>
                            <th>Assessed Amount</th>
                            <th>Depreciated Book Val</th>
                            <th>Waiver</th>
                            <th>Net Recovery</th>
                            <th>Installments Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->damageAssessments as $dam)
                            <tr>
                                <td class="code-font fw-bold">#{{ $dam->id }}</td>
                                <td>
                                    <div class="code-font">{{ $dam->allocation->asset->asset_code }}</div>
                                    <div class="text-muted" style="font-size: 11.5px;">{{ $dam->allocation->asset->name }}</div>
                                </td>
                                <td class="code-font">₹{{ number_format($dam->assessed_amount, 2) }}</td>
                                <td class="code-font text-muted">₹{{ number_format($dam->depreciated_value, 2) }} ({{ $dam->depreciation_strategy }})</td>
                                <td>
                                    @if($dam->waived_amount > 0)
                                        <span class="badge bg-success-subtle text-success border">₹{{ number_format($dam->waived_amount, 2) }} Waived</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="code-font fw-bold text-danger">₹{{ number_format($dam->net_recovery_amount, 2) }}</td>
                                <td>
                                    @foreach($dam->recoveryRecords as $rec)
                                        <div class="d-flex align-items-center gap-1 mb-1" style="font-size: 11.5px;">
                                            <span class="code-font">Inst #{{ $rec->installment_number }}:</span>
                                            <span>₹{{ number_format($rec->amount, 2) }}</span>
                                            @if($rec->status === 'Paid')
                                                <span class="badge bg-success text-white" style="font-size: 9px;">Paid</span>
                                            @elseif($rec->status === 'Queued for Phase 5 Payroll')
                                                <span class="badge bg-info text-white" style="font-size: 9px;">Payroll Queue</span>
                                            @else
                                                <span class="badge bg-warning text-dark" style="font-size: 9px;">Pending</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No damage assessments or fine records for this employee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 6: Prior Internal Tenures -->
    <div class="tab-pane fade" id="history" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white" style="font-weight: 600;">
                Prior Service Records in Group Companies
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Previous Entity</th>
                            <th>Previous EMP Code</th>
                            <th>Designation & Dept</th>
                            <th>Tenure Period</th>
                            <th>Reason for Leaving</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->previousEmployments as $pe)
                            <tr>
                                <td style="font-weight: 600;">{{ $pe->previousCompany?->name ?? 'Group Company' }}</td>
                                <td class="code-font">{{ $pe->previous_employee_code }}</td>
                                <td>{{ $pe->last_designation ?? '—' }} ({{ $pe->last_department ?? '—' }})</td>
                                <td class="code-font">
                                    {{ $pe->joining_date ? $pe->joining_date->format('M Y') : '—' }} to {{ $pe->relieving_date ? $pe->relieving_date->format('M Y') : '—' }}
                                </td>
                                <td>{{ $pe->reason_for_leaving ?? 'Standard Separation' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No previous employment tenure recorded within group entities.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Update Gate Pass -->
<div class="modal fade" id="updateGatePassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.gate-pass.update', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Update Gate Pass Number</h6>
                    <button type="button" class="btn-close" data-bs-toggle="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Current Gate Pass</label>
                        <input type="text" class="form-control code-font" value="{{ $employee->gate_pass_number ?? 'None' }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">New Gate Pass Number</label>
                        <input type="text" name="gate_pass_number" class="form-control code-font" placeholder="e.g. GP-2026-9041">
                        <div class="form-text" style="font-size: 11.5px;">Leave empty if deactivating gate pass.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Reason for Update / Reissue <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Lost physical card, site transfer, damaged badge" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand btn-sm">Save & Log to Audit History</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Trigger eKYC Check -->
<div class="modal fade" id="ekycModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ekyc.attempt', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Execute eKYC Verification Attempt</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size: 13px; color: #475569;">
                        Attempting eKYC verification for <strong>{{ $employee->full_name }}</strong> (Attempt #{{ $employee->ekyc_attempt_count + 1 }} of 3).
                    </p>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Verification Mode</label>
                        <select name="mode" class="form-select form-select-sm" required>
                            <option value="Simulated eKYC">Simulated eKYC (UIDAI Sandbox Mock)</option>
                            <option value="Manual Verification">Manual Physical Verification</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Simulation Scenario (For Testing)</label>
                        <select name="scenario" class="form-select form-select-sm">
                            <option value="auto">Auto / Pass (Simulate Valid Match)</option>
                            <option value="fail_name_mismatch">Fail: Name Mismatch</option>
                            <option value="fail_dob_mismatch">Fail: DOB Mismatch</option>
                            <option value="fail_unclear_doc">Fail: Blurry / Unreadable Document</option>
                            <option value="force_failure">Force General Failure</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Grace Period Days (If Attempt Fails)</label>
                        <input type="number" name="grace_days" class="form-control form-control-sm" value="14" min="7" max="21">
                        <div class="form-text" style="font-size: 11.5px;">Standard enterprise policy is 14 days (configurable between 7 to 21 days).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Execute Verification Attempt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: HO Exception Override -->
@if(auth()->user()->isHoAdmin())
<div class="modal fade" id="exceptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ekyc.exception', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-danger"><i class="bi bi-shield-exclamation me-1"></i> HO Admin Exception Override</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning" style="font-size: 12.5px;">
                        <strong>High-Privilege Action:</strong> This action forces the candidate into <code>Exceptionally Verified</code> status, bypassing failed or pending eKYC attempts. A permanent immutable audit record will be logged.
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Mandatory Justification / Physical Evidence <span class="text-danger">*</span></label>
                        <textarea name="exception_remarks" rows="3" class="form-control" placeholder="Provide detailed operational rationale, physical identification checks conducted, or director clearance..." required minlength="10"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirm Exception Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal 4: Reject Document Modal -->
<div class="modal fade" id="rejectDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="rejectDocForm">
                @csrf
                <input type="hidden" name="action" value="reject">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Reject Document</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Mandatory Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" rows="3" class="form-control" placeholder="e.g. Document copy is illegible, expired, or names do not match..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Reject Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 5: Return Asset Modal -->
<div class="modal fade" id="returnAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="returnAssetForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Return Asset <span id="returnAssetCodeBadge" class="code-font"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Condition at Return <span class="text-danger">*</span></label>
                        <select name="condition_at_return" class="form-select form-select-sm" required>
                            <option value="Good">Good / Standard Working Order</option>
                            <option value="Fair">Fair / Minor Scratches</option>
                            <option value="Minor Wear & Tear">Minor Wear & Tear</option>
                            <option value="Damaged">Damaged (Requires Repair / Fine)</option>
                            <option value="Heavy Wear & Tear">Heavy Wear & Tear</option>
                            <option value="Lost / Stolen">Lost / Stolen / Non-Returnable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Inspection Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control form-control-sm" placeholder="Notes on physical check..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand btn-sm">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function verifyDoc(docId, action) {
        if (!confirm('Are you sure you want to approve and mark this document as verified?')) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/documents/' + docId + '/verify';
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        const act = document.createElement('input');
        act.type = 'hidden';
        act.name = 'action';
        act.value = action;
        form.appendChild(act);
        document.body.appendChild(form);
        form.submit();
    }

    function openRejectDocModal(docId) {
        const form = document.getElementById('rejectDocForm');
        form.action = '/documents/' + docId + '/verify';
        const modal = new bootstrap.Modal(document.getElementById('rejectDocModal'));
        modal.show();
    }

    function openReturnModal(allocId, assetCode) {
        const form = document.getElementById('returnAssetForm');
        form.action = '/asset-allocations/' + allocId + '/return';
        document.getElementById('returnAssetCodeBadge').innerText = '(' + assetCode + ')';
        const modal = new bootstrap.Modal(document.getElementById('returnAssetModal'));
        modal.show();
    }
</script>
@endsection

