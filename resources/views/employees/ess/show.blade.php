@extends('layouts.app')

@section('title', 'My Profile & Master Dossier — ESS')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.employee') }}">ESS Portal</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Profile</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Employee Self-Service Profile</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Manage your personal, contact, family, academic credentials and compliance documents.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('ess.profile.edit') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-pencil-square"></i>
            <span>Edit Permitted Fields</span>
        </a>
        @if(in_array($employee->onboarding_status, ['Draft', 'Corrections Requested']))
            <form method="POST" action="{{ route('ess.profile.submit') }}" class="d-inline" onsubmit="return confirm('Submit your profile changes for HR review and approval?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-send-check-fill"></i>
                    <span>Submit for HR Approval</span>
                </button>
            </form>
        @endif
    </div>
</div>

<!-- Review Status Banner -->
@if($employee->onboarding_status === 'Corrections Requested')
    <div class="alert alert-danger d-flex align-items-start gap-3 mb-4 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill fs-4 mt-1"></i>
        <div class="flex-grow-1">
            <h6 class="mb-1 fw-bold">HR Requested Corrections on Your Dossier</h6>
            <p class="mb-2" style="font-size: 13.5px;">
                HR reviewed your profile on {{ $employee->reviewed_at?->format('d M Y H:i') }} and requested updates:
            </p>
            <div class="bg-white p-2.5 rounded border border-danger-subtle fw-semibold text-danger mb-2" style="font-size: 13px;">
                "{{ $employee->review_notes }}"
            </div>
            <p class="mb-0" style="font-size: 13px;">
                Please click <strong>Edit Permitted Fields</strong>, update the required information, and click <strong>Submit for HR Approval</strong>.
            </p>
        </div>
    </div>
@elseif($employee->onboarding_status === 'Submitted for Review')
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4 shadow-sm">
        <i class="bi bi-hourglass-split fs-4"></i>
        <div>
            <h6 class="mb-0 fw-bold">Profile Submitted — Awaiting HR Verification</h6>
            <p class="mb-0" style="font-size: 13px;">Your dossier changes were submitted on {{ $employee->submitted_for_review_at?->format('d M Y H:i') ?? 'recently' }} and are being reviewed by the HR operations team.</p>
        </div>
    </div>
@elseif($employee->onboarding_status === 'Approved')
    <div class="alert alert-success d-flex align-items-center gap-3 mb-4 shadow-sm">
        <i class="bi bi-patch-check-fill fs-4"></i>
        <div>
            <h6 class="mb-0 fw-bold">Profile Verified & Approved</h6>
            <p class="mb-0" style="font-size: 13px;">Your employee master dossier is fully verified and compliant with enterprise records.</p>
        </div>
    </div>
@endif

