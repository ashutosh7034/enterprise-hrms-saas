@extends('layouts.app')

@section('title', "{$employee->full_name} ({$employee->employee_code}) — Master Dossier")

@section('content')
<!-- Header Card -->
<div class="card card-custom mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
            <!-- Left: Avatar & Identity -->
            <div class="d-flex align-items-start gap-3">
                <div style="width: 64px; height: 64px; border-radius: 12px; border: 2px solid #E2E8F0; overflow: hidden; background: #0D9488; color: #FFFFFF; font-weight: 700; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    @if($employee->profile_photo_path)
                        <img src="{{ asset('storage/' . $employee->profile_photo_path) }}" alt="{{ $employee->full_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        {{ $employee->initials }}
                    @endif
                </div>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h4 class="mb-0" style="font-weight: 700; color: #0F172A;">
                            {{ $employee->title ? $employee->title . ' ' : '' }}{{ $employee->full_name }}
                        </h4>
                        @if($employee->aka_name)
                            <span class="text-muted" style="font-size: 13px;">({{ $employee->aka_name }})</span>
                        @endif
                        <span class="badge bg-light text-dark border code-font fs-6">{{ $employee->employee_code }}</span>
                        @if($employee->ticket_number)
                            <span class="badge bg-secondary-subtle text-secondary border code-font">Tkt: {{ $employee->ticket_number }}</span>
                        @endif

                        <!-- Employment Status Pill -->
                        @if($employee->employment_status === 'Active')
                            <span class="status-pill status-pill-success"><span class="dot"></span> Active</span>
                        @elseif($employee->employment_status === 'Probation')
                            <span class="status-pill status-pill-info"><span class="dot"></span> Probation</span>
                        @elseif($employee->employment_status === 'Draft')
                            <span class="status-pill status-pill-warning"><span class="dot"></span> Draft</span>
                        @else
                            <span class="status-pill status-pill-warning"><span class="dot"></span> {{ $employee->employment_status }}</span>
                        @endif

                        <!-- Onboarding Review Pill -->
                        @if($employee->onboarding_status === 'Approved')
                            <span class="badge bg-success-subtle text-success border"><i class="bi bi-patch-check-fill me-1"></i>Approved</span>
                        @elseif($employee->onboarding_status === 'Submitted for Review')
                            <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-hourglass-split me-1"></i>Submitted for Review</span>
                        @elseif($employee->onboarding_status === 'Corrections Requested')
                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-exclamation-octagon-fill me-1"></i>Corrections Requested</span>
                        @elseif($employee->onboarding_status === 'Draft')
                            <span class="badge bg-light text-muted border">Onboarding: Draft</span>
                        @endif
                    </div>

                    <div class="text-muted" style="font-size: 13px;">
                        <span><strong>{{ $employee->designation?->name ?? 'Designation Unassigned' }}</strong></span> • 
                        <span>{{ $employee->department?->name ?? 'Department Unassigned' }}</span> • 
                        <span><i class="bi bi-geo-alt"></i> {{ $employee->location?->name ?? 'Site Unassigned' }}</span> • 
                        <span>{{ $employee->company->name }}</span>
                    </div>

                    <!-- Completeness Progress Bar -->
                    <div class="d-flex align-items-center gap-2 mt-2" style="max-width: 420px;">
                        <span class="text-muted" style="font-size: 11.5px; font-weight: 600;">Dossier Completeness:</span>
                        <div class="progress flex-grow-1" style="height: 7px;">
                            <div class="progress-bar {{ (is_array($completeness) ? $completeness['percentage'] : $completeness) >= 80 ? 'bg-success' : ((is_array($completeness) ? $completeness['percentage'] : $completeness) >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ is_array($completeness) ? $completeness['percentage'] : $completeness }}%;" 
                                 aria-valuenow="{{ is_array($completeness) ? $completeness['percentage'] : $completeness }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                        <span class="fw-bold" style="font-size: 11.5px;">{{ is_array($completeness) ? $completeness['percentage'] : $completeness }}%</span>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if(in_array($employee->onboarding_status, ['Submitted for Review', 'Under Review', 'Corrections Requested', 'Draft']))
                    <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveProfileModal">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Approve Profile</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#requestCorrectionModal">
                        <i class="bi bi-arrow-return-left"></i>
                        <span>Request Correction</span>
                    </button>
                @endif

                @if(!$employee->userAccount)
                    <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#provisionAccessModal">
                        <i class="bi bi-key-fill"></i>
                        <span>Provision ESS Login</span>
                    </button>
                @endif

                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-brand btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Dossier</span>
                </a>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        More Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateGatePassModal"><i class="bi bi-pass me-2 text-secondary"></i>Update Gate Pass</a></li>
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#ekycModal"><i class="bi bi-shield-check me-2 text-primary"></i>Run eKYC Check</a></li>
                        @if(auth()->user()->isHoAdmin() && !in_array($employee->ekyc_status, ['Verified', 'Exceptionally Verified']))
                            <li><a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#exceptionModal"><i class="bi bi-award me-2"></i>HO Exception Override</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- Verification Banner Alerts -->
        @if($employee->onboarding_status === 'Corrections Requested')
            <div class="alert alert-danger d-flex align-items-center gap-3 mt-3 mb-0 py-2.5 px-3">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div class="flex-grow-1" style="font-size: 13px;">
                    <strong>Corrections Requested:</strong> Candidate was asked to revise profile on {{ $employee->reviewed_at?->format('d M Y H:i') }}.
                    @if($employee->review_notes)
                        <div class="mt-1"><em>"{{ $employee->review_notes }}"</em></div>
                    @endif
                </div>
            </div>
        @elseif($employee->onboarding_status === 'Submitted for Review')
            <div class="alert alert-warning d-flex align-items-center gap-3 mt-3 mb-0 py-2.5 px-3">
                <i class="bi bi-hourglass-split fs-5"></i>
                <div class="flex-grow-1" style="font-size: 13px;">
                    <strong>Submitted for Review:</strong> The candidate or employee has finalized their profile on {{ $employee->submitted_for_review_at?->format('d M Y H:i') ?? 'recently' }}. Please verify records and click <strong>Approve Profile</strong> or <strong>Request Correction</strong>.
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
            <i class="bi bi-briefcase me-1"></i> Job & Organizational
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
            <i class="bi bi-person me-1"></i> Personal & Addresses
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="family-tab" data-bs-toggle="tab" data-bs-target="#family" type="button" role="tab">
            <i class="bi bi-people me-1"></i> Family & Dependents ({{ $employee->familyMembers->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="statutory-tab" data-bs-toggle="tab" data-bs-target="#statutory" type="button" role="tab">
            <i class="bi bi-shield-check me-1"></i> Statutory & Compliance
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="banking-tab" data-bs-toggle="tab" data-bs-target="#banking" type="button" role="tab">
            <i class="bi bi-bank me-1"></i> Banking & Payment
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#education" type="button" role="tab">
            <i class="bi bi-mortarboard me-1"></i> Education ({{ $employee->educations->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills" type="button" role="tab">
            <i class="bi bi-stars me-1"></i> Skills Matrix ({{ $employee->skills->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="visa-tab" data-bs-toggle="tab" data-bs-target="#visa" type="button" role="tab">
            <i class="bi bi-globe me-1"></i> Visa & Immigration
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="ekyc-tab" data-bs-toggle="tab" data-bs-target="#ekyc" type="button" role="tab">
            <i class="bi bi-file-earmark-check me-1"></i> Documents & eKYC ({{ $employee->documents->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab">
            <i class="bi bi-laptop me-1"></i> Assets ({{ $employee->assetAllocations->where('status', 'Active')->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab">
            <i class="bi bi-key me-1"></i> Login & Security
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit" type="button" role="tab">
            <i class="bi bi-clock-history me-1"></i> Profile History ({{ $employee->profileHistories->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="lifecycle-tab" data-bs-toggle="tab" data-bs-target="#lifecycle" type="button" role="tab">
            <i class="bi bi-arrow-repeat me-1 text-primary"></i> Lifecycle & Transitions ({{ $employee->lifecycleEvents->count() }})
        </button>
    </li>
</ul>

<!-- Tab Contents -->
<div class="tab-content" id="employeeTabsContent">
    
    <!-- Tab 1: Job & Organizational Info -->
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Organizational Assignment</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Legal Entity</td>
                                <td class="fw-semibold">{{ $employee->company->name }} ({{ $employee->company->code }})</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Work Site / Location</td>
                                <td>{{ $employee->location?->name ?? '—' }} ({{ $employee->location?->code ?? '' }})</td>
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
                                <td class="text-muted">Skill Level</td>
                                <td><span class="badge bg-light text-dark border">{{ $employee->skill_level ?? 'Skilled' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Grade / Band</td>
                                <td>{{ $employee->grade ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Division / Unit</td>
                                <td>{{ $employee->division ?? '—' }} / {{ $employee->unit ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Project / Cost Center</td>
                                <td>{{ $employee->project ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Reporting Manager</td>
                                <td>
                                    @if($employee->reportingManager)
                                        <a href="{{ route('employees.show', $employee->reportingManager->id) }}" class="text-decoration-none fw-semibold">
                                            {{ $employee->reportingManager->full_name }} ({{ $employee->reportingManager->employee_code }})
                                        </a>
                                    @else
                                        <span class="text-muted">No Direct Manager</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Tenure & Contract Terms</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Date of Joining</td>
                                <td class="fw-semibold">{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Length of Service</td>
                                <td>{{ $employee->service_months ? $employee->service_months . ' Months' : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Onboarding Channel</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $employee->onboarding_channel }}</span>
                                    @if($employee->contractor)
                                        <div class="text-muted mt-1" style="font-size: 11.5px;">Contractor: <strong>{{ $employee->contractor->name }}</strong></div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gate Pass Number</td>
                                <td class="code-font">{{ $employee->gate_pass_number ?? 'No Active Pass' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ticket Number</td>
                                <td class="code-font">{{ $employee->ticket_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Job Post Code</td>
                                <td>{{ $employee->job_post_code ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Legacy Employee Code</td>
                                <td>{{ $employee->old_employee_code ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">JV Code (Credit)</td>
                                <td>{{ $employee->jv_code_cr ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Payment Mode</td>
                                <td><span class="badge bg-light text-secondary border">{{ $employee->payment_mode ?? 'Bank Transfer' }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Personal & Addresses -->
    <div class="tab-pane fade" id="personal" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Demographic & Health Profile</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Title & Full Name</td>
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
                                <td class="text-muted">Place of Birth</td>
                                <td>{{ $employee->place_of_birth ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Blood Group</td>
                                <td><span class="badge bg-danger-subtle text-danger border fw-bold">{{ $employee->blood_group ?? '—' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Identification Mark</td>
                                <td>{{ $employee->identification_mark ?? 'None recorded' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Specially Abled / Divyaang</td>
                                <td>
                                    @if($employee->is_specially_abled)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Yes (Divyaang)</span>
                                        <div class="text-muted small mt-1">{{ $employee->disability_details }}</div>
                                    @else
                                        <span class="badge bg-light text-muted border">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Personal Notes</td>
                                <td>{{ $employee->personal_remarks ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Contact & Domicile Addresses</div>
                    <div class="card-body">
                        @php $addr = $employee->addressRecord; @endphp
                        <h6 class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.05em;">Phone & Digital Channels</h6>
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Primary Phone</td>
                                <td class="fw-semibold">{{ $employee->phone }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Personal Mobile</td>
                                <td>{{ $employee->personal_phone ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Primary Email</td>
                                <td>{{ $employee->email ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Alternate / CC Email</td>
                                <td>{{ $employee->alternate_email ?? '—' }} / {{ $employee->cc_email ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Microsoft 365 / Google</td>
                                <td>{{ $employee->microsoft_user ?? '—' }} / {{ $employee->google_user ?? '—' }}</td>
                            </tr>
                        </table>

                        <hr class="my-2">
                        <h6 class="text-uppercase text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.05em;">Present Address</h6>
                        <p class="mb-2" style="font-size: 13px;">
                            {{ $addr?->current_full ?? $employee->address ?? 'Not recorded' }}
                        </p>

                        <h6 class="text-uppercase text-muted fw-bold mt-3" style="font-size: 11px; letter-spacing: 0.05em;">Permanent Address</h6>
                        <p class="mb-0" style="font-size: 13px;">
                            {{ $addr?->permanent_full ?? $employee->address ?? 'Not recorded' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Family & Dependents -->
    <div class="tab-pane fade" id="family" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <span class="fw-bold">Family Members & Dependents</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->familyMembers->count() }} Registered</span>
                </div>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addFamilyMemberModal">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Add Family Member</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Relationship</th>
                            <th>Date of Birth / Age</th>
                            <th>Contact Phone</th>
                            <th>Emergency Contact</th>
                            <th>Dependent</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->familyMembers as $member)
                            <tr>
                                <td class="fw-semibold">{{ $member->full_name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $member->relationship }}</span></td>
                                <td>
                                    {{ $member->date_of_birth ? $member->date_of_birth->format('d M Y') : '—' }}
                                    @if($member->date_of_birth)
                                        <span class="text-muted">({{ $member->date_of_birth->age }} yrs)</span>
                                    @endif
                                </td>
                                <td>{{ $member->phone ?? '—' }}</td>
                                <td>
                                    @if($member->is_emergency_contact)
                                        <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-heart-pulse-fill me-1"></i>Emergency Contact</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($member->is_dependent)
                                        <span class="badge bg-info-subtle text-info border">Dependent</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('employees.family.delete', [$employee->id, $member->id]) }}" onsubmit="return confirm('Remove family member record?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm p-1" title="Delete member">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-people display-6 d-block mb-2 text-secondary"></i>
                                    <div>No family members registered. Click "Add Family Member" to record dependents or emergency contacts.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Statutory & Compliance -->
    <div class="tab-pane fade" id="statutory" role="tabpanel">
        @php $stat = $employee->statutory; @endphp
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Provident Fund (PF) & Pension</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">Universal Account Number (UAN)</td>
                                <td class="code-font fw-semibold">{{ $stat?->uan_number ?? $employee->uan_number ?? 'Not Assigned' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">PF Member ID / Number</td>
                                <td class="code-font">{{ $stat?->pf_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Family Pension Fund (FPF) #</td>
                                <td class="code-font">{{ $stat?->fpf_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">VPF Contribution</td>
                                <td>{{ $stat?->vpf_percentage ? $stat->vpf_percentage . '%' : 'None' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">International Worker</td>
                                <td>
                                    @if($stat?->is_international_worker)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Yes (Expat)</span>
                                    @else
                                        <span class="badge bg-light text-muted border">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Previous PF Member ID</td>
                                <td>{{ $stat?->pf_previous_member_id ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">ESIC & Statutory Tokens</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">PAN Card Number</td>
                                <td class="code-font text-uppercase fw-semibold">{{ $employee->pan_number ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Aadhaar (UIDAI Masked)</td>
                                <td class="code-font fw-semibold">{{ $employee->aadhaar_masked ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">ESIC Insurance Number</td>
                                <td class="code-font">{{ $stat?->esic_number ?? $employee->esic_number ?? 'Not Assigned' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">ESIC Dispensary / IMP</td>
                                <td>{{ $stat?->esic_imp_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Voter ID / Election Card</td>
                                <td class="code-font">{{ $stat?->election_card_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ration Card Number</td>
                                <td class="code-font">{{ $stat?->ration_card_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">NPR Number</td>
                                <td class="code-font">{{ $stat?->npr_number ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 5: Banking & Payment (Page 94 Compensation Segregation) -->
    <div class="tab-pane fade" id="banking" role="tabpanel">
        @php $bank = $employee->bankDetail; @endphp
        <div class="card card-custom">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Bank Disbursement & Payroll Account</span>
                <span class="badge bg-light text-muted border">Page 94 Compensation Segregated</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Bank Name</td>
                                <td class="fw-bold">{{ $bank?->bank_name ?? 'Not Provided' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Branch Name</td>
                                <td>{{ $bank?->bank_branch ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">IFSC Code</td>
                                <td class="code-font text-uppercase fw-semibold">{{ $bank?->bank_ifsc ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Account Number</td>
                                <td class="code-font fw-bold">
                                    {{ $bank?->masked_account_number ?? '—' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%;">Account Type</td>
                                <td><span class="badge bg-light text-dark border">{{ $bank?->bank_account_type ?? 'Salary' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Disbursement Mode</td>
                                <td><span class="badge bg-success-subtle text-success border">{{ $bank?->payment_mode ?? $employee->payment_mode ?? 'Bank Transfer' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Name on Passbook</td>
                                <td>{{ $bank?->bank_passbook_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Name as per PAN</td>
                                <td>{{ $bank?->pan_card_name ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 6: Education & Qualifications -->
    <div class="tab-pane fade" id="education" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <span class="fw-bold">Academic Qualifications & Certifications</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->educations->count() }} Records</span>
                </div>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addEducationModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Qualification</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Qualification / Degree</th>
                            <th>Institute / University</th>
                            <th>Specialization / Major</th>
                            <th>Year of Passing</th>
                            <th>Score / Grade</th>
                            <th>Verification Status</th>
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
                                        <span class="badge bg-success-subtle text-success border"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                                    @elseif($edu->verification_status === 'Rejected')
                                        <span class="badge bg-danger-subtle text-danger border">Rejected</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($edu->certificate_path)
                                        <a href="{{ asset('storage/' . $edu->certificate_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm p-1" title="View Document">
                                            <i class="bi bi-file-earmark-pdf text-danger"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted small">None</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('employees.education.delete', [$employee->id, $edu->id]) }}" onsubmit="return confirm('Delete this qualification?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm p-1" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-mortarboard display-6 d-block mb-2 text-secondary"></i>
                                    <div>No educational records uploaded. Click "Add Qualification" to record degrees.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 7: Skills & Matrix -->
    <div class="tab-pane fade" id="skills" role="tabpanel">
        <div class="card card-custom mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <span class="fw-bold">Employee Competency & Skills Matrix</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->skills->count() }} Skills Evaluated</span>
                </div>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addSkillModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Assess / Add Skill</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Skill Name</th>
                            <th>Category</th>
                            <th>Proficiency</th>
                            <th>Experience (Yrs)</th>
                            <th>Certified</th>
                            <th>Last Assessed</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->skills as $skill)
                            <tr>
                                <td class="fw-semibold">{{ $skill->skill_name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $skill->skill_category }}</span></td>
                                <td>
                                    @php
                                        $pClass = match($skill->proficiency_level) {
                                            'Expert' => 'bg-danger-subtle text-danger',
                                            'Advanced' => 'bg-primary-subtle text-primary',
                                            'Intermediate' => 'bg-success-subtle text-success',
                                            default => 'bg-secondary-subtle text-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $pClass }} border">{{ $skill->proficiency_level }}</span>
                                </td>
                                <td>{{ $skill->years_of_experience ?? '—' }} yrs</td>
                                <td>
                                    @if($skill->is_certified)
                                        <span class="badge bg-success-subtle text-success border"><i class="bi bi-patch-check-fill me-1"></i>Certified</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $skill->last_assessed_at ? $skill->last_assessed_at->format('d M Y') : '—' }}
                                    @if($skill->assessor)
                                        <div class="text-muted small">by {{ $skill->assessor->name }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('employees.skills.delete', [$employee->id, $skill->id]) }}" onsubmit="return confirm('Remove skill?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm p-1" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-stars display-6 d-block mb-2 text-secondary"></i>
                                    <div>No skills assessed. Click "Assess / Add Skill" to build the matrix.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Skill Change History Ledger -->
        <div class="card card-custom">
            <div class="card-header bg-white py-3">
                <span class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Immutable Skill History Ledger</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-custom align-middle mb-0" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Skill</th>
                            <th>Action</th>
                            <th>Previous Level</th>
                            <th>New Level</th>
                            <th>Assessment Notes</th>
                            <th>Assessed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->skills->flatMap->histories->sortByDesc('created_at') as $hist)
                            <tr>
                                <td class="code-font text-muted">{{ $hist->created_at->format('d M Y H:i') }}</td>
                                <td class="fw-semibold">{{ $hist->skill?->skill_name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $hist->action }}</span></td>
                                <td>{{ $hist->old_proficiency ?? '—' }}</td>
                                <td class="fw-semibold text-primary">{{ $hist->new_proficiency }}</td>
                                <td>{{ $hist->assessment_notes ?? '—' }}</td>
                                <td>{{ $hist->changer?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">No skill transitions recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 8: Visa & Immigration -->
    <div class="tab-pane fade" id="visa" role="tabpanel">
        @php $visa = $employee->visaDetail; @endphp
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Passport Information</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">Passport Number</td>
                                <td class="code-font fw-bold text-uppercase">{{ $visa?->passport_number ?? 'Not Recorded' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Issue Date</td>
                                <td>{{ $visa?->passport_issue_date ? $visa->passport_issue_date->format('d M Y') : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Expiry Date</td>
                                <td>
                                    {{ $visa?->passport_expiry_date ? $visa->passport_expiry_date->format('d M Y') : '—' }}
                                    @if($visa?->passport_expiry_date && $visa->passport_expiry_date->isPast())
                                        <span class="badge bg-danger ms-1">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Issuing Country</td>
                                <td>{{ $visa?->passport_issuing_country ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white fw-bold py-3">Visa & Work Authorizations</div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 45%;">Visa Tracking Required</td>
                                <td>
                                    @if($visa?->visa_required)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Required</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Not Required</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Visa Type & Number</td>
                                <td>{{ $visa?->visa_type ?? '—' }} / <span class="code-font">{{ $visa?->visa_number ?? '—' }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Visa Expiry Date</td>
                                <td>
                                    {{ $visa?->visa_expiry_date ? $visa->visa_expiry_date->format('d M Y') : '—' }}
                                    @if($visa?->visa_expiry_date && $visa->visa_expiry_date->isPast())
                                        <span class="badge bg-danger ms-1">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Work Permit Number</td>
                                <td class="code-font">{{ $visa?->work_permit_number ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Work Permit Expiry</td>
                                <td>{{ $visa?->work_permit_expiry_date ? $visa->work_permit_expiry_date->format('d M Y') : '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 9: Documents & eKYC -->
    <div class="tab-pane fade" id="ekyc" role="tabpanel">
        <div class="card card-custom mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold">Employee Document Dossier</span>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <span>Upload Document</span>
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Document Type</th>
                            <th>Filename</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $doc)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $doc->document_category ?? 'General' }}</span></td>
                                <td class="fw-semibold">{{ $doc->document_type }}</td>
                                <td>{{ $doc->file_name }}</td>
                                <td>
                                    @if($doc->expiry_date)
                                        {{ $doc->expiry_date->format('d M Y') }}
                                        @if($doc->expiry_date->isPast())
                                            <span class="badge bg-danger ms-1">Expired</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($doc->verification_status === 'Verified')
                                        <span class="badge bg-success-subtle text-success border"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                                    @elseif($doc->verification_status === 'Rejected')
                                        <span class="badge bg-danger-subtle text-danger border">Rejected</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border">Pending</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $doc->remarks ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm p-1" title="Preview">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-outline-secondary btn-sm p-1" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-folder2-open display-6 d-block mb-2 text-secondary"></i>
                                    <div>No documents on file. Use "Upload Document" to attach identity cards, degrees or forms.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- eKYC Attempts Log -->
        <div class="card card-custom">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">eKYC Verification Attempts ({{ $employee->ekycAttempts->count() }} of 3 Max)</span>
                <span class="badge bg-light text-dark border code-font">Current Status: {{ $employee->ekyc_status }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Timestamp</th>
                            <th>Mode</th>
                            <th>Result</th>
                            <th>Failure Reason</th>
                            <th>Grace Expiry</th>
                            <th>Verified By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->ekycAttempts as $idx => $att)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="code-font text-muted">{{ $att->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $att->attempt_mode }}</td>
                                <td>
                                    @if($att->status === 'Success')
                                        <span class="badge bg-success-subtle text-success border">Success</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $att->failure_reason ?? '—' }}</td>
                                <td>{{ $att->grace_period_ends_at ? $att->grace_period_ends_at->format('d M Y') : '—' }}</td>
                                <td>{{ $att->verifier?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">No verification attempts logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 10: Assets & Allocations -->
    <div class="tab-pane fade" id="assets" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Assigned IT & Physical Assets</span>
                <span class="badge bg-light text-dark border">{{ $employee->assetAllocations->count() }} Total Handed Over</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Asset Tag / Code</th>
                            <th>Name / Model</th>
                            <th>Category</th>
                            <th>Allocated Date</th>
                            <th>Condition</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->assetAllocations as $alloc)
                            <tr>
                                <td class="code-font fw-semibold">{{ $alloc->asset?->asset_code ?? '—' }}</td>
                                <td>{{ $alloc->asset?->name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $alloc->asset?->category?->name ?? '—' }}</span></td>
                                <td>{{ $alloc->allocated_at ? $alloc->allocated_at->format('d M Y') : '—' }}</td>
                                <td>{{ $alloc->condition_at_allocation ?? 'Good' }}</td>
                                <td>
                                    @if($alloc->status === 'Active')
                                        <span class="badge bg-success-subtle text-success border">Active Assigned</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">Returned</span>
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

    <!-- Tab 11: Login & Account Security -->
    <div class="tab-pane fade" id="account" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-key-fill text-primary me-2"></i>Employee Self-Service (ESS) Account</span>
                @if($employee->userAccount)
                    <span class="badge bg-success-subtle text-success border">Linked & Active</span>
                @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border">No User Account</span>
                @endif
            </div>
            <div class="card-body p-4">
                @if($employee->userAccount)
                    <div class="row g-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">User Name</td>
                                    <td class="fw-bold">{{ $employee->userAccount->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Login Email</td>
                                    <td class="code-font">{{ $employee->userAccount->email }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Assigned Role</td>
                                    <td><span class="badge bg-primary-subtle text-primary border">{{ $employee->userAccount->role?->name ?? 'Employee' }}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Account Created</td>
                                    <td>{{ $employee->userAccount->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Account Status</td>
                                    <td><span class="badge bg-success-subtle text-success border">Active</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-person-x display-6 text-muted mb-2 d-block"></i>
                        <h6>No ESS Portal Account Linked</h6>
                        <p class="text-muted mx-auto mb-3" style="max-width: 480px; font-size: 13px;">
                            Provisioning an ESS account allows this employee to log in to the employee self-service portal, view payslips, submit leave applications, and update personal profile records.
                        </p>
                        <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#provisionAccessModal">
                            <i class="bi bi-person-plus-fill me-1"></i> Provision Account Now
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tab 12: Profile Change History Ledger -->
    <div class="tab-pane fade" id="audit" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white py-3">
                <span class="fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Employee Profile Change History Ledger</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-custom align-middle mb-0" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Action / Event</th>
                            <th>Changed By</th>
                            <th>Reason for Revision</th>
                            <th>Changes Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->profileHistories->sortByDesc('created_at') as $audit)
                            <tr>
                                <td class="code-font text-muted">{{ $audit->created_at->format('d M Y H:i') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $audit->action }}</span></td>
                                <td class="fw-semibold">{{ $audit->changer?->name ?? 'System' }}</td>
                                <td>{{ $audit->change_reason ?? '—' }}</td>
                                <td>
                                    @if(!empty($audit->new_values))
                                        <details style="cursor: pointer;">
                                            <summary class="text-primary small">View {{ count($audit->new_values) }} Modified Keys</summary>
                                            <div class="bg-light p-2 rounded mt-1 border" style="font-size: 11px; max-height: 140px; overflow-y: auto;">
                                                @foreach($audit->new_values as $k => $v)
                                                    <div><strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-muted small">No specific delta</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No profile revision history recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 13: Employee Lifecycle Management & Transitions -->
    <div class="tab-pane fade" id="lifecycle" role="tabpanel">
        
        <!-- Lifecycle Header & Quick Actions -->
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="fw-bold mb-0 text-dark">Employee Lifecycle Management</h5>
                            <span class="badge bg-primary-subtle text-primary border rounded-pill">{{ $employee->lifecycleEvents->count() }} Recorded Events</span>
                        </div>
                        <p class="text-muted small mb-0">Track employment status transitions, probation confirmation, branch transfers, and intern-to-permanent conversions with full audit trails.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#branchTransferModal">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Transfer Branch / Dept</span>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#convertTypeModal">
                            <i class="bi bi-person-check-fill"></i>
                            <span>Convert Employment Type</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#manageConfirmationModal">
                            <i class="bi bi-patch-check"></i>
                            <span>Manage Confirmation</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#updateDojModal">
                            <i class="bi bi-calendar-event"></i>
                            <span>Update DOJ</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Employment Attributes & Confirmation Status Overview -->
        <div class="row g-4 mb-4">
            <!-- Left: Joining vs Current Attributes -->
            <div class="col-lg-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Organizational Allocation</span>
                        @if($employee->joining_location_id && $employee->joining_location_id !== $employee->location_id)
                            <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-arrow-left-right me-1"></i>Transferred</span>
                        @else
                            <span class="badge bg-light text-muted border">Base Location</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 13px;">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5" style="width: 38%;">Date of Joining (DOJ)</td>
                                    <td class="pe-3 py-2.5">
                                        @if($employee->date_of_joining)
                                            <span class="fw-bold text-dark">{{ $employee->date_of_joining->format('d M Y') }}</span>
                                            <span class="badge bg-light text-muted border ms-2">{{ $employee->date_of_joining->diffForHumans(null, true) }} tenure</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Current Branch / Site</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="fw-semibold text-dark">{{ $employee->location?->name ?? 'Unassigned' }}</span>
                                        <span class="text-muted small">({{ $employee->location?->code ?? 'N/A' }})</span>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Original Joining Branch</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="text-secondary">{{ $employee->joiningLocation?->name ?? ($employee->location?->name ?? 'Unassigned') }}</span>
                                        @if($employee->joining_location_id && $employee->joining_location_id !== $employee->location_id)
                                            <span class="badge bg-info-subtle text-info border ms-1" style="font-size: 10px;">Preserved</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Current Department</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="fw-medium">{{ $employee->department?->name ?? 'Unassigned' }}</span>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Current Designation</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="fw-medium">{{ $employee->designation?->title ?? 'Unassigned' }}</span>
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Reporting Manager</td>
                                    <td class="pe-3 py-2.5">
                                        @if($employee->reportingManager)
                                            <span class="fw-semibold text-dark">{{ $employee->reportingManager->full_name }}</span>
                                            <span class="text-muted code-font small">({{ $employee->reportingManager->employee_code }})</span>
                                        @else
                                            <span class="text-muted">No Manager Assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-3 py-2.5">Code Gen Method</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="badge bg-light text-dark border code-font">{{ $employee->code_generation_method ?? 'System Auto' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Confirmation & Probation Overview -->
            <div class="col-lg-6">
                <div class="card card-custom h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6"><i class="bi bi-patch-check-fill text-success me-2"></i>Probation & Confirmation</span>
                        @php
                            $cStatus = $employee->confirmation_status ?? 'On Probation';
                        @endphp
                        @if($cStatus === 'Confirmed')
                            <span class="badge bg-success-subtle text-success border"><i class="bi bi-patch-check-fill me-1"></i>Confirmed</span>
                        @elseif($cStatus === 'Confirmation Due')
                            <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-clock-history me-1"></i>Due</span>
                        @elseif($cStatus === 'Probation Extended')
                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-arrow-repeat me-1"></i>Extended</span>
                        @elseif($cStatus === 'On Probation')
                            <span class="badge bg-info-subtle text-info border"><i class="bi bi-hourglass-split me-1"></i>On Probation</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border">{{ $cStatus }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-borderless align-middle mb-0" style="font-size: 13px;">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5" style="width: 38%;">Employment Type</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="badge bg-primary-subtle text-primary border">{{ $employee->employment_type ?? $employee->employee_type ?? 'Permanent' }}</span>
                                        @if($employee->initial_employment_status)
                                            <span class="text-muted small ms-1">(Initial: {{ $employee->initial_employment_status }})</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Probation Period</td>
                                    <td class="pe-3 py-2.5">
                                        @if($employee->probation_start_date)
                                            <span class="fw-medium">{{ $employee->probation_start_date->format('d M Y') }}</span>
                                            <span class="text-muted">to</span>
                                            <span class="fw-medium">{{ $employee->probation_end_date?->format('d M Y') ?? 'Ongoing' }}</span>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Confirmation Due Date</td>
                                    <td class="pe-3 py-2.5">
                                        @if($employee->confirmation_due_date)
                                            <span class="fw-bold text-dark">{{ $employee->confirmation_due_date->format('d M Y') }}</span>
                                            @if($employee->confirmation_status !== 'Confirmed')
                                                @if($employee->confirmation_due_date->isPast())
                                                    <span class="badge bg-danger-subtle text-danger border ms-1">Overdue by {{ $employee->confirmation_due_date->diffInDays(now()) }} days</span>
                                                @else
                                                    <span class="badge bg-light text-muted border ms-1">Due in {{ now()->diffInDays($employee->confirmation_due_date) }} days</span>
                                                @endif
                                            @endif
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="text-muted ps-3 py-2.5">Confirmation Letter</td>
                                    <td class="pe-3 py-2.5">
                                        @if($employee->confirmation_letter_path)
                                            <a href="{{ asset('storage/' . $employee->confirmation_letter_path) }}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 11.5px;">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>View Letter
                                            </a>
                                        @else
                                            <span class="text-muted small">No document uploaded</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-3 py-2.5">Confirmation Remarks</td>
                                    <td class="pe-3 py-2.5">
                                        <span class="text-secondary small">{{ $employee->confirmation_remarks ?? 'None recorded' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Branch & Department Transfers History -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold fs-6"><i class="bi bi-arrow-left-right text-warning me-2"></i>Branch & Department Transfer History</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->branchTransfers->count() }} Transfers</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#branchTransferModal">
                    <i class="bi bi-plus-lg me-1"></i>New Transfer
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr style="font-size: 11.5px; text-transform: uppercase;">
                            <th>Effective Date</th>
                            <th>Type</th>
                            <th>From Branch</th>
                            <th>To Branch</th>
                            <th>Department & Designation</th>
                            <th>Reporting Manager</th>
                            <th>Reason / Status</th>
                            <th class="text-end">Order Letter</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->branchTransfers->sortByDesc('effective_date') as $transfer)
                            <tr>
                                <td class="fw-semibold">{{ $transfer->effective_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge {{ $transfer->transfer_type === 'Permanent' ? 'bg-primary-subtle text-primary border' : 'bg-warning-subtle text-warning-emphasis border' }}">
                                        {{ $transfer->transfer_type }}
                                    </span>
                                </td>
                                <td>{{ $transfer->fromLocation?->name ?? '—' }}</td>
                                <td class="fw-semibold text-dark">{{ $transfer->toLocation?->name ?? '—' }}</td>
                                <td>
                                    <div>{{ $transfer->toDepartment?->name ?? ($employee->department?->name ?? '—') }}</div>
                                    <div class="text-muted small">{{ $transfer->toDesignation?->title ?? ($employee->designation?->title ?? '—') }}</div>
                                </td>
                                <td>
                                    {{ $transfer->toReportingManager?->full_name ?? '—' }}
                                </td>
                                <td>
                                    <div class="small text-truncate" style="max-width: 180px;" title="{{ $transfer->reason }}">{{ $transfer->reason }}</div>
                                    <span class="badge bg-success-subtle text-success border" style="font-size: 10px;">{{ $transfer->status }}</span>
                                </td>
                                <td class="text-end">
                                    @if($transfer->transfer_order_letter_path)
                                        <a href="{{ asset('storage/' . $transfer->transfer_order_letter_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11.5px;">
                                            <i class="bi bi-download me-1"></i>Order
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No branch or department transfers recorded for this employee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Intern-to-Permanent Conversions -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold fs-6"><i class="bi bi-person-check-fill text-success me-2"></i>Employment Type & Intern Conversions</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->conversions->count() }} Conversions</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#convertTypeModal">
                    <i class="bi bi-plus-lg me-1"></i>New Conversion
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr style="font-size: 11.5px; text-transform: uppercase;">
                            <th>Effective Date</th>
                            <th>Transition</th>
                            <th>Designation Change</th>
                            <th>CTC Revision</th>
                            <th>Initiator</th>
                            <th>Remarks & Status</th>
                            <th class="text-end">Letter</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->conversions->sortByDesc('effective_date') as $conv)
                            <tr>
                                <td class="fw-semibold">{{ $conv->effective_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ $conv->from_employment_type }}</span>
                                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                    <span class="badge bg-success-subtle text-success border">{{ $conv->to_employment_type }}</span>
                                </td>
                                <td>
                                    <div>{{ $conv->toDesignation?->title ?? $employee->designation?->title }}</div>
                                    @if($conv->fromDesignation && $conv->from_designation_id !== $conv->to_designation_id)
                                        <div class="text-muted small" style="font-size: 11px;">Prev: {{ $conv->fromDesignation->title }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($conv->new_ctc)
                                        <div class="fw-semibold text-dark">₹{{ number_format($conv->new_ctc, 2) }}</div>
                                        @if($conv->old_ctc)
                                            <div class="text-muted small" style="font-size: 11px;">Prev: ₹{{ number_format($conv->old_ctc, 2) }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $conv->initiator?->name ?? 'System' }}</td>
                                <td>
                                    <div class="small text-truncate" style="max-width: 180px;" title="{{ $conv->remarks }}">{{ $conv->remarks ?? '—' }}</div>
                                    <span class="badge bg-success-subtle text-success border" style="font-size: 10px;">{{ $conv->status }}</span>
                                </td>
                                <td class="text-end">
                                    @if($conv->conversion_letter_path)
                                        <a href="{{ asset('storage/' . $conv->conversion_letter_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11.5px;">
                                            <i class="bi bi-download me-1"></i>Letter
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No employment conversions recorded. Candidate retains original employment classification.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 4: Confirmation & Probation Records History -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold fs-6"><i class="bi bi-patch-check text-primary me-2"></i>Confirmation & Probation Extension Ledger</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->confirmationRecords->count() }} Decisions</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageConfirmationModal">
                    <i class="bi bi-plus-lg me-1"></i>Record Decision
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr style="font-size: 11.5px; text-transform: uppercase;">
                            <th>Decision Date</th>
                            <th>Action Taken</th>
                            <th>Effective Date</th>
                            <th>Extended Until</th>
                            <th>Justification / Remarks</th>
                            <th>Decision By</th>
                            <th class="text-end">Letter</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->confirmationRecords->sortByDesc('decision_date') as $rec)
                            <tr>
                                <td class="fw-semibold">{{ $rec->decision_date->format('d M Y') }}</td>
                                <td>
                                    @if($rec->action === 'Confirmed')
                                        <span class="badge bg-success-subtle text-success border"><i class="bi bi-check2-circle me-1"></i>Confirmed</span>
                                    @elseif($rec->action === 'Probation Extended')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-clock-history me-1"></i>Extended</span>
                                    @elseif($rec->action === 'Employment Discontinued')
                                        <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-x-circle me-1"></i>Discontinued</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">{{ $rec->action }}</span>
                                    @endif
                                </td>
                                <td>{{ $rec->effective_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $rec->extended_due_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <div class="small" style="max-width: 250px;">{{ $rec->justification }}</div>
                                </td>
                                <td>{{ $rec->approver?->name ?? 'System' }}</td>
                                <td class="text-end">
                                    @if($rec->letter_path)
                                        <a href="{{ asset('storage/' . $rec->letter_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 11.5px;">
                                            <i class="bi bi-download me-1"></i>Letter
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No formal confirmation or probation extension decisions recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 5: Master Employee Lifecycle Events Audit Log -->
        <div class="card card-custom">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold fs-6"><i class="bi bi-clock-history text-secondary me-2"></i>Comprehensive Lifecycle Audit Ledger</span>
                    <span class="badge bg-light text-dark border ms-2">{{ $employee->lifecycleEvents->count() }} Entries</span>
                </div>
                <span class="text-muted small">Immutable administrative timeline</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr style="font-size: 11.5px; text-transform: uppercase;">
                            <th>Timestamp</th>
                            <th>Event Type</th>
                            <th>Description</th>
                            <th>Effective Date</th>
                            <th>Requested By</th>
                            <th>Approved By</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->lifecycleEvents->sortByDesc('created_at') as $event)
                            <tr>
                                <td class="code-font text-muted">{{ $event->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border code-font">{{ $event->event_type }}</span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $event->action_description }}</div>
                                    @if($event->remarks)
                                        <div class="text-muted small">{{ $event->remarks }}</div>
                                    @endif
                                </td>
                                <td>{{ $event->effective_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $event->requester?->name ?? 'System' }}</td>
                                <td>{{ $event->approver?->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if(!empty($event->new_values) || !empty($event->old_values))
                                        <details style="cursor: pointer;">
                                            <summary class="text-primary small">View Delta</summary>
                                            <div class="bg-light p-2 rounded mt-1 border text-start" style="font-size: 11px; max-height: 120px; overflow-y: auto;">
                                                @if(!empty($event->old_values))
                                                    <div class="text-danger fw-semibold">Old Values:</div>
                                                    @foreach($event->old_values as $k => $v)
                                                        <div><span class="text-muted">{{ $k }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                                    @endforeach
                                                @endif
                                                @if(!empty($event->new_values))
                                                    <div class="text-success fw-semibold mt-1">New Values:</div>
                                                    @foreach($event->new_values as $k => $v)
                                                        <div><span class="text-muted">{{ $k }}:</span> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No lifecycle events recorded for this employee yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- MODAL 1: Approve Profile -->
<div class="modal fade" id="approveProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.approve', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-success"><i class="bi bi-patch-check-fill me-1"></i> Approve Employee Master Dossier</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size: 13px; color: #475569;">
                        You are verifying and approving the master profile for <strong>{{ $employee->full_name }}</strong> ({{ $employee->employee_code }}).
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Approval Remarks</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="All documents and statutory records verified against physical submissions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">Confirm & Approve Dossier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Request Corrections -->
<div class="modal fade" id="requestCorrectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.request-correction', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i> Request Dossier Corrections</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 12.5px;">
                        The candidate will be notified to revise their dossier through Employee Self-Service.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Correction Instructions <span class="text-danger">*</span></label>
                        <textarea name="correction_notes" class="form-control form-control-sm" rows="3" placeholder="Please upload a legible copy of your PAN card and fill out permanent address..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Send Correction Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: Provision Access -->
<div class="modal fade" id="provisionAccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.provision-access', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-primary"><i class="bi bi-key-fill me-1"></i> Provision ESS Portal Access</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Login Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm" value="{{ $employee->email ?? '' }}" required placeholder="employee@company.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Temporary Password (Optional)</label>
                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Leave blank to generate random password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Provision & Enable Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: Add Family Member -->
<div class="modal fade" id="addFamilyMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.family.add', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-people me-1 text-primary"></i> Add Family Member / Dependent</h6>
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
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="famEmergSwitch">
                                <label class="form-check-label fw-semibold" for="famEmergSwitch" style="font-size: 12.5px;">Mark as Primary Emergency Contact</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_dependent" value="1" id="famDepSwitch" checked>
                                <label class="form-check-label fw-semibold" for="famDepSwitch" style="font-size: 12.5px;">Dependent for Health Insurance / Tax</label>
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

<!-- MODAL 5: Add Education -->
<div class="modal fade" id="addEducationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.education.add', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-mortarboard me-1 text-primary"></i> Add Academic Qualification</h6>
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
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Qualification / Degree Name <span class="text-danger">*</span></label>
                            <input type="text" name="qualification_name" class="form-control form-control-sm" placeholder="e.g. B.Tech Computer Science" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Institute / University <span class="text-danger">*</span></label>
                            <input type="text" name="institute_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Specialization</label>
                            <input type="text" name="specialization" class="form-control form-control-sm" placeholder="e.g. Mechanical, Finance">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Year Passed <span class="text-danger">*</span></label>
                            <input type="number" name="year_of_passing" class="form-control form-control-sm" value="{{ date('Y') }}" min="1960" max="{{ date('Y') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Score / %</label>
                            <input type="number" step="0.01" name="percentage_or_cgpa" class="form-control form-control-sm" placeholder="85.5">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Degree Certificate / Marksheet (PDF/JPG)</label>
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

<!-- MODAL 6: Assess / Add Skill -->
<div class="modal fade" id="addSkillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.skills.add', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-stars me-1 text-primary"></i> Assess / Add Skill</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Skill Name <span class="text-danger">*</span></label>
                            <input type="text" name="skill_name" class="form-control form-control-sm" placeholder="e.g. PHP / Laravel, CNC Machining" required>
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
                            <input type="number" step="0.5" name="years_of_experience" class="form-control form-control-sm" placeholder="e.g. 3.5">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_certified" value="1" id="skillCertSwitch">
                                <label class="form-check-label fw-semibold" for="skillCertSwitch" style="font-size: 12.5px;">Formally Certified in this Skill</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Assessment Remarks / Evaluation Notes</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Notes on competency evaluation..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Record Skill Assessment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 7: Upload Document -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('documents.store', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up-fill me-1 text-primary"></i> Upload Employee Document</h6>
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
                        <input type="text" name="document_type" class="form-control form-control-sm" placeholder="e.g. Aadhaar Card, Degree Certificate, Offer Letter" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Select Document File (PDF, JPG, PNG) <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Document Expiry Date (If Applicable)</label>
                        <input type="date" name="expiry_date" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Remarks / Verification Notes</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional notes...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Upload & Secure Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Update Gate Pass -->
<div class="modal fade" id="updateGatePassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.gate-pass.update', $employee->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Update Gate Pass Number</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Current Gate Pass</label>
                        <input type="text" class="form-control form-control-sm bg-light" value="{{ $employee->gate_pass_number ?? 'None' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">New Gate Pass Number <span class="text-danger">*</span></label>
                        <input type="text" name="gate_pass_number" class="form-control form-control-sm" placeholder="e.g. GP-2026-0042" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Mandatory Reason for Change <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" class="form-control form-control-sm" placeholder="e.g. Badge damaged, Lost card replacement..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand btn-sm">Update & Issue Pass</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Trigger eKYC Check -->
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

<!-- Modal: HO Exception Override -->
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

<!-- MODAL: Branch & Department Transfer -->
<div class="modal fade" id="branchTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.lifecycle.transfer', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-arrow-left-right text-warning me-2"></i>Initiate Employee Branch / Department Transfer</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 12.5px;">
                        <strong>Base Location Preservation:</strong> The employee's original joining location (<strong>{{ $employee->joiningLocation?->name ?? $employee->location?->name }}</strong>) will be permanently preserved for statutory compliance and multi-unit tenure reporting.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Transfer Type <span class="text-danger">*</span></label>
                            <select name="transfer_type" class="form-select form-select-sm" required>
                                <option value="Permanent">Permanent Transfer</option>
                                <option value="Temporary">Temporary Deputation / Site Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Target Branch / Location <span class="text-danger">*</span></label>
                            <select name="to_location_id" class="form-select form-select-sm" required>
                                <option value="">Select Destination Branch</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ $employee->location_id == $loc->id ? 'disabled' : '' }}>
                                        {{ $loc->name }} ({{ $loc->code }}) {{ $employee->location_id == $loc->id ? '— (Current)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Target Department</label>
                            <select name="to_department_id" class="form-select form-select-sm">
                                <option value="">Retain Current ({{ $employee->department?->name ?? 'Unassigned' }})</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Target Designation</label>
                            <select name="to_designation_id" class="form-select form-select-sm">
                                <option value="">Retain Current ({{ $employee->designation?->title ?? 'Unassigned' }})</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">New Reporting Manager</label>
                            <select name="to_reporting_manager_id" class="form-select form-select-sm">
                                <option value="">Retain Current ({{ $employee->reportingManager?->full_name ?? 'None' }})</option>
                                @foreach($managers as $mgr)
                                    <option value="{{ $mgr->id }}">{{ $mgr->full_name }} ({{ $mgr->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Expected Return Date (For Temporary)</label>
                            <input type="date" name="expected_return_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Transfer Order Letter (Optional)</label>
                            <input type="file" name="transfer_order_letter" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Operational Justification / Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="e.g. Relocation requested by Plant Operations Manager for project expansion..." required minlength="5"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Execute Branch Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Convert Employment Type (Intern to Permanent) -->
<div class="modal fade" id="convertTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.lifecycle.convert', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-person-check-fill text-success me-2"></i>Convert Employment Type</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success py-2 px-3 mb-3" style="font-size: 12.5px;">
                        <strong>Employee ID & Code Continuity:</strong> Employee Code <code>{{ $employee->employee_code }}</code> and internal ID #{{ $employee->id }} will remain identical. All attendance, leave balances, and statutory identifiers will seamlessly carry over.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Current Type</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $employee->employment_type ?? $employee->employee_type ?? 'Permanent' }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">New Employment Type <span class="text-danger">*</span></label>
                            <select name="to_employment_type" class="form-select form-select-sm" required>
                                <option value="Permanent" selected>Permanent</option>
                                <option value="Contract">Contract</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Trainee">Trainee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Effective Conversion Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Revised Annual CTC (₹)</label>
                            <input type="number" step="0.01" name="new_ctc" class="form-control form-control-sm" placeholder="e.g. 450000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">New Designation</label>
                            <select name="to_designation_id" class="form-select form-select-sm">
                                <option value="">Retain Current ({{ $employee->designation?->title ?? 'Unassigned' }})</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}">{{ $desig->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">New Department</label>
                            <select name="to_department_id" class="form-select form-select-sm">
                                <option value="">Retain Current ({{ $employee->department?->name ?? 'Unassigned' }})</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Conversion Letter / Agreement (Optional)</label>
                            <input type="file" name="conversion_letter" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Remarks / Conversion Note</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Successfully completed 6-month internship with exemplary ratings..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">Confirm Conversion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Manage Confirmation & Probation -->
<div class="modal fade" id="manageConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-patch-check text-primary me-2"></i>Manage Probation & Confirmation</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills nav-fill mb-3" id="confirmPills" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active small py-1.5" id="pills-confirm-tab" data-bs-toggle="pill" data-bs-target="#pills-confirm" type="button" role="tab">Confirm Employee</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link small py-1.5" id="pills-extend-tab" data-bs-toggle="pill" data-bs-target="#pills-extend" type="button" role="tab">Extend Probation</button>
                    </li>
                </ul>
                <div class="tab-content" id="confirmPillsContent">
                    <!-- Tab 1: Confirm Employee -->
                    <div class="tab-pane fade show active" id="pills-confirm" role="tabpanel">
                        <form method="POST" action="{{ route('employees.lifecycle.confirm', $employee->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Effective Confirmation Date <span class="text-danger">*</span></label>
                                <input type="date" name="effective_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Confirmation Letter (PDF/Image)</label>
                                <input type="file" name="confirmation_letter" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Performance Appraisal Remarks</label>
                                <textarea name="remarks" class="form-control form-control-sm" rows="3" placeholder="Performance appraisal score, confirmation justification, or probation review outcome..."></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success btn-sm px-3">Confirm Employment</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Extend Probation -->
                    <div class="tab-pane fade" id="pills-extend" role="tabpanel">
                        <form method="POST" action="{{ route('employees.lifecycle.extend-probation', $employee->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Extended Until Date <span class="text-danger">*</span></label>
                                <input type="date" name="extended_until" class="form-control form-control-sm" value="{{ now()->addMonths(3)->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Extension Notification Letter (Optional)</label>
                                <input type="file" name="extension_letter" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Mandatory Rationale for Extension <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="State specific performance metrics, attendance deficiencies, or technical competencies needing extension..." required minlength="10"></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-warning btn-sm px-3">Extend Probation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Update Date of Joining -->
<div class="modal fade" id="updateDojModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.lifecycle.update-doj', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-calendar-event text-secondary me-2"></i>Update Date of Joining (DOJ)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 12px;">
                        <strong>Audit Notice:</strong> Updating DOJ triggers recalculation of service tenure, gratuity accrual, and probation milestones. An immutable audit record will be logged.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Current Date of Joining</label>
                        <input type="text" class="form-control form-control-sm bg-light" value="{{ $employee->date_of_joining?->format('d M Y') ?? 'Not Set' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">New Date of Joining <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_joining" class="form-control form-control-sm" value="{{ $employee->date_of_joining?->format('Y-m-d') ?? '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Supporting Document (Offer Letter / Agreement)</label>
                        <input type="file" name="supporting_document" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mandatory Reason for Revision <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="e.g. Corrected based on signed joining acknowledgment slip..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Update Date of Joining</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hash = window.location.hash;
    
    if (hash === '#lifecycle' || urlParams.has('action')) {
        const lifecycleTab = document.getElementById('lifecycle-tab');
        if (lifecycleTab) {
            bootstrap.Tab.getOrCreateInstance(lifecycleTab).show();
        }
        const action = urlParams.get('action');
        if (action === 'transfer') {
            const m = new bootstrap.Modal(document.getElementById('branchTransferModal'));
            m.show();
        } else if (action === 'convert') {
            const m = new bootstrap.Modal(document.getElementById('convertTypeModal'));
            m.show();
        } else if (action === 'confirm') {
            const m = new bootstrap.Modal(document.getElementById('manageConfirmationModal'));
            m.show();
        }
    }
});
</script>

@endsection