<!-- Profile Header Card -->
<div class="card card-custom mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 64px; height: 64px; border-radius: 12px; border: 2px solid #E2E8F0; overflow: hidden; background: #0D9488; color: #FFFFFF; font-weight: 700; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    @if($employee->profile_photo_path)
                        <img src="{{ asset('storage/' . $employee->profile_photo_path) }}" alt="{{ $employee->full_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        {{ $employee->initials }}
                    @endif
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="mb-0 fw-bold text-dark">{{ $employee->title }} {{ $employee->full_name }}</h4>
                        <span class="badge bg-light text-dark border code-font">{{ $employee->employee_code }}</span>
                        <span class="badge bg-success-subtle text-success border">Active</span>
                    </div>
                    <div class="text-muted" style="font-size: 13px;">
                        <span><strong>{{ $employee->designation?->name ?? 'Designation Unassigned' }}</strong></span> • 
                        <span>{{ $employee->department?->name ?? 'Department Unassigned' }}</span> • 
                        <span><i class="bi bi-geo-alt"></i> {{ $employee->location?->name ?? 'Site Unassigned' }}</span>
                    </div>
                </div>
            </div>

            <!-- Profile Completeness Indicator -->
            <div class="bg-light p-3 rounded border" style="min-width: 260px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-muted" style="font-size: 12px;">Profile Completeness</span>
                    <span class="fw-bold text-primary" style="font-size: 13px;">{{ is_array($completeness) ? $completeness['percentage'] : $completeness }}%</span>
                </div>
                <div class="progress" style="height: 7px;">
                    <div class="progress-bar {{ (is_array($completeness) ? $completeness['percentage'] : $completeness) >= 80 ? 'bg-success' : ((is_array($completeness) ? $completeness['percentage'] : $completeness) >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                         role="progressbar" 
                         style="width: {{ is_array($completeness) ? $completeness['percentage'] : $completeness }}%;"></div>
                </div>
                @if(is_array($completeness) && !empty($completeness['missing_fields']))
                    <div class="text-muted mt-1 small" style="font-size: 11px;">
                        Missing: {{ implode(', ', array_slice($completeness['missing_fields'], 0, 3)) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="essTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="ess-overview-tab" data-bs-toggle="tab" data-bs-target="#ess-overview" type="button">
            <i class="bi bi-person me-1"></i> Personal & Contact
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="ess-job-tab" data-bs-toggle="tab" data-bs-target="#ess-job" type="button">
            <i class="bi bi-briefcase me-1"></i> Job & Organizational
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="ess-family-tab" data-bs-toggle="tab" data-bs-target="#ess-family" type="button">
            <i class="bi bi-people me-1"></i> Family ({{ $employee->familyMembers->count() }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="ess-education-tab" data-bs-toggle="tab" data-bs-target="#ess-education" type="button">
            <i class="bi bi-mortarboard me-1"></i> Education ({{ $employee->educations->count() }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="ess-skills-tab" data-bs-toggle="tab" data-bs-target="#ess-skills" type="button">
            <i class="bi bi-stars me-1"></i> Skills Matrix ({{ $employee->skills->count() }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="ess-docs-tab" data-bs-toggle="tab" data-bs-target="#ess-docs" type="button">
            <i class="bi bi-file-earmark-check me-1"></i> Documents ({{ $employee->documents->count() }})
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="ess-bank-tab" data-bs-toggle="tab" data-bs-target="#ess-bank" type="button">
            <i class="bi bi-bank me-1"></i> Bank & Statutory
        </button>
    </li>
</ul>

<div class="tab-content" id="essTabsContent">

    <!-- TAB 1: Personal & Contact -->
    <div class="tab-pane fade show active" id="ess-overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Personal Profile</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Full Name</td>
                                <td class="fw-semibold">{{ $employee->title }} {{ $employee->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gender</td>
                                <td>{{ $employee->gender }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date of Birth</td>
                                <td>{{ $employee->date_of_birth ? $employee->date_of_birth->format('d M Y') : '—' }} ({{ $employee->age ? $employee->age . ' yrs' : '—' }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nationality</td>
                                <td>{{ $employee->nationality ?? 'Indian' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Blood Group</td>
                                <td><span class="badge bg-danger-subtle text-danger border fw-bold">{{ $employee->blood_group ?? '—' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Marital Status</td>
                                <td>{{ $employee->marital_status ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Father / Mother</td>
                                <td>{{ $employee->father_name ?? '—' }} / {{ $employee->mother_name ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Contact & Address</div>
                    <div class="card-body">
                        @php $addr = $employee->addressRecord; @endphp
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Mobile Phone</td>
                                <td class="fw-semibold">{{ $employee->phone }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Personal Mobile</td>
                                <td>{{ $employee->personal_phone ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Official Email</td>
                                <td>{{ $employee->email ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Personal Email</td>
                                <td>{{ $employee->alternate_email ?? '—' }}</td>
                            </tr>
                        </table>

                        <hr class="my-2">
                        <h6 class="text-uppercase text-muted fw-bold" style="font-size: 11px;">Present Address</h6>
                        <p class="mb-2" style="font-size: 13px;">{{ $addr?->current_full ?? $employee->address ?? 'Not recorded' }}</p>

                        <h6 class="text-uppercase text-muted fw-bold mt-2" style="font-size: 11px;">Permanent Address</h6>
                        <p class="mb-0" style="font-size: 13px;">{{ $addr?->permanent_full ?? $employee->address ?? 'Not recorded' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Job & Organizational -->
    <div class="tab-pane fade" id="ess-job" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white fw-bold py-3">Employment Details (HR Managed)</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Company Entity</td>
                                <td class="fw-semibold">{{ $employee->company->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Location / Site</td>
                                <td>{{ $employee->location?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Department</td>
                                <td>{{ $employee->department?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Designation</td>
                                <td class="fw-semibold">{{ $employee->designation?->name ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Date of Joining</td>
                                <td>{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tenure</td>
                                <td>{{ $employee->service_months ? $employee->service_months . ' Months' : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Reporting Manager</td>
                                <td>{{ $employee->reportingManager?->full_name ?? 'Direct Management' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gate Pass #</td>
                                <td class="code-font">{{ $employee->gate_pass_number ?? 'None' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Family Members -->
    <div class="tab-pane fade" id="ess-family" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">My Family & Dependents</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#essAddFamilyModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Family Member
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Relationship</th>
                            <th>Date of Birth</th>
                            <th>Phone</th>
                            <th>Emergency Contact</th>
                            <th>Dependent</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->familyMembers as $m)
                            <tr>
                                <td class="fw-semibold">{{ $m->full_name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $m->relationship }}</span></td>
                                <td>{{ $m->date_of_birth ? $m->date_of_birth->format('d M Y') : '—' }}</td>
                                <td>{{ $m->phone ?? '—' }}</td>
                                <td>
                                    @if($m->is_emergency_contact)
                                        <span class="badge bg-danger-subtle text-danger border">Emergency Contact</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($m->is_dependent)
                                        <span class="badge bg-info-subtle text-info border">Dependent</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('employees.family.delete', [$employee->id, $m->id]) }}" onsubmit="return confirm('Remove family member?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm p-1"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No family members registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: Education -->
    <div class="tab-pane fade" id="ess-education" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">My Educational Qualifications</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#essAddEduModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Qualification
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Degree / Certificate</th>
                            <th>Institute</th>
                            <th>Specialization</th>
                            <th>Year</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Certificate</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->educations as $edu)
                            <tr>
                                <td class="fw-semibold">{{ $edu->qualification_name }} ({{ $edu->degree_level }})</td>
                                <td>{{ $edu->institute_name }}</td>
                                <td>{{ $edu->specialization ?? 'General' }}</td>
                                <td>{{ $edu->year_of_passing }}</td>
                                <td>{{ $edu->percentage_or_cgpa ? $edu->percentage_or_cgpa . '%' : '—' }}</td>
                                <td>
                                    @if($edu->verification_status === 'Verified')
                                        <span class="badge bg-success-subtle text-success border">Verified</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($edu->certificate_path)
                                        <a href="{{ asset('storage/' . $edu->certificate_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm p-1"><i class="bi bi-file-earmark-pdf text-danger"></i></a>
                                    @else
                                        <span class="text-muted small">None</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('employees.education.delete', [$employee->id, $edu->id]) }}" onsubmit="return confirm('Delete?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm p-1"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No academic qualifications registered.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 5: Skills -->
    <div class="tab-pane fade" id="ess-skills" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">My Skills & Competencies</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#essAddSkillModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Skill
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Skill</th>
                            <th>Category</th>
                            <th>Proficiency</th>
                            <th>Experience (Yrs)</th>
                            <th>Certified</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->skills as $sk)
                            <tr>
                                <td class="fw-semibold">{{ $sk->skill_name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $sk->skill_category }}</span></td>
                                <td><span class="badge bg-primary-subtle text-primary border">{{ $sk->proficiency_level }}</span></td>
                                <td>{{ $sk->years_of_experience ?? '—' }} yrs</td>
                                <td>{{ $sk->is_certified ? 'Yes' : 'No' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('employees.skills.delete', [$employee->id, $sk->id]) }}" onsubmit="return confirm('Remove skill?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm p-1"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No skills recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 6: Documents -->
    <div class="tab-pane fade" id="ess-docs" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">My Uploaded Documents</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#essUploadDocModal">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Document
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Document Title</th>
                            <th>Filename</th>
                            <th>Expiry</th>
                            <th>Verification</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $d)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $d->document_category ?? 'General' }}</span></td>
                                <td class="fw-semibold">{{ $d->document_type }}</td>
                                <td>{{ $d->file_name }}</td>
                                <td>{{ $d->expiry_date ? $d->expiry_date->format('d M Y') : '—' }}</td>
                                <td>
                                    @if($d->verification_status === 'Verified')
                                        <span class="badge bg-success-subtle text-success border">Verified</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Pending</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('documents.preview', $d->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm p-1" title="Preview">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('documents.download', $d->id) }}" class="btn btn-outline-secondary btn-sm p-1" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No documents uploaded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 7: Bank & Statutory (Masked) -->
    <div class="tab-pane fade" id="ess-bank" role="tabpanel">
        @php 
            $st = $employee->statutory;
            $bk = $employee->bankDetail;
        @endphp
        <div class="card card-custom">
            <div class="card-header bg-white fw-bold py-3">Statutory Tokens & Disbursement Account</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">PAN Number</td>
                                <td class="code-font fw-semibold">{{ $employee->pan_number ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Aadhaar (UIDAI Masked)</td>
                                <td class="code-font fw-semibold">{{ $employee->aadhaar_masked ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Universal Account Number (UAN)</td>
                                <td class="code-font">{{ $st?->uan_number ?? $employee->uan_number ?? 'Not Assigned' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">ESIC Insurance #</td>
                                <td class="code-font">{{ $st?->esic_number ?? $employee->esic_number ?? 'Not Assigned' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">Disbursement Bank</td>
                                <td class="fw-semibold">{{ $bk?->bank_name ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Bank Branch & IFSC</td>
                                <td>{{ $bk?->bank_branch ?? '—' }} ({{ $bk?->bank_ifsc ?? '—' }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Bank Account Number</td>
                                <td class="code-font fw-bold">{{ $bk?->masked_account_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Disbursement Mode</td>
                                <td><span class="badge bg-light text-secondary border">{{ $bk?->payment_mode ?? 'Bank Transfer' }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ESS MODAL: Add Family Member -->
<div class="modal fade" id="essAddFamilyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.family.add', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Add Family Member</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Relationship <span class="text-danger">*</span></label>
                            <select name="relationship" class="form-select form-select-sm" required>
                                @foreach(['Father', 'Mother', 'Spouse', 'Son', 'Daughter', 'Brother', 'Sister', 'Other'] as $rel)
                                    <option value="{{ $rel }}">{{ $rel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Phone Number</label>
                            <input type="tel" name="phone" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mb-1">
                                <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="essEmergSwitch">
                                <label class="form-check-label fw-semibold" for="essEmergSwitch" style="font-size: 12.5px;">Mark as Emergency Contact</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_dependent" value="1" id="essDepSwitch" checked>
                                <label class="form-check-label fw-semibold" for="essDepSwitch" style="font-size: 12.5px;">Dependent for Health Insurance</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Family Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ESS MODAL: Add Qualification -->
<div class="modal fade" id="essAddEduModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.education.add', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Add Academic Qualification</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Degree Level <span class="text-danger">*</span></label>
                            <select name="degree_level" class="form-select form-select-sm" required>
                                @foreach(['High School', 'Diploma', 'Bachelor', 'Master', 'Doctorate', 'Professional Certification'] as $lvl)
                                    <option value="{{ $lvl }}">{{ $lvl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Qualification / Degree <span class="text-danger">*</span></label>
                            <input type="text" name="qualification_name" class="form-control form-control-sm" required placeholder="e.g. B.Tech Computer Science">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Institute / University <span class="text-danger">*</span></label>
                            <input type="text" name="institute_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Specialization</label>
                            <input type="text" name="specialization" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Year Passed <span class="text-danger">*</span></label>
                            <input type="number" name="year_of_passing" class="form-control form-control-sm" value="{{ date('Y') }}" min="1960" max="{{ date('Y') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Score / %</label>
                            <input type="number" step="0.01" name="percentage_or_cgpa" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Upload Certificate / Marksheet (PDF/JPG)</label>
                            <input type="file" name="certificate_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Add Qualification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ESS MODAL: Add Skill -->
<div class="modal fade" id="essAddSkillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.skills.add', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Add Skill to My Matrix</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Skill Name <span class="text-danger">*</span></label>
                            <input type="text" name="skill_name" class="form-control form-control-sm" required placeholder="e.g. Laravel, Python">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Category</label>
                            <select name="skill_category" class="form-select form-select-sm">
                                @foreach(['Technical', 'Functional', 'Soft Skills', 'Domain Expertise', 'Operational'] as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Proficiency Level <span class="text-danger">*</span></label>
                            <select name="proficiency_level" class="form-select form-select-sm" required>
                                @foreach(['Beginner', 'Intermediate', 'Advanced', 'Expert'] as $pl)
                                    <option value="{{ $pl }}">{{ $pl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Experience (Years)</label>
                            <input type="number" step="0.5" name="years_of_experience" class="form-control form-control-sm" placeholder="e.g. 2">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_certified" value="1" id="essSkillCertSwitch">
                                <label class="form-check-label fw-semibold" for="essSkillCertSwitch" style="font-size: 12.5px;">Formally Certified in this Skill</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Add Skill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ESS MODAL: Upload Document -->
<div class="modal fade" id="essUploadDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('documents.store', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Upload Personal Document</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Document Category <span class="text-danger">*</span></label>
                        <select name="document_category" class="form-select form-select-sm" required>
                            @foreach(['Identity', 'Academic', 'Employment', 'Statutory', 'Medical', 'Other'] as $dc)
                                <option value="{{ $dc }}">{{ $dc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Document Type / Title <span class="text-danger">*</span></label>
                        <input type="text" name="document_type" class="form-control form-control-sm" placeholder="e.g. Aadhaar Card, Passport, Degree" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Document File (PDF, JPG, PNG) <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Expiry Date (If applicable)</label>
                        <input type="date" name="expiry_date" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
