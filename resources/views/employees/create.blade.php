@extends('layouts.app')

@section('title', 'Onboard Candidate — Multi-Step Dossier')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Workforce</a></li>
                <li class="breadcrumb-item active" aria-current="page">Employee Onboarding Wizard</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Enterprise Workforce Onboarding</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Complete 16-section dossier registration with automated duplicate checks, quota validation & statutory tokens. <span class="d-none">Candidate Dossier Registration</span></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('employees.drafts.index') }}" class="btn btn-outline-teal btn-sm d-flex align-items-center gap-1 shadow-xs fw-semibold" style="color: #0D9488; border-color: #0D9488;" title="View all saved onboarding drafts">
            <i class="bi bi-clock-history"></i>
            <span>Drafts ({{ $activeDraftsCount ?? 0 }})</span>
        </a>
        @if(auth()->user()->isHoAdmin())
            <a href="{{ route('admin.masters.index') }}" target="_blank" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-xs" title="Manage Dropdown Masters">
                <i class="bi bi-ui-checks-grid"></i>
                <span>Dropdown Masters</span>
            </a>
        @endif
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-xs">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Roster</span>
        </a>
    </div>
</div>

<!-- Auto-Save Status & Progress Indicator Bar -->
<div class="card border rounded-3 shadow-xs mb-3 bg-white" id="autoSaveStatusBar">
    <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <!-- Left: Save Status Pill -->
        <div class="d-flex align-items-center gap-3">
            <div id="autoSaveStatusIndicator" class="d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border bg-light text-secondary shadow-xs" style="font-size: 12px; font-weight: 500;">
                @if(isset($draft) && $draft->last_saved_at)
                    <i class="bi bi-cloud-check-fill text-success" id="saveStatusIcon"></i>
                    <span id="saveStatusText">Saved at {{ $draft->last_saved_at->format('h:i A') }}</span>
                @else
                    <i class="bi bi-circle-fill text-secondary opacity-50" id="saveStatusIcon" style="font-size: 8px;"></i>
                    <span id="saveStatusText">Ready (Changes auto-save)</span>
                @endif
            </div>

            <div class="vr d-none d-sm-block my-1"></div>

            <!-- Draft ID info badge if resuming -->
            <div id="draftIdBadgeContainer" class="{{ isset($draft) ? '' : 'd-none' }}">
                <span class="badge bg-light text-dark border font-monospace shadow-xs" style="font-size: 11px;">
                    Draft ID: <strong id="activeDraftIdDisplay">{{ $draft?->draft_id }}</strong>
                </span>
                <span class="text-muted small ms-1" id="draftVersionDisplay">v{{ $draft?->version ?? 1 }}</span>
            </div>
        </div>

        <!-- Right: Completion % & Manual Save Action -->
        <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center gap-1.5 me-2">
                <small class="text-muted fw-semibold" style="font-size: 11.5px;">Dossier Progress:</small>
                <span class="badge bg-teal text-white rounded-pill px-2 py-0.5" id="completionPercentageBadge" style="background-color: #0D9488; font-size: 11px;">
                    {{ $draft?->completion_percentage ?? 6 }}%
                </span>
            </div>

            <button type="button" class="btn btn-sm btn-outline-teal d-inline-flex align-items-center gap-1 fw-semibold shadow-xs" id="manualSaveDraftBtn" style="color: #0D9488; border-color: #0D9488; font-size: 12px;">
                <i class="bi bi-floppy"></i>
                <span>Save Draft Now</span>
            </button>

            <a href="{{ route('employees.drafts.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-xs" style="font-size: 12px;" title="View all onboarding drafts">
                <i class="bi bi-clock-history"></i>
                <span>All Drafts ({{ $activeDraftsCount ?? 0 }})</span>
            </a>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" id="formErrorsAlert" role="alert">
        <div class="d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
            <div>
                <strong>Please resolve the following form errors:</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size: 13px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('employees.store') }}" id="onboardingWizardForm" enctype="multipart/form-data" autocomplete="off">
    @csrf

    <!-- Persistent Draft Hidden Tracking Fields -->
    <input type="hidden" name="draft_id" id="draftIdInput" value="{{ $draft?->draft_id }}">
    <input type="hidden" name="draft_version" id="draftVersionInput" value="{{ $draft?->version ?? 1 }}">
    <input type="hidden" name="current_step" id="currentStepInput" value="{{ $draft?->current_step ?? 1 }}">

    <!-- Prevent browser credential manager auto-filling admin credentials -->
    <input type="text" style="display:none" aria-hidden="true" tabindex="-1">
    <input type="password" style="display:none" aria-hidden="true" tabindex="-1">

    <div class="row g-4">
        <!-- Left: Step Navigation Sidebar -->
        <div class="col-lg-3">
            <div class="card card-custom sticky-top" style="top: 85px; z-index: 10;">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-weight: 700; font-size: 13px; color: #1E293B;">ONBOARDING SECTIONS</span>
                        <span class="badge bg-primary-subtle text-primary fw-bold" id="stepCounterBadge">Step 1 of 16</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="wizardProgressBar" role="progressbar" style="width: 6.25%;" aria-valuenow="6" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="list-group list-group-flush" id="wizardStepsNav" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                    @php
                        $steps = [
                            1 => ['name' => 'Basic Information', 'icon' => 'bi-person'],
                            2 => ['name' => 'Employment Information', 'icon' => 'bi-briefcase'],
                            3 => ['name' => 'Contact Information', 'icon' => 'bi-telephone'],
                            4 => ['name' => 'Address Information', 'icon' => 'bi-geo-alt'],
                            5 => ['name' => 'Family Details', 'icon' => 'bi-people'],
                            6 => ['name' => 'Personal Details', 'icon' => 'bi-person-badge'],
                            7 => ['name' => 'Statutory & Compliance', 'icon' => 'bi-shield-check'],
                            8 => ['name' => 'Banking & Identity', 'icon' => 'bi-bank'],
                            9 => ['name' => 'Education & Qualifications', 'icon' => 'bi-mortarboard'],
                            10 => ['name' => 'Skills & Skill Matrix', 'icon' => 'bi-stars'],
                            11 => ['name' => 'Visa & Immigration', 'icon' => 'bi-globe'],
                            12 => ['name' => 'Documents & Certificates', 'icon' => 'bi-file-earmark-arrow-up'],
                            13 => ['name' => 'Assets & IT Info', 'icon' => 'bi-laptop'],
                            14 => ['name' => 'Login & Account Access', 'icon' => 'bi-key'],
                            15 => ['name' => 'User Defined Fields', 'icon' => 'bi-sliders'],
                            16 => ['name' => 'Review & Submission', 'icon' => 'bi-check2-circle'],
                        ];
                    @endphp

                    @foreach($steps as $num => $step)
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 px-3 wizard-step-btn {{ $num === 1 ? 'active text-white' : '' }}" data-step="{{ $num }}">
                            <div class="d-flex align-items-center gap-2" style="font-size: 13px;">
                                <i class="bi {{ $step['icon'] }} step-icon"></i>
                                <span>{{ $num }}. {{ $step['name'] }}</span>
                            </div>
                            <i class="bi bi-check-circle-fill text-success step-status-icon d-none" id="stepCheck-{{ $num }}"></i>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right: Step Form Content Cards -->
        <div class="col-lg-9">
            <div class="card card-custom shadow-sm mb-4">
                <div class="card-body p-4">

                    <!-- SECTION 1: BASIC INFORMATION -->
                    <div class="wizard-section" id="section-1">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-person text-primary me-2"></i>1. Basic Employee Information</h5>
                                <p class="text-muted mb-0" style="font-size: 13px;">Core identification, names, and bio attributes.</p>
                            </div>
                            <span class="badge bg-light text-dark border code-font">Auto-Code: {{ $activeCompany->code }}-EMP-XXXX</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3 text-center mb-3">
                                <label class="form-label d-block text-muted" style="font-size: 12px; font-weight: 600;">PROFILE PHOTO</label>
                                <div class="position-relative d-inline-block">
                                    <div id="photoPreviewBox" style="width: 110px; height: 110px; border-radius: 50%; background: #F1F5F9; border: 2px dashed #CBD5E1; display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto;">
                                        <i class="bi bi-camera text-muted fs-2" id="photoPlaceholderIcon"></i>
                                        <img id="photoPreviewImg" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                    </div>
                                    <label for="profile_photo_input" class="btn btn-sm btn-light border position-absolute bottom-0 end-0 rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Upload Photo">
                                        <i class="bi bi-pencil" style="font-size: 12px;"></i>
                                    </label>
                                    <input type="file" name="profile_photo" id="profile_photo_input" class="d-none" accept="image/png,image/jpeg">
                                </div>
                                <div class="form-text" style="font-size: 11px;">Max 2MB (JPG, PNG)</div>
                            </div>

                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-weight: 500;">Title</label>
                                        <select name="title" class="form-select">
                                            <option value="">Select</option>
                                            @if(isset($masters['title']) && count($masters['title']) > 0)
                                                @foreach($masters['title'] as $item)
                                                    <option value="{{ $item->name }}" {{ old('title') === $item->name ? 'selected' : '' }}>{{ $item->name }}</option>
                                                @endforeach
                                            @else
                                                <option value="Mr." {{ old('title') === 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                                <option value="Ms." {{ old('title') === 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                                <option value="Mrs." {{ old('title') === 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                                <option value="Dr." {{ old('title') === 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-weight: 500;">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $draft?->form_data['first_name'] ?? ($draft?->first_name ?? '')) }}" placeholder="e.g. Rahul">
                                        @error('first_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-weight: 500;">Middle Name</label>
                                        <input type="text" name="middle_name" id="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name', $draft?->form_data['middle_name'] ?? ($draft?->middle_name ?? '')) }}">
                                        @error('middle_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" style="font-weight: 500;">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $draft?->form_data['last_name'] ?? ($draft?->last_name ?? '')) }}" placeholder="e.g. Sharma">
                                        @error('last_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 500;">Ticket #</label>
                                        <input type="text" name="ticket_number" class="form-control" placeholder="Plant punch token" value="{{ old('ticket_number') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 500;">Display Name</label>
                                        <input type="text" name="display_name" class="form-control" placeholder="Preferred badge name" value="{{ old('display_name') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 500;">A.K.A. Name</label>
                                        <input type="text" name="aka_name" class="form-control" placeholder="Alias or alternate name" value="{{ old('aka_name') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    @if(isset($masters['gender']) && count($masters['gender']) > 0)
                                        @foreach($masters['gender'] as $item)
                                            <option value="{{ $item->name }}" {{ old('gender') === $item->name || old('gender') === $item->code ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" id="dobInput" class="form-control" value="{{ old('date_of_birth') }}" max="{{ now()->format('Y-m-d') }}" required>
                                <div id="dobUnderageWarning" class="d-none"></div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-weight: 500;">Age</label>
                                <input type="text" id="calculatedAgeInput" class="form-control bg-light fw-bold text-dark" readonly placeholder="Auto">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-weight: 500;">Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="{{ old('nationality', 'Indian') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-weight: 500;">Place of Birth</label>
                                <input type="text" name="place_of_birth" class="form-control" placeholder="City / Town" value="{{ old('place_of_birth') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select Blood Group</option>
                                    @if(isset($masters['blood_group']) && count($masters['blood_group']) > 0)
                                        @foreach($masters['blood_group'] as $bg)
                                            <option value="{{ $bg->name }}" {{ old('blood_group') === $bg->name || old('blood_group') === $bg->code ? 'selected' : '' }}>{{ $bg->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                            <option value="{{ $bg }}" {{ old('blood_group') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" style="font-weight: 500;">Identification Mark</label>
                                <input type="text" name="identification_mark" class="form-control" placeholder="e.g. Mole on right wrist" value="{{ old('identification_mark') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Specially Abled / Divyaang</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_specially_abled" value="1" id="divyaangSwitch" {{ old('is_specially_abled') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="divyaangSwitch">Yes, Specially Abled</label>
                                </div>
                            </div>
                            <div class="col-12 d-none" id="disabilityDetailsBox">
                                <label class="form-label" style="font-weight: 500;">Disability Category & Percentage</label>
                                <input type="text" name="disability_details" class="form-control" placeholder="Details of impairment and disability certificate reference" value="{{ old('disability_details') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" style="font-weight: 500;">Personal Remarks</label>
                                <textarea name="personal_remarks" class="form-control" rows="2" placeholder="Administrative bio notes...">{{ old('personal_remarks') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: EMPLOYMENT INFORMATION -->
                    <div class="wizard-section d-none" id="section-2">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-briefcase text-primary me-2"></i>2. Employment & Job Information</h5>
                                <p class="text-muted mb-0" style="font-size: 13px;">Department, site allocation, grade, reporting hierarchy, and service dates.</p>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border">Entity: {{ $activeCompany->name }}</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Job Post Code <span class="text-danger">*</span></label>
                                <input type="text" name="job_post_code" class="form-control code-font" placeholder="e.g. JPC-MNC-2026" value="{{ old('job_post_code', 'JPC-' . strtoupper(Str::random(6))) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Grade / Band <span class="text-danger">*</span></label>
                                <select name="grade" class="form-select">
                                    @if(isset($masters['employee_grade']) && count($masters['employee_grade']) > 0)
                                        @foreach($masters['employee_grade'] as $item)
                                            <option value="{{ $item->code }}" {{ old('grade', 'E1') === $item->code || old('grade') === $item->name ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(['E1' => 'E1 - Entry Executive', 'E2' => 'E2 - Executive', 'M1' => 'M1 - Middle Management', 'M2' => 'M2 - Senior Manager', 'W1' => 'W1 - Technical Worker', 'W2' => 'W2 - Shop Floor Operator'] as $val => $lbl)
                                            <option value="{{ $val }}" {{ old('grade', 'E1') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Site / Branch Location <span class="text-danger">*</span></label>
                                <select name="location_id" class="form-select" required>
                                    <option value="">Select Location</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }} ({{ $loc->state }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Designation <span class="text-danger">*</span></label>
                                <select name="designation_id" class="form-select" required>
                                    <option value="">Select Designation</option>
                                    @foreach($designations as $desig)
                                        <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->name ?? $desig->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Cadre Category <span class="text-danger">*</span></label>
                                <select name="employee_category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('employee_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Division</label>
                                <input type="text" name="division" list="divisionsList" class="form-control" placeholder="Operational Division" value="{{ old('division', 'Manufacturing Ops') }}">
                                <datalist id="divisionsList">
                                    @foreach($masters['division'] ?? [] as $div)
                                        <option value="{{ $div->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Unit / Plant Unit</label>
                                <input type="text" name="unit" list="plantUnitsList" class="form-control" placeholder="Unit-1, Fabrication Shop" value="{{ old('unit', 'Unit-1') }}">
                                <datalist id="plantUnitsList">
                                    @foreach($masters['plant_unit'] ?? [] as $pu)
                                        <option value="{{ $pu->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Project Allocation</label>
                                <input type="text" name="project" list="projectsList" class="form-control" placeholder="Default Plant Project" value="{{ old('project', 'General Operations') }}">
                                <datalist id="projectsList">
                                    @foreach($masters['project'] ?? [] as $prj)
                                        <option value="{{ $prj->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Workforce Channel <span class="text-danger">*</span></label>
                                <select name="onboarding_channel" id="onboardingChannelSelect" class="form-select" required>
                                    @if(isset($masters['onboarding_channel']) && count($masters['onboarding_channel']) > 0)
                                        @foreach($masters['onboarding_channel'] as $item)
                                            <option value="{{ $item->name }}" {{ old('onboarding_channel', 'Direct') === $item->name || old('onboarding_channel') === $item->code ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Direct" {{ old('onboarding_channel', 'Direct') === 'Direct' ? 'selected' : '' }}>Direct Workforce</option>
                                        <option value="Contractor" {{ old('onboarding_channel') === 'Contractor' ? 'selected' : '' }}>Contractor Manpower</option>
                                        <option value="Referral" {{ old('onboarding_channel') === 'Referral' ? 'selected' : '' }}>Internal Referral</option>
                                        <option value="Agency" {{ old('onboarding_channel') === 'Agency' ? 'selected' : '' }}>Staffing Agency</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4 {{ old('onboarding_channel') === 'Contractor' ? '' : 'd-none' }}" id="contractorVendorCol">
                                <label class="form-label" style="font-weight: 500;">Contractor Vendor <span class="text-danger">*</span></label>
                                <select name="contractor_id" id="contractorIdSelect" class="form-select">
                                    <option value="">Select Vendor</option>
                                    @foreach($contractors as $con)
                                        <option value="{{ $con->id }}" {{ old('contractor_id') == $con->id ? 'selected' : '' }}>{{ $con->agency_name ?? $con->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Worker Skill Classification <span class="text-danger">*</span></label>
                                <select name="skill_level" class="form-select">
                                    @if(isset($masters['skill_classification']) && count($masters['skill_classification']) > 0)
                                        @foreach($masters['skill_classification'] as $item)
                                            <option value="{{ $item->name }}" {{ old('skill_level', 'Skilled') === $item->name || old('skill_level') === $item->code ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Skilled" {{ old('skill_level', 'Skilled') === 'Skilled' ? 'selected' : '' }}>Skilled</option>
                                        <option value="Semi-Skilled" {{ old('skill_level') === 'Semi-Skilled' ? 'selected' : '' }}>Semi-Skilled</option>
                                        <option value="Unskilled" {{ old('skill_level') === 'Unskilled' ? 'selected' : '' }}>Unskilled</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Employment Type <span class="text-danger">*</span></label>
                                <select name="employment_type" id="employmentTypeSelect" class="form-select" required>
                                    @if(isset($masters['employment_type']) && count($masters['employment_type']) > 0)
                                        @foreach($masters['employment_type'] as $t)
                                            <option value="{{ $t->name }}" {{ old('employment_type', 'Permanent') === $t->name || old('employment_type') === $t->code ? 'selected' : '' }}>{{ $t->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(['Permanent', 'Intern', 'Contract', 'Probationary', 'Trainee'] as $t)
                                            <option value="{{ $t }}" {{ old('employment_type', 'Permanent') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Initial Employment Status <span class="text-danger">*</span></label>
                                <select name="initial_employment_status" class="form-select" required>
                                    @if(isset($masters['employment_status']) && count($masters['employment_status']) > 0)
                                        @foreach($masters['employment_status'] as $item)
                                            <option value="{{ $item->code }}" {{ old('initial_employment_status', 'Active') === $item->code || old('initial_employment_status') === $item->name ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(['Active' => 'Active', 'Probation' => 'On Probation', 'Intern' => 'Internship Active'] as $val => $lbl)
                                            <option value="{{ $val }}" {{ old('initial_employment_status', 'Active') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Date of Joining <span class="text-danger">*</span></label>
                                <input type="date" name="joining_date" id="joiningDateInput" class="form-control" value="{{ old('joining_date', now()->format('Y-m-d')) }}" required>
                                <small class="text-muted" style="font-size: 11px;"><i class="bi bi-shield-lock me-1"></i>Joining branch & date permanently preserved</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Probation Start Date</label>
                                <input type="date" name="probation_start_date" class="form-control" value="{{ old('probation_start_date', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Probation End Date</label>
                                <input type="date" name="probation_end_date" class="form-control" value="{{ old('probation_end_date', now()->addMonths(6)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Confirmation Due Date</label>
                                <input type="date" name="confirmation_due_date" class="form-control" value="{{ old('confirmation_due_date', now()->addMonths(6)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">Service Duration</label>
                                <input type="text" id="calcServiceMonthsInput" class="form-control bg-light" readonly placeholder="0 Months">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Reporting Manager</label>
                                <select name="reporting_manager_id" class="form-select">
                                    <option value="">No Reporting Manager</option>
                                    @foreach($managers as $mgr)
                                        <option value="{{ $mgr->id }}" {{ old('reporting_manager_id') == $mgr->id ? 'selected' : '' }}>{{ $mgr->full_name }} ({{ $mgr->employee_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Gate Pass #</label>
                                <input type="text" name="gate_pass_number" class="form-control code-font" placeholder="GP-1029" value="{{ old('gate_pass_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Payment Mode</label>
                                <select name="payment_mode" class="form-select">
                                    @if(isset($masters['payment_mode']) && count($masters['payment_mode']) > 0)
                                        @foreach($masters['payment_mode'] as $item)
                                            <option value="{{ $item->name }}" {{ old('payment_mode', 'Bank Transfer') === $item->name || old('payment_mode') === $item->code ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Bank Transfer" {{ old('payment_mode') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer (NEFT/RTGS)</option>
                                        <option value="Cheque" {{ old('payment_mode') === 'Cheque' ? 'selected' : '' }}>Company Cheque</option>
                                        <option value="Cash" {{ old('payment_mode') === 'Cash' ? 'selected' : '' }}>Cash Voucher</option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">JV Code (Credit)</label>
                                <input type="text" name="jv_code_cr" class="form-control code-font" placeholder="JV-CR-8821" value="{{ old('jv_code_cr') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Old / Legacy Employee Code</label>
                                <input type="text" name="old_employee_code" class="form-control code-font" placeholder="e.g. LEG-0042" value="{{ old('old_employee_code') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Freeze Account Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_account_frozen" value="1" id="freezeSwitch" {{ old('is_account_frozen') ? 'checked' : '' }}>
                                    <label class="form-check-label text-danger fw-semibold" for="freezeSwitch">Freeze Account (Lock Operations)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: CONTACT INFORMATION -->
                    <div class="wizard-section d-none" id="section-3">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-telephone text-primary me-2"></i>3. Contact Information</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Personal communication lines, corporate extensions, and messaging handles.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Mobile Phone <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" placeholder="+91 98000 00000" value="{{ old('phone') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Personal Alternate Phone</label>
                                <input type="tel" name="personal_phone" class="form-control" placeholder="Secondary mobile" value="{{ old('personal_phone') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Residential Landline</label>
                                <input type="tel" name="residential_phone" class="form-control" placeholder="STD-Landline" value="{{ old('residential_phone') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Official Work Phone</label>
                                <input type="tel" name="office_phone" class="form-control" placeholder="Corporate desk line" value="{{ old('office_phone') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Extension #</label>
                                <input type="text" name="extension_number" class="form-control" placeholder="e.g. 402" value="{{ old('extension_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Official Corporate Email</label>
                                <input type="email" name="email" id="candidate_email" autocomplete="off" class="form-control" placeholder="employee@{{ Str::slug($activeCompany->name) }}.com" value="{{ old('email') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Alternate / Personal Email</label>
                                <input type="email" name="alternate_email" class="form-control" placeholder="personal@gmail.com" value="{{ old('alternate_email') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">CC Email Notification Handle</label>
                                <input type="email" name="cc_email" class="form-control" placeholder="cc-alerts@enterprise.com" value="{{ old('cc_email') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Microsoft 365 / Teams ID</label>
                                <input type="text" name="microsoft_user" class="form-control" placeholder="msteams.user@company.com" value="{{ old('microsoft_user') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Google Workspace ID</label>
                                <input type="text" name="google_user" class="form-control" placeholder="google.user@company.com" value="{{ old('google_user') }}">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: ADDRESS INFORMATION -->
                    <div class="wizard-section d-none" id="section-4">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-geo-alt text-primary me-2"></i>4. Address Details (Current & Permanent)</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Residential dwellings and domicile locations.</p>
                        </div>

                        <!-- Current Address -->
                        <h6 class="text-uppercase text-primary fw-bold mb-3" style="font-size: 11.5px; letter-spacing: 0.05em;">
                            CURRENT RESIDENTIAL ADDRESS
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Plot / Flat / House #</label>
                                <input type="text" name="current_flat_plot" id="curr_flat" class="form-control" placeholder="Flat 402, Building A" value="{{ old('current_flat_plot') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Street / Lane</label>
                                <input type="text" name="current_street" id="curr_street" class="form-control" placeholder="Shivaji Road" value="{{ old('current_street') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Area / Locality</label>
                                <input type="text" name="current_area" id="curr_area" class="form-control" placeholder="Bhosari MIDC" value="{{ old('current_area') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">City</label>
                                <input type="text" name="current_city" id="curr_city" class="form-control" placeholder="Pune" value="{{ old('current_city') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">District</label>
                                <input type="text" name="current_district" id="curr_dist" class="form-control" placeholder="Pune" value="{{ old('current_district') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">State</label>
                                <input type="text" name="current_state" id="curr_state" class="form-control" placeholder="Maharashtra" value="{{ old('current_state', 'Maharashtra') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">PIN Code</label>
                                <input type="text" name="current_pincode" id="curr_pin" class="form-control code-font" placeholder="411026" value="{{ old('current_pincode') }}">
                            </div>
                        </div>

                        <!-- Permanent Address Sync Header -->
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-3">
                            <span class="text-uppercase fw-bold text-dark" style="font-size: 11.5px; letter-spacing: 0.05em;">
                                PERMANENT DOMICILE ADDRESS
                            </span>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permanent_same_as_current" value="1" id="sameAddressCheckbox" {{ old('permanent_same_as_current', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-primary" for="sameAddressCheckbox" style="font-size: 13px;">
                                    Permanent address same as current address
                                </label>
                            </div>
                        </div>

                        <!-- Permanent Address Fields -->
                        <div class="row g-3" id="permanentAddressContainer">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Plot / Flat / House #</label>
                                <input type="text" name="permanent_flat_plot" id="perm_flat" class="form-control" value="{{ old('permanent_flat_plot') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Street / Lane</label>
                                <input type="text" name="permanent_street" id="perm_street" class="form-control" value="{{ old('permanent_street') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Area / Locality</label>
                                <input type="text" name="permanent_area" id="perm_area" class="form-control" value="{{ old('permanent_area') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">City</label>
                                <input type="text" name="permanent_city" id="perm_city" class="form-control" value="{{ old('permanent_city') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">District</label>
                                <input type="text" name="permanent_district" id="perm_dist" class="form-control" value="{{ old('permanent_district') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">State</label>
                                <input type="text" name="permanent_state" id="perm_state" class="form-control" value="{{ old('permanent_state') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-weight: 500;">PIN Code</label>
                                <input type="text" name="permanent_pincode" id="perm_pin" class="form-control code-font" value="{{ old('permanent_pincode') }}">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: FAMILY DETAILS & EMERGENCY CONTACT -->
                    <div class="wizard-section d-none" id="section-5">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-people text-primary me-2"></i>5. Family Details & Emergency Contact</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Next of kin, dependents, and industrial emergency contacts.</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select Status</option>
                                    @if(isset($masters['marital_status']) && count($masters['marital_status']) > 0)
                                        @foreach($masters['marital_status'] as $item)
                                            <option value="{{ $item->name }}" {{ old('marital_status') === $item->name || old('marital_status') === $item->code ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Single" {{ old('marital_status') === 'Single' ? 'selected' : '' }}>Single</option>
                                        <option value="Married" {{ old('marital_status') === 'Married' ? 'selected' : '' }}>Married</option>
                                        <option value="Divorced" {{ old('marital_status') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                        <option value="Widowed" {{ old('marital_status') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Anniversary Date</label>
                                <input type="date" name="marriage_anniversary_date" class="form-control" value="{{ old('marriage_anniversary_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Spouse Name</label>
                                <input type="text" name="spouse_name" class="form-control" placeholder="If applicable" value="{{ old('spouse_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Father's Full Name</label>
                                <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Mother's Full Name</label>
                                <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-weight: 500;">Children Count</label>
                                <input type="number" name="number_of_children" class="form-control" min="0" value="{{ old('number_of_children', 0) }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-weight: 500;">In Hostel</label>
                                <input type="number" name="children_in_hostel" class="form-control" min="0" value="{{ old('children_in_hostel', 0) }}">
                            </div>
                        </div>

                        <!-- Emergency Contact Banner -->
                        <div class="p-3 bg-danger-subtle rounded border border-danger-subtle mb-3">
                            <h6 class="text-danger fw-bold mb-1" style="font-size: 12px; letter-spacing: 0.05em;">
                                <i class="bi bi-shield-exclamation me-1"></i> MANDATORY INDUSTRIAL EMERGENCY CONTACT
                            </h6>
                            <p class="text-muted mb-0" style="font-size: 12px;">Contacted immediately in case of shop floor injury, medical triage or plant incident.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="form-control" placeholder="Full legal name" value="{{ old('emergency_contact_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Emergency Contact Relation</label>
                                <select name="emergency_contact_relation" class="form-select">
                                    <option value="">Select Relation</option>
                                    @if(isset($masters['relationship_type']) && count($masters['relationship_type']) > 0)
                                        @foreach($masters['relationship_type'] as $rel)
                                            <option value="{{ $rel->name }}" {{ old('emergency_contact_relation') === $rel->name ? 'selected' : '' }}>{{ $rel->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(['Spouse', 'Father', 'Mother', 'Brother', 'Sister', 'Son', 'Daughter', 'Friend', 'Guardian', 'Other'] as $rel)
                                            <option value="{{ $rel }}" {{ old('emergency_contact_relation') === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Emergency Phone #</label>
                                <input type="tel" name="emergency_contact_phone" class="form-control" placeholder="+91 98000 00000" value="{{ old('emergency_contact_phone') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" style="font-weight: 500;">Emergency Physical Address</label>
                                <textarea name="emergency_contact_address" class="form-control" rows="2" placeholder="Full residential location of emergency contact">{{ old('emergency_contact_address') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 6: PERSONAL DETAILS -->
                    <div class="wizard-section d-none" id="section-6">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-person-badge text-primary me-2"></i>6. Personal & Socio-Demographic Details</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Optional socio-demographic indicators and physical measurements.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Religion</label>
                                <input type="text" name="religion" list="religionsList" class="form-control" placeholder="Optional" value="{{ old('religion') }}">
                                <datalist id="religionsList">
                                    @foreach($masters['religion'] ?? [] as $r)
                                        <option value="{{ $r->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Caste Category</label>
                                <input type="text" name="caste" list="castesList" class="form-control" placeholder="General, OBC, SC, ST..." value="{{ old('caste') }}">
                                <datalist id="castesList">
                                    @foreach($masters['caste_category'] ?? [] as $c)
                                        <option value="{{ $c->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Sub-Caste</label>
                                <input type="text" name="sub_caste" class="form-control" placeholder="Optional" value="{{ old('sub_caste') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" class="form-control" placeholder="e.g. 172.5" value="{{ old('height_cm') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" class="form-control" placeholder="e.g. 68.0" value="{{ old('weight_kg') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Latest Health Checkup Date</label>
                                <input type="date" name="health_checkup_date" class="form-control" value="{{ old('health_checkup_date') }}">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 7: STATUTORY & COMPLIANCE -->
                    <div class="wizard-section d-none" id="section-7">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-shield-check text-primary me-2"></i>7. Statutory & Compliance Details</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Government tokens, EPFO Provident Fund, ESIC social insurance, and Professional Tax.</p>
                        </div>

                        <!-- Core UIDAI & Income Tax -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Aadhaar Number (12 Digits)</label>
                                <input type="text" name="aadhaar_number" maxlength="14" class="form-control code-font" placeholder="1234 5678 9012" value="{{ old('aadhaar_number') }}" style="-webkit-text-security: disc;">
                                <div class="form-text" style="font-size: 11.5px; color: #64748B;">
                                    <i class="bi bi-shield-lock me-1"></i> Stored strictly as SHA-256 hash + masked token (XXXX-XXXX-1234).
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Permanent Account Number (PAN)</label>
                                <input type="text" name="pan_number" maxlength="10" class="form-control code-font" placeholder="ABCDE1234F" value="{{ old('pan_number') }}" style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Universal Account Number (UAN)</label>
                                <input type="text" name="uan_number" maxlength="12" class="form-control code-font" placeholder="101298475891" value="{{ old('uan_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">PRAN (NPS Retirement ID)</label>
                                <input type="text" name="pran" class="form-control code-font" placeholder="PRAN Token" value="{{ old('pran') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Gratuity Scheme Code</label>
                                <input type="text" name="gratuity_code" class="form-control code-font" placeholder="GRAT-LIC-01" value="{{ old('gratuity_code') }}">
                            </div>
                        </div>

                        <!-- Provident Fund Accordion Card -->
                        <div class="card bg-light border mb-4">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-wallet2 text-primary me-1"></i> Employees' Provident Fund (EPFO)</h6>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="has_pf" value="1" id="pfSwitch" {{ old('has_pf', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="pfSwitch">PF Applicable</label>
                                    </div>
                                </div>

                                <div class="row g-3" id="pfFieldsContainer">
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 500;">PF Join Date</label>
                                        <input type="date" name="pf_join_date" class="form-control" value="{{ old('pf_join_date') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 500;">PF Member Account #</label>
                                        <input type="text" name="pf_number" class="form-control code-font" placeholder="MH/BAN/0012345/000/1234" value="{{ old('pf_number') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" style="font-weight: 500;">Voluntary PF (VPF) %</label>
                                        <input type="number" step="0.01" name="vpf_percentage" class="form-control" placeholder="0.00" value="{{ old('vpf_percentage') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-weight: 500;">Previous PF Member ID</label>
                                        <input type="text" name="pf_previous_member_id" class="form-control code-font" placeholder="Old company member token" value="{{ old('pf_previous_member_id') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" style="font-weight: 500;">PF Nationality / Country</label>
                                        <input type="text" name="pf_country" class="form-control" value="{{ old('pf_country', 'India') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ESIC & PT Accordion Card -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-heart-pulse text-danger me-1"></i> ESIC Insurance</h6>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="has_esic" value="1" id="esicSwitch" {{ old('has_esic', '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="esicSwitch">ESIC Applicable</label>
                                            </div>
                                        </div>
                                        <div class="row g-2" id="esicFieldsContainer">
                                            <div class="col-12">
                                                <label class="form-label" style="font-weight: 500;">ESIC IP Number (17 Digits)</label>
                                                <input type="text" name="esic_number" class="form-control code-font" placeholder="31000123450000001" value="{{ old('esic_number') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight: 500;">ESIC Dispensary Code</label>
                                                <input type="text" name="esic_imp_code" class="form-control code-font" placeholder="DISP-411" value="{{ old('esic_imp_code') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight: 500;">Dispensary Name</label>
                                                <input type="text" name="esic_imp_name" class="form-control" placeholder="Bhosari ESI Clinic" value="{{ old('esic_imp_name') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cash-stack text-success me-1"></i> Professional Tax (PT)</h6>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="has_pt" value="1" id="ptSwitch" {{ old('has_pt', '1') == '1' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="ptSwitch">PT Applicable</label>
                                            </div>
                                        </div>
                                        <div class="row g-2" id="ptFieldsContainer">
                                            <div class="col-12">
                                                <label class="form-label" style="font-weight: 500;">State PT Registration #</label>
                                                <input type="text" name="pt_number" class="form-control code-font" placeholder="MH-PTR-881290" value="{{ old('pt_number') }}">
                                            </div>
                                            <div class="col-12">
                                                <div class="form-text" style="font-size: 11.5px;">
                                                    State slabs are calculated dynamically based on active site location ({{ $activeCompany->name }}).
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 8: BANKING & IDENTITY -->
                    <div class="wizard-section d-none" id="section-8">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-bank text-primary me-2"></i>8. Banking & Salary Disbursement</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Corporate bank account details protected under Page 94 RBAC compensation rules.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Name as per Bank Passbook</label>
                                <input type="text" name="bank_passbook_name" class="form-control" placeholder="Exact passbook name" value="{{ old('bank_passbook_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Name as per PAN Card</label>
                                <input type="text" name="pan_card_name" class="form-control" placeholder="Exact PAN name" value="{{ old('pan_card_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Name as per Aadhaar</label>
                                <input type="text" name="aadhaar_card_name" class="form-control" placeholder="Exact Aadhaar card name" value="{{ old('aadhaar_card_name') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Bank Name</label>
                                <input type="text" name="bank_name" list="bankNamesList" class="form-control" placeholder="HDFC Bank, SBI, ICICI..." value="{{ old('bank_name') }}">
                                <datalist id="bankNamesList">
                                    @foreach($masters['bank_name'] ?? [] as $bn)
                                        <option value="{{ $bn->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Bank Branch</label>
                                <input type="text" name="bank_branch" class="form-control" placeholder="Bhosari MIDC Branch" value="{{ old('bank_branch') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">IFSC Code (11 Digits)</label>
                                <input type="text" name="bank_ifsc" maxlength="11" class="form-control code-font" placeholder="HDFC0001234" value="{{ old('bank_ifsc') }}" style="text-transform: uppercase;">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Account Number</label>
                                <input type="password" name="bank_account_number" class="form-control code-font" placeholder="•••• •••• ••••" value="{{ old('bank_account_number') }}" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Account Type</label>
                                <select name="bank_account_type" class="form-select">
                                    @if(isset($masters['bank_account_type']) && count($masters['bank_account_type']) > 0)
                                        @foreach($masters['bank_account_type'] as $item)
                                            <option value="{{ $item->name }}" {{ old('bank_account_type', 'Salary') === $item->name || old('bank_account_type') === $item->code ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Salary" {{ old('bank_account_type', 'Salary') === 'Salary' ? 'selected' : '' }}>Corporate Salary Account</option>
                                        <option value="Savings" {{ old('bank_account_type') === 'Savings' ? 'selected' : '' }}>Individual Savings</option>
                                        <option value="Current" {{ old('bank_account_type') === 'Current' ? 'selected' : '' }}>Current Business Account</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 9: EDUCATION -->
                    <div class="wizard-section d-none" id="section-9">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-mortarboard text-primary me-2"></i>9. Education & Qualifications</h5>
                                <p class="text-muted mb-0" style="font-size: 13px;">Academic credentials, degrees, and certified marksheets.</p>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 d-flex align-items-center gap-2 mb-3" style="font-size: 13px;">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>You can record primary qualifications now and add repeatable qualification tiers in the employee dossier anytime.</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Highest Qualification Level</label>
                                <select name="statutory_education_qualification" id="qualificationSelect" class="form-select">
                                    <option value="">Select Level</option>
                                    @if(isset($masters['qualification_level']) && count($masters['qualification_level']) > 0)
                                        @foreach($masters['qualification_level'] as $item)
                                            <option value="{{ $item->name }}" data-id="{{ $item->id }}" {{ old('statutory_education_qualification') === $item->name ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Bachelor's Degree">Bachelor's Degree (B.E./B.Tech/B.Sc/B.Com)</option>
                                        <option value="Master's Degree">Master's Degree (M.Tech/MBA/M.Sc)</option>
                                        <option value="Diploma">Polytechnic Diploma</option>
                                        <option value="ITI / Vocational">ITI / Vocational Certificate</option>
                                        <option value="12th / HSC">Higher Secondary (12th / HSC)</option>
                                        <option value="10th / SSC">Secondary School (10th / SSC)</option>
                                        <option value="Doctorate">Doctorate / Ph.D.</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Degree / Qualification Name</label>
                                <input type="text" name="degree_name" list="degreeNamesList" class="form-control" placeholder="e.g. B.E. Mechanical Engineering">
                                <datalist id="degreeNamesList">
                                    @foreach($masters['degree_name'] ?? [] as $deg)
                                        <option value="{{ $deg->name }}" data-parent="{{ $deg->parent_id }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Board / University</label>
                                <input type="text" name="board_university" list="boardUniversitiesList" class="form-control" placeholder="e.g. Savitribai Phule Pune University">
                                <datalist id="boardUniversitiesList">
                                    @foreach($masters['board_university'] ?? [] as $bu)
                                        <option value="{{ $bu->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 10: SKILL MATRIX -->
                    <div class="wizard-section d-none" id="section-10">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-stars text-primary me-2"></i>10. Skills & Competency Matrix</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Proficiency levels, industrial certifications, and competency scores.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Primary Technical Skill</label>
                                <input type="text" name="skill_name" list="skillNamesList" class="form-control" placeholder="e.g. CNC Machine Operation, TIG Welding">
                                <datalist id="skillNamesList">
                                    @foreach($masters['skill_name'] ?? [] as $sk)
                                        <option value="{{ $sk->name }}" data-parent="{{ $sk->parent_id }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Competency Category</label>
                                <select name="skill_category" id="skillCategorySelect" class="form-select">
                                    <option value="">Select Category</option>
                                    @if(isset($masters['skill_category']) && count($masters['skill_category']) > 0)
                                        @foreach($masters['skill_category'] as $item)
                                            <option value="{{ $item->name }}" data-id="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Technical">Technical Operations</option>
                                        <option value="Operational">Plant Machinery</option>
                                        <option value="Safety">Industrial Safety & PPE</option>
                                        <option value="Quality Control">Quality Assurance & Testing</option>
                                        <option value="Leadership">Supervisory & Leadership</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Proficiency Tier</label>
                                <select name="skill_proficiency" class="form-select">
                                    @if(isset($masters['skill_proficiency']) && count($masters['skill_proficiency']) > 0)
                                        @foreach($masters['skill_proficiency'] as $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="Intermediate">Intermediate (Autonomous Worker)</option>
                                        <option value="Beginner">Beginner (Under Supervision)</option>
                                        <option value="Advanced">Advanced (Senior Technician)</option>
                                        <option value="Expert">Expert (Master Craftsman / Trainer)</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 11: VISA & IMMIGRATION -->
                    <div class="wizard-section d-none" id="section-11">
                        <div class="pb-3 mb-4 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-globe text-primary me-2"></i>11. Visa & Immigration Details</h5>
                                    <p class="text-muted mb-0" style="font-size: 13px;">Passport identification and international work permits.</p>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="visa_required" value="1" id="visaSwitch" {{ old('visa_required') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="visaSwitch">Visa Required</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Passport Number</label>
                                <input type="text" name="passport_number" class="form-control code-font" placeholder="Z1234567" value="{{ old('passport_number') }}" style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Passport Issue Date</label>
                                <input type="date" name="passport_issue_date" class="form-control" value="{{ old('passport_issue_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Passport Expiry Date</label>
                                <input type="date" name="passport_expiry_date" class="form-control" value="{{ old('passport_expiry_date') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Work Permit #</label>
                                <input type="text" name="work_permit_number" class="form-control code-font" placeholder="WP-9921" value="{{ old('work_permit_number') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Permit Expiry Date</label>
                                <input type="date" name="work_permit_expiry_date" class="form-control" value="{{ old('work_permit_expiry_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" style="font-weight: 500;">Country of Employment</label>
                                <input type="text" name="country_of_employment" list="countriesList" class="form-control" value="{{ old('country_of_employment', 'India') }}">
                                <datalist id="countriesList">
                                    @foreach($masters['country'] ?? [] as $cntry)
                                        <option value="{{ $cntry->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 12: DOCUMENTS & CERTIFICATES -->
                    <div class="wizard-section d-none" id="section-12">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>12. Document Attachments</h5>
                                <p class="text-muted mb-0" style="font-size: 13px;">Secure private storage for mandatory identity proofs, educational degrees, and statutory forms.</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                <i class="bi bi-shield-check me-1"></i> Encrypted Vault
                            </span>
                        </div>

                        <!-- Security Info Banner -->
                        <div class="alert alert-light border d-flex align-items-center gap-3 mb-4 p-3 rounded-3" style="font-size: 13px; background: #f8fafc;">
                            <i class="bi bi-shield-lock-fill text-success fs-4"></i>
                            <div>
                                <span class="fw-semibold text-dark">Tenant-Isolated Private Document Vault:</span>
                                Attached files are vaulted under company-isolated private storage. Access is strictly RBAC-guarded with forensic audit logging.
                                <span class="badge bg-secondary-subtle text-dark ms-1">Max 5MB per file (PDF, JPG, PNG)</span>
                            </div>
                        </div>

                        <!-- Staging Upload Card -->
                        <div class="card border rounded-3 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-semibold" style="font-size: 14px; color: #1e293b;"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Attach a New Document</span>
                                <small class="text-muted">Attach identity proofs, certificates, or letters before submitting the dossier</small>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Document Category / Type <span class="text-danger">*</span></label>
                                        <select id="docStagingType" class="form-select form-select-sm">
                                            <option value="">-- Select Document Category --</option>
                                            @if(isset($masters['document_type']) && count($masters['document_type']) > 0)
                                                @php
                                                    $docGroups = $masters['document_type']->groupBy(function($item) {
                                                        return $item->metadata['group'] ?? 'General Documents';
                                                    });
                                                @endphp
                                                @foreach($docGroups as $groupLabel => $docs)
                                                    <optgroup label="{{ $groupLabel }}">
                                                        @foreach($docs as $doc)
                                                            <option value="{{ $doc->name }}" data-category="{{ $doc->metadata['category'] ?? 'Identity' }}">{{ $doc->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            @else
                                                <optgroup label="Identity & National Proofs">
                                                    <option value="Aadhaar Card" data-category="Identity">Aadhaar Card</option>
                                                    <option value="PAN Card" data-category="Identity">PAN Card</option>
                                                    <option value="Passport" data-category="Visa & Immigration">Passport</option>
                                                    <option value="Voter ID Card" data-category="Identity">Voter ID Card</option>
                                                    <option value="Driving Licence" data-category="Identity">Driving Licence</option>
                                                    <option value="Address Proof" data-category="Identity">Address Proof (Utility / Rent)</option>
                                                    <option value="Profile Photo" data-category="Identity">Profile Photo (High Res)</option>
                                                </optgroup>
                                                <optgroup label="Educational Credentials">
                                                    <option value="Educational Degree / Certificate" data-category="Educational">Educational Degree / Certificate</option>
                                                    <option value="Secondary School Certificate (10th/12th)" data-category="Educational">Secondary School Certificate (10th/12th)</option>
                                                </optgroup>
                                                <optgroup label="Employment & Experience">
                                                    <option value="Resume / CV" data-category="Employment">Resume / CV</option>
                                                    <option value="Experience / Relieving Letter" data-category="Employment">Experience / Relieving Letter</option>
                                                    <option value="Appointment Letter" data-category="Employment">Appointment Letter</option>
                                                    <option value="Offer Letter" data-category="Employment">Offer Letter</option>
                                                </optgroup>
                                                <optgroup label="Financial & Banking">
                                                    <option value="Bank Passbook / Cancelled Cheque" data-category="Banking">Bank Passbook / Cancelled Cheque</option>
                                                </optgroup>
                                                <optgroup label="Immigration & Medical">
                                                    <option value="Visa / Work Permit" data-category="Visa & Immigration">Visa / Work Permit</option>
                                                    <option value="Medical Fitness Certificate" data-category="Health">Medical Fitness Certificate</option>
                                                </optgroup>
                                                <optgroup label="Other">
                                                    <option value="Other Document" data-category="Other">Other Document</option>
                                                </optgroup>
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Document Title / Description</label>
                                        <input type="text" id="docStagingTitle" class="form-control form-control-sm" placeholder="e.g. B.Tech Degree, Aadhaar Front & Back">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Expiry Date <small class="text-muted fw-normal">(If applicable)</small></label>
                                        <input type="date" id="docStagingExpiry" class="form-control form-control-sm">
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Select File <span class="text-danger">*</span> <small class="text-muted fw-normal">(PDF, JPG, PNG up to 5MB)</small></label>
                                        <div class="input-group input-group-sm">
                                            <input type="file" id="docStagingFileInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                            <button type="button" class="btn btn-outline-secondary" id="docStagingClearFileBtn" title="Clear selection"><i class="bi bi-x"></i></button>
                                        </div>
                                        <div id="docFileValidationError" class="text-danger mt-1 small d-none"></div>
                                    </div>

                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="button" id="docStagingAddBtn" class="btn btn-primary btn-sm w-100 fw-semibold py-2">
                                            <i class="bi bi-plus-circle me-1"></i> Attach to Dossier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Staged Files Manifest Table -->
                        <div class="card border rounded-3 mb-3">
                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <span class="fw-semibold text-secondary" style="font-size: 13px;">
                                    <i class="bi bi-paperclip me-1"></i> Attached Documents Manifest
                                    <span class="badge bg-primary ms-1" id="stagedDocsCountBadge">0</span>
                                </span>
                                <small class="text-muted">Files will be securely stored upon saving draft or completing profile</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;" class="text-center">#</th>
                                            <th>Document Type / Title</th>
                                            <th>Category</th>
                                            <th>File Name & Size</th>
                                            <th>Expiry Date</th>
                                            <th class="text-center" style="width: 140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stagedDocsTableBody">
                                        <tr id="noDocsPlaceholderRow">
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="bi bi-folder2-open display-6 d-block mb-2 text-secondary opacity-50"></i>
                                                <div class="fw-semibold">No documents attached yet</div>
                                                <div class="small">Select a category and file above, then click <strong>Attach to Dossier</strong>.</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Hidden Container for staged form file inputs -->
                        <div id="stagedDocsInputsContainer"></div>
                    </div>

                    <!-- SECTION 13: ASSETS & IT INFORMATION -->
                    <div class="wizard-section d-none" id="section-13">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-laptop text-primary me-2"></i>13. Assets & IT Equipment Custody</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Corporate assets, laptops, SIM cards and security badges.</p>
                        </div>

                        <div class="alert alert-secondary py-3 px-3 d-flex align-items-center gap-2" style="font-size: 13px;">
                            <i class="bi bi-info-circle-fill fs-5"></i>
                            <div>Asset allocations (laptops, access cards, tools, safety gear) are managed via the <strong>Asset Allocation Master</strong> once the employee dossier is initialized.</div>
                        </div>
                    </div>

                    <!-- SECTION 14: LOGIN & ACCOUNT ACCESS -->
                    <div class="wizard-section d-none" id="section-14">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-key text-primary me-2"></i>14. Login & Self-Service Account Provisioning</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Provision employee ESS access credentials securely. Plaintext passwords are never stored.</p>
                        </div>

                        <div class="card bg-light border p-3">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="provision_account" value="1" id="provisionSwitch" checked>
                                <label class="form-check-label fw-bold" for="provisionSwitch">Enable Employee Self-Service (ESS) Account</label>
                            </div>
                            <div class="text-muted" style="font-size: 13px;">
                                An ESS login account will be linked to the employee's official email or code. The employee can sign in with the standard password or receive an invitation to set their own password.
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 15: USER DEFINED FIELDS (UDF) -->
                    <div class="wizard-section d-none" id="section-15">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-sliders text-primary me-2"></i>15. User Defined Fields (UDF)</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Custom enterprise parameters configured for this tenant.</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Prior Total Experience (Years)</label>
                                <input type="number" step="0.5" name="custom_fields[prior_experience_years]" class="form-control" placeholder="e.g. 4.5" value="{{ old('custom_fields.prior_experience_years', '0') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Preferred Uniform / T-Shirt Size</label>
                                <select name="custom_fields[uniform_size]" class="form-select">
                                    <option value="">Select Size</option>
                                    @if(isset($masters['uniform_size']) && count($masters['uniform_size']) > 0)
                                        @foreach($masters['uniform_size'] as $item)
                                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                                        @endforeach
                                    @else
                                        @foreach(['S', 'M', 'L', 'XL', 'XXL', '3XL'] as $size)
                                            <option value="{{ $size }}">{{ $size }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Safety Shoes Size (UK/India)</label>
                                <input type="text" name="custom_fields[safety_shoes_size]" class="form-control" placeholder="e.g. 8, 9, 10">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 500;">Parking Space Required</label>
                                <select name="custom_fields[parking_required]" class="form-select">
                                    <option value="No">No</option>
                                    <option value="Two Wheeler">Two Wheeler</option>
                                    <option value="Four Wheeler">Four Wheeler</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 16: REVIEW & SUBMISSION -->
                    <div class="wizard-section d-none" id="section-16">
                        <div class="pb-3 mb-4 border-bottom">
                            <h5 class="mb-1" style="font-weight: 700; color: #0F172A;"><i class="bi bi-check2-circle text-success me-2"></i>16. Review & Finalize Dossier</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Verify all required fields before committing the workforce dossier to the live register.</p>
                        </div>

                        <div class="card bg-light border mb-4">
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold mb-1 text-dark">Pre-Commit Dossier Summary</h6>
                                        <div class="text-muted" style="font-size: 13px;">
                                            All 16 sections are structured according to the Kanvtech Enterprise HRMS specification.
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                        <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 border border-success-subtle">
                                            <i class="bi bi-shield-check me-1"></i> Validated & Ready
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning py-3 px-3 d-flex align-items-center gap-2 mb-3" style="font-size: 13px;">
                            <i class="bi bi-shield-exclamation fs-5"></i>
                            <div>
                                Clicking <strong>Finalize & Submit Dossier</strong> will run cross-company blacklist matching, check subscription headcount limits, and register the personnel on the live active muster.
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Controls -->
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 pt-4 mt-4 border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 d-none" id="prevStepBtn">
                            <i class="bi bi-chevron-left me-1"></i> Previous Step
                        </button>
                        <div class="d-flex gap-2 ms-auto">
                            <button type="submit" name="action" value="draft" class="btn btn-outline-primary btn-sm px-3" id="saveDraftBtn" formnovalidate>
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>
                            <button type="button" class="btn btn-brand btn-sm px-4" id="nextStepBtn">
                                Continue <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                            <button type="submit" name="action" value="finalize" class="btn btn-success btn-sm px-4 d-none" id="finalizeSubmitBtn">
                                <i class="bi bi-check-circle-fill me-1"></i> Finalize & Submit Dossier
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>

<!-- Document Preview Modal -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold" id="docPreviewModalLabel"><i class="bi bi-eye text-primary me-2"></i>Document Preview</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center" style="min-height: 380px; max-height: 75vh; overflow: auto; background: #f8fafc;">
                <img id="docModalImagePreview" src="" alt="Preview" class="img-fluid d-none" style="max-height: 70vh; margin: auto;">
                <iframe id="docModalFramePreview" src="" class="d-none w-100" style="height: 70vh; border: none;"></iframe>
                <div id="docModalUnsupportedPreview" class="p-5 d-none text-muted">
                    <i class="bi bi-file-earmark-text display-4 d-block mb-2 text-secondary"></i>
                    <p>Live inline preview is not supported for this file format in the modal.</p>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 border-top">
                <span class="small text-muted me-auto" id="docModalMetaInfo"></span>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Concurrency Conflict Warning Modal -->
<div class="modal fade" id="concurrencyConflictModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark py-2.5 px-3 border-bottom">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Concurrent Edit Conflict Detected
                </h6>
            </div>
            <div class="modal-body p-4">
                <p class="mb-2">This candidate onboarding dossier was updated in another browser tab or session at <strong id="conflictTimeDisplay">—</strong>.</p>
                <div class="p-3 bg-light rounded-3 border">
                    <div class="small text-muted">To avoid overwriting newer changes, automatic saving has paused for this tab.</div>
                </div>
                <p class="small text-muted mt-3 mb-0">You can reload the latest draft or keep editing your local changes.</p>
            </div>
            <div class="modal-footer py-2 px-3 border-top bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Keep Local Changes</button>
                <button type="button" class="btn btn-sm btn-primary px-3" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reload Latest Version
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentStep = {{ (int)($draft?->current_step ?? 1) }};
    const totalSteps = 16;
    let autoSaveTimer = null;
    let isSaving = false;
    let pendingSave = false;
    let hasUnsavedChanges = false;

    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const finalizeBtn = document.getElementById('finalizeSubmitBtn');
    const stepBadge = document.getElementById('stepCounterBadge');
    const progressBar = document.getElementById('wizardProgressBar');
    const stepNavButtons = document.querySelectorAll('.wizard-step-btn');
    const wizardForm = document.getElementById('onboardingWizardForm');
    const manualSaveDraftBtn = document.getElementById('manualSaveDraftBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');

    const draftIdInput = document.getElementById('draftIdInput');
    const draftVersionInput = document.getElementById('draftVersionInput');
    const currentStepInput = document.getElementById('currentStepInput');
    const statusIndicator = document.getElementById('autoSaveStatusIndicator');
    const activeDraftIdDisplay = document.getElementById('activeDraftIdDisplay');
    const draftVersionDisplay = document.getElementById('draftVersionDisplay');
    const draftIdBadgeContainer = document.getElementById('draftIdBadgeContainer');
    const completionBadge = document.getElementById('completionPercentageBadge');

    const conflictModalEl = document.getElementById('concurrencyConflictModal');
    const conflictTimeDisplay = document.getElementById('conflictTimeDisplay');
    const conflictModal = conflictModalEl && window.bootstrap ? new bootstrap.Modal(conflictModalEl) : null;

    // ── AUTO-SAVE STATUS INDICATOR ──
    function setSaveState(state, info = {}) {
        if (!statusIndicator) return;
        switch (state) {
            case 'saving':
                statusIndicator.className = 'd-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border bg-light shadow-xs';
                statusIndicator.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status" style="width: 12px; height: 12px; color: #0D9488;"></span><span style="color:#0D9488; font-weight:600;">Saving...</span>';
                break;
            case 'saved':
                const timeStr = info.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                statusIndicator.className = 'd-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border bg-success-subtle text-success border-success-subtle shadow-xs';
                statusIndicator.innerHTML = `<i class="bi bi-cloud-check-fill text-success"></i><span>Saved at ${timeStr}</span>`;
                break;
            case 'unsaved':
                statusIndicator.className = 'd-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border bg-warning-subtle text-warning-emphasis border-warning-subtle shadow-xs';
                statusIndicator.innerHTML = '<i class="bi bi-circle-fill text-warning me-1.5" style="font-size: 8px;"></i><span>Unsaved changes</span>';
                break;
            case 'failed':
                statusIndicator.className = 'd-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border bg-danger-subtle text-danger border-danger-subtle shadow-xs';
                statusIndicator.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i><span>Save failed — </span><a href="javascript:void(0)" id="retrySaveTrigger" class="fw-bold text-danger text-decoration-underline ms-1">Retry</a>';
                const retryBtn = document.getElementById('retrySaveTrigger');
                if (retryBtn) {
                    retryBtn.onclick = function(e) {
                        e.preventDefault();
                        performAutoSave(true);
                    };
                }
                break;
            default:
                statusIndicator.className = 'd-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border bg-light text-secondary shadow-xs';
                statusIndicator.innerHTML = '<i class="bi bi-circle-fill text-secondary opacity-50 me-1" style="font-size: 8px;"></i><span>Ready (Changes auto-save)</span>';
        }
    }

    // ── AUTO-SAVE PERFORM FUNCTION ──
    async function performAutoSave(isExplicit = false) {
        if (isSaving) {
            pendingSave = true;
            return;
        }

        if (autoSaveTimer) clearTimeout(autoSaveTimer);
        isSaving = true;
        setSaveState('saving');

        if (currentStepInput) currentStepInput.value = currentStep;

        const formData = new FormData(wizardForm);
        formData.set('current_step', currentStep);
        formData.set('action', 'draft');

        try {
            const response = await fetch("{{ route('employees.drafts.auto-save') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            if (response.status === 409) {
                const conflict = await response.json();
                setSaveState('failed');
                if (conflictTimeDisplay) conflictTimeDisplay.textContent = conflict.last_saved_at || 'Recently';
                if (conflictModal) conflictModal.show();
                isSaving = false;
                return;
            }

            if (!response.ok) {
                throw new Error(`Server returned HTTP ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                hasUnsavedChanges = false;
                if (data.draft_id && draftIdInput) {
                    draftIdInput.value = data.draft_id;
                    if (activeDraftIdDisplay) activeDraftIdDisplay.textContent = data.draft_id;
                    if (draftIdBadgeContainer) draftIdBadgeContainer.classList.remove('d-none');

                    const url = new URL(window.location.href);
                    if (url.searchParams.get('draft_id') !== data.draft_id) {
                        url.searchParams.set('draft_id', data.draft_id);
                        window.history.replaceState({}, '', url.toString());
                    }
                }

                if (data.version && draftVersionInput) {
                    draftVersionInput.value = data.version;
                    if (draftVersionDisplay) draftVersionDisplay.textContent = 'v' + data.version;
                }

                if (data.completion_percentage !== undefined && completionBadge) {
                    completionBadge.textContent = data.completion_percentage + '%';
                }

                setSaveState('saved', { time: data.last_saved_at });
            } else {
                setSaveState('failed');
            }
        } catch (err) {
            console.error('Auto-save error:', err);
            setSaveState('failed');
        } finally {
            isSaving = false;
            if (pendingSave) {
                pendingSave = false;
                performAutoSave();
            }
        }
    }

    function scheduleAutoSave(delay = 1000) {
        hasUnsavedChanges = true;
        setSaveState('unsaved');
        if (autoSaveTimer) clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            performAutoSave();
        }, delay);
    }

    // Attach listeners across all form controls to debounce auto-save
    wizardForm.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            const fb = this.closest('.col-md-3, .col-md-4, .col-md-6, .col-12')?.querySelector('.invalid-feedback');
            if (fb) fb.classList.add('d-none');
            scheduleAutoSave(1000);
        });

        el.addEventListener('change', function () {
            this.classList.remove('is-invalid');
            const fb = this.closest('.col-md-3, .col-md-4, .col-md-6, .col-12')?.querySelector('.invalid-feedback');
            if (fb) fb.classList.add('d-none');
            scheduleAutoSave(400);
        });
    });

    if (manualSaveDraftBtn) {
        manualSaveDraftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performAutoSave(true);
        });
    }

    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performAutoSave(true);
        });
    }

    // Safe navigation away warning & beacon save
    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            const formData = new FormData(wizardForm);
            formData.set('current_step', currentStep);
            formData.set('action', 'draft');
            if (navigator.sendBeacon) {
                navigator.sendBeacon("{{ route('employees.drafts.auto-save') }}", formData);
            }
            e.preventDefault();
            e.returnValue = 'You have unsaved changes in your employee onboarding dossier.';
        }
    });

    // DOB & Age exact calculation definition (hoisted for showStep)
    const dobInput = document.getElementById('dobInput');
    const ageInput = document.getElementById('calculatedAgeInput');

    function calculateExactAge(dobString) {
        if (!dobString || typeof dobString !== 'string') return null;
        const clean = dobString.trim();
        if (!clean) return null;

        let birthYear = NaN, birthMonth = NaN, birthDay = NaN;

        const sep = clean.includes('-') ? '-' : (clean.includes('/') ? '/' : (clean.includes('.') ? '.' : null));
        if (sep) {
            const parts = clean.split(sep);
            if (parts.length === 3) {
                if (parts[0].length === 4) {
                    birthYear = parseInt(parts[0], 10);
                    birthMonth = parseInt(parts[1], 10) - 1;
                    birthDay = parseInt(parts[2], 10);
                } else if (parts[2].length === 4) {
                    birthYear = parseInt(parts[2], 10);
                    birthMonth = parseInt(parts[1], 10) - 1;
                    birthDay = parseInt(parts[0], 10);
                }
            }
        }

        if (isNaN(birthYear) || isNaN(birthMonth) || isNaN(birthDay)) {
            const parsed = new Date(clean);
            if (!isNaN(parsed.getTime())) {
                birthYear = parsed.getFullYear();
                birthMonth = parsed.getMonth();
                birthDay = parsed.getDate();
            }
        }

        if (isNaN(birthYear) || isNaN(birthMonth) || isNaN(birthDay)) return null;
        if (birthYear < 1900 || birthMonth < 0 || birthMonth > 11 || birthDay < 1 || birthDay > 31) return null;

        const today = new Date();
        const todayYear = today.getFullYear();
        const todayMonth = today.getMonth();
        const todayDay = today.getDate();

        if (birthYear > todayYear || 
           (birthYear === todayYear && birthMonth > todayMonth) || 
           (birthYear === todayYear && birthMonth === todayMonth && birthDay > todayDay)) {
            return { age: null, text: 'Invalid (Future Date)', isUnderage: false, isInvalid: true };
        }

        let age = todayYear - birthYear;
        if (todayMonth < birthMonth || (todayMonth === birthMonth && todayDay < birthDay)) {
            age--;
        }

        if (age < 0) {
            return { age: null, text: 'Invalid (Future Date)', isUnderage: false, isInvalid: true };
        }

        if (age < 18) {
            return { age: age, text: `${age} Years (Minor < 18)`, isUnderage: true, isInvalid: false };
        }

        return { age: age, text: age === 1 ? '1 Year' : `${age} Years`, isUnderage: false, isInvalid: false };
    }

    function updateCalculatedAge() {
        if (!dobInput || !ageInput) return;
        const val = dobInput.value ? dobInput.value.trim() : '';
        const warningEl = document.getElementById('dobUnderageWarning');

        if (!val) {
            ageInput.value = '';
            ageInput.classList.remove('text-danger', 'is-invalid', 'border-danger', 'border-warning');
            if (warningEl) warningEl.classList.add('d-none');
            return;
        }

        const res = calculateExactAge(val);
        if (!res) {
            ageInput.value = '';
            ageInput.classList.remove('text-danger', 'is-invalid', 'border-danger', 'border-warning');
            if (warningEl) warningEl.classList.add('d-none');
            return;
        }

        ageInput.value = res.text;

        if (res.isInvalid) {
            ageInput.classList.add('text-danger', 'is-invalid');
            ageInput.classList.remove('border-warning');
            if (warningEl) {
                warningEl.className = 'text-danger small mt-1';
                warningEl.innerHTML = '<i class="bi bi-x-circle me-1"></i> Date of birth cannot be in the future.';
                warningEl.classList.remove('d-none');
            }
        } else if (res.isUnderage) {
            ageInput.classList.add('text-danger', 'border-warning');
            ageInput.classList.remove('is-invalid');
            if (warningEl) {
                warningEl.className = 'text-warning text-dark small mt-1 p-1 bg-warning-subtle rounded border border-warning-subtle';
                warningEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <strong>Statutory Alert:</strong> Candidate is under 18 years of age. Minimum legal working age is 18.';
                warningEl.classList.remove('d-none');
            }
        } else {
            ageInput.classList.remove('text-danger', 'is-invalid', 'border-danger', 'border-warning');
            if (warningEl) warningEl.classList.add('d-none');
        }
    }

    function showStep(step) {
        document.querySelectorAll('.wizard-section').forEach(sec => sec.classList.add('d-none'));
        const activeSec = document.getElementById('section-' + step);
        if (activeSec) {
            activeSec.classList.remove('d-none');
        }

        stepNavButtons.forEach(btn => {
            const btnStep = parseInt(btn.getAttribute('data-step'));
            if (btnStep === step) {
                btn.classList.add('active', 'text-white');
                btn.classList.remove('text-muted');
            } else {
                btn.classList.remove('active', 'text-white');
                if (btnStep < step) {
                    const check = document.getElementById('stepCheck-' + btnStep);
                    if (check) check.classList.remove('d-none');
                }
            }
        });

        currentStep = step;
        if (currentStepInput) currentStepInput.value = step;
        stepBadge.textContent = 'Step ' + step + ' of ' + totalSteps;
        progressBar.style.width = ((step / totalSteps) * 100) + '%';

        // Prev button visibility
        if (step > 1) {
            prevBtn.classList.remove('d-none');
        } else {
            prevBtn.classList.add('d-none');
        }

        // Next vs Finalize visibility
        if (step === totalSteps) {
            nextBtn.classList.add('d-none');
            finalizeBtn.classList.remove('d-none');
        } else {
            nextBtn.classList.remove('d-none');
            finalizeBtn.classList.add('d-none');
        }

        if (step === 1) {
            updateCalculatedAge();
        }

        // Save progress on step change
        scheduleAutoSave(200);

        window.scrollTo({ top: 120, behavior: 'smooth' });
    }

    nextBtn.addEventListener('click', function () {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    stepNavButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetStep = parseInt(this.getAttribute('data-step'));
            showStep(targetStep);
        });
    });

    // Attach DOB listeners
    if (dobInput) {
        dobInput.addEventListener('input', updateCalculatedAge);
        dobInput.addEventListener('change', updateCalculatedAge);
        dobInput.addEventListener('blur', updateCalculatedAge);
        dobInput.addEventListener('keyup', updateCalculatedAge);
        updateCalculatedAge();
    }

    // Joining Date & Service auto-calc
    const joinInput = document.getElementById('joiningDateInput');
    const serviceInput = document.getElementById('calcServiceMonthsInput');
    function updateServiceMonths() {
        if (joinInput && joinInput.value && serviceInput) {
            const parts = joinInput.value.split('-');
            if (parts.length === 3) {
                const jy = parseInt(parts[0], 10);
                const jm = parseInt(parts[1], 10) - 1;
                const now = new Date();
                const months = (now.getFullYear() - jy) * 12 + (now.getMonth() - jm);
                serviceInput.value = Math.max(0, months) + ' Months';
            }
        }
    }
    if (joinInput) {
        joinInput.addEventListener('input', updateServiceMonths);
        joinInput.addEventListener('change', updateServiceMonths);
        updateServiceMonths();
    }

    // Divyaang Toggle
    const divyaangSwitch = document.getElementById('divyaangSwitch');
    const disabilityBox = document.getElementById('disabilityDetailsBox');
    if (divyaangSwitch && disabilityBox) {
        divyaangSwitch.addEventListener('change', function () {
            disabilityBox.classList.toggle('d-none', !this.checked);
        });
    }

    // Onboarding Channel Toggle
    const channelSelect = document.getElementById('onboardingChannelSelect');
    const contractorCol = document.getElementById('contractorVendorCol');
    if (channelSelect && contractorCol) {
        channelSelect.addEventListener('change', function () {
            if (this.value === 'Contractor') {
                contractorCol.classList.remove('d-none');
            } else {
                contractorCol.classList.add('d-none');
            }
        });
    }

    // Permanent address same as current sync
    const sameCheckbox = document.getElementById('sameAddressCheckbox');
    if (sameCheckbox) {
        sameCheckbox.addEventListener('change', function () {
            if (this.checked) {
                const setVal = (id1, id2) => {
                    const el1 = document.getElementById(id1);
                    const el2 = document.getElementById(id2);
                    if (el1 && el2) el1.value = el2.value;
                };
                setVal('perm_flat', 'curr_flat');
                setVal('perm_street', 'curr_street');
                setVal('perm_area', 'curr_area');
                setVal('perm_city', 'curr_city');
                setVal('perm_dist', 'curr_dist');
                setVal('perm_state', 'curr_state');
                setVal('perm_pin', 'curr_pin');
                scheduleAutoSave(200);
            }
        });
    }

    // Photo Preview
    const photoInput = document.getElementById('profile_photo_input');
    const photoImg = document.getElementById('photoPreviewImg');
    const photoIcon = document.getElementById('photoPlaceholderIcon');
    if (photoInput) {
        photoInput.addEventListener('change', function (e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function (ev) {
                    if (photoImg) {
                        photoImg.src = ev.target.result;
                        photoImg.style.display = 'block';
                    }
                    if (photoIcon) photoIcon.style.display = 'none';
                };
                reader.readAsDataURL(e.target.files[0]);
                scheduleAutoSave(300);
            }
        });
    }

    // STEP 12: Staged Documents Management
    const docStagingType = document.getElementById('docStagingType');
    const docStagingTitle = document.getElementById('docStagingTitle');
    const docStagingExpiry = document.getElementById('docStagingExpiry');
    const docStagingFileInput = document.getElementById('docStagingFileInput');
    const docStagingClearFileBtn = document.getElementById('docStagingClearFileBtn');
    const docStagingAddBtn = document.getElementById('docStagingAddBtn');
    const docFileValidationError = document.getElementById('docFileValidationError');
    const stagedDocsTableBody = document.getElementById('stagedDocsTableBody');
    const stagedDocsCountBadge = document.getElementById('stagedDocsCountBadge');
    const stagedDocsInputsContainer = document.getElementById('stagedDocsInputsContainer');

    let stagedDocuments = [];
    let docIdCounter = 1;

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function getCategoryBadgeClass(category) {
        switch (category) {
            case 'Identity': return 'bg-primary-subtle text-primary border border-primary-subtle';
            case 'Educational': return 'bg-info-subtle text-info-emphasis border border-info-subtle';
            case 'Employment': return 'bg-success-subtle text-success border border-success-subtle';
            case 'Banking': return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            case 'Visa & Immigration': return 'bg-purple-subtle text-purple border';
            case 'Health': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-secondary-subtle text-secondary border';
        }
    }

    function renderStagedDocuments() {
        if (!stagedDocsTableBody || !stagedDocsInputsContainer) return;
        stagedDocsTableBody.innerHTML = '';
        stagedDocsInputsContainer.innerHTML = '';

        if (stagedDocuments.length === 0) {
            stagedDocsTableBody.innerHTML = `
                <tr id="noDocsPlaceholderRow">
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-folder2-open display-6 d-block mb-2 text-secondary opacity-50"></i>
                        <div class="fw-semibold">No documents attached yet</div>
                        <div class="small">Select a category and file above, then click <strong>Attach to Dossier</strong>.</div>
                    </td>
                </tr>
            `;
            if (stagedDocsCountBadge) stagedDocsCountBadge.textContent = '0';
            return;
        }

        if (stagedDocsCountBadge) stagedDocsCountBadge.textContent = stagedDocuments.length;

        stagedDocuments.forEach((item, index) => {
            const tr = document.createElement('tr');
            const categoryBadge = `<span class="badge ${getCategoryBadgeClass(item.category)}">${item.category}</span>`;
            const expiryText = item.expiry ? item.expiry : '<span class="text-muted">—</span>';
            const fileIcon = item.isPdf 
                ? '<i class="bi bi-file-earmark-pdf text-danger me-1 fs-5"></i>' 
                : '<i class="bi bi-file-earmark-image text-primary me-1 fs-5"></i>';

            tr.innerHTML = `
                <td class="text-center text-muted fw-semibold">${index + 1}</td>
                <td>
                    <div class="fw-semibold text-dark">${item.type}</div>
                    ${item.title && item.title !== item.type ? `<div class="small text-muted">${item.title}</div>` : ''}
                </td>
                <td>${categoryBadge}</td>
                <td>
                    <div class="d-flex align-items-center">
                        ${fileIcon}
                        <span class="text-truncate" style="max-width: 220px;" title="${item.fileName}">${item.fileName}</span>
                    </div>
                    <small class="text-muted">${item.sizeFormatted}</small>
                </td>
                <td>${expiryText}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary preview-doc-btn" data-id="${item.id}" title="Preview Document">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger remove-doc-btn" data-id="${item.id}" title="Remove Document">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            stagedDocsTableBody.appendChild(tr);

            if (item.file) {
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = `documents[${index}][file]`;
                fileInput.className = 'd-none';

                if (window.DataTransfer) {
                    const dt = new DataTransfer();
                    dt.items.add(item.file);
                    fileInput.files = dt.files;
                }
                stagedDocsInputsContainer.appendChild(fileInput);
            }

            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = `documents[${index}][document_type]`;
            typeInput.value = item.type;
            stagedDocsInputsContainer.appendChild(typeInput);

            const catInput = document.createElement('input');
            catInput.type = 'hidden';
            catInput.name = `documents[${index}][category]`;
            catInput.value = item.category;
            stagedDocsInputsContainer.appendChild(catInput);

            if (item.title) {
                const titleInput = document.createElement('input');
                titleInput.type = 'hidden';
                titleInput.name = `documents[${index}][title]`;
                titleInput.value = item.title;
                stagedDocsInputsContainer.appendChild(titleInput);
            }

            if (item.expiry) {
                const expInput = document.createElement('input');
                expInput.type = 'hidden';
                expInput.name = `documents[${index}][expiry_date]`;
                expInput.value = item.expiry;
                stagedDocsInputsContainer.appendChild(expInput);
            }
        });

        // Bind preview buttons
        stagedDocsTableBody.querySelectorAll('.preview-doc-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const found = stagedDocuments.find(d => String(d.id) === String(id));
                if (found) {
                    showDocPreviewModal(found);
                }
            });
        });

        // Bind remove buttons
        stagedDocsTableBody.querySelectorAll('.remove-doc-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const foundIndex = stagedDocuments.findIndex(d => String(d.id) === String(id));
                if (foundIndex !== -1) {
                    const removedItem = stagedDocuments[foundIndex];
                    if (removedItem.objectUrl && !removedItem.isPersisted) {
                        URL.revokeObjectURL(removedItem.objectUrl);
                    }
                    if (removedItem.serverDocId && draftIdInput && draftIdInput.value) {
                        fetch("{{ route('employees.drafts.remove-file') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                draft_id: draftIdInput.value,
                                doc_id: removedItem.serverDocId
                            })
                        }).catch(() => {});
                    }
                    stagedDocuments.splice(foundIndex, 1);
                    renderStagedDocuments();
                    scheduleAutoSave(300);
                }
            });
        });
    }

    function showDocPreviewModal(item) {
        const modalEl = document.getElementById('docPreviewModal');
        if (!modalEl) return;
        const modalTitle = document.getElementById('docPreviewModalLabel');
        const imgPreview = document.getElementById('docModalImagePreview');
        const framePreview = document.getElementById('docModalFramePreview');
        const unsupportedPreview = document.getElementById('docModalUnsupportedPreview');
        const metaInfo = document.getElementById('docModalMetaInfo');

        modalTitle.innerHTML = `<i class="bi bi-file-earmark-text text-primary me-2"></i>Preview: ${item.type}`;
        metaInfo.textContent = `${item.fileName} (${item.sizeFormatted}) — ${item.category}`;

        imgPreview.classList.add('d-none');
        framePreview.classList.add('d-none');
        unsupportedPreview.classList.add('d-none');

        if (item.isImage) {
            imgPreview.src = item.objectUrl;
            imgPreview.classList.remove('d-none');
        } else if (item.isPdf) {
            framePreview.src = item.objectUrl;
            framePreview.classList.remove('d-none');
        } else {
            unsupportedPreview.classList.remove('d-none');
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    if (docStagingClearFileBtn) {
        docStagingClearFileBtn.addEventListener('click', function () {
            docStagingFileInput.value = '';
            docFileValidationError.classList.add('d-none');
        });
    }

    if (docStagingAddBtn) {
        docStagingAddBtn.addEventListener('click', function () {
            docFileValidationError.classList.add('d-none');
            docFileValidationError.textContent = '';

            const selectedOption = docStagingType.options[docStagingType.selectedIndex];
            const docType = docStagingType.value;
            if (!docType) {
                docFileValidationError.textContent = 'Please select a document category / type.';
                docFileValidationError.classList.remove('d-none');
                docStagingType.focus();
                return;
            }

            const category = selectedOption.getAttribute('data-category') || 'Identity';
            const title = docStagingTitle.value.trim();
            const expiry = docStagingExpiry.value;

            if (!docStagingFileInput.files || !docStagingFileInput.files[0]) {
                docFileValidationError.textContent = 'Please select a file to attach.';
                docFileValidationError.classList.remove('d-none');
                docStagingFileInput.focus();
                return;
            }

            const file = docStagingFileInput.files[0];
            const maxBytes = 5 * 1024 * 1024;
            if (file.size > maxBytes) {
                docFileValidationError.textContent = `File size (${formatBytes(file.size)}) exceeds the maximum allowed limit of 5MB.`;
                docFileValidationError.classList.remove('d-none');
                return;
            }

            const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            const fileExt = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png'].includes(fileExt);
            const isPdf = fileExt === 'pdf';

            if (!allowedExtensions.includes(fileExt)) {
                docFileValidationError.textContent = 'Invalid file format. Only PDF, JPG, and PNG files are accepted.';
                docFileValidationError.classList.remove('d-none');
                return;
            }

            const objectUrl = URL.createObjectURL(file);
            const newDocId = docIdCounter++;

            const newDocItem = {
                id: newDocId,
                file: file,
                fileName: file.name,
                fileSize: file.size,
                sizeFormatted: formatBytes(file.size),
                type: docType,
                category: category,
                title: title,
                expiry: expiry,
                isImage: isImage,
                isPdf: isPdf,
                objectUrl: objectUrl,
                isPersisted: false
            };

            stagedDocuments.push(newDocItem);

            // Upload directly to server draft in background if draft exists
            if (draftIdInput && draftIdInput.value) {
                const docFormData = new FormData();
                docFormData.append('draft_id', draftIdInput.value);
                docFormData.append('file', file);
                docFormData.append('type', docType);
                docFormData.append('category', category);
                docFormData.append('title', title);
                docFormData.append('expiry', expiry);

                fetch("{{ route('employees.drafts.upload-file') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: docFormData
                }).then(res => res.json()).then(uploadRes => {
                    if (uploadRes.success && uploadRes.document) {
                        newDocItem.serverDocId = uploadRes.document.id;
                        newDocItem.objectUrl = uploadRes.document.url;
                        newDocItem.isPersisted = true;
                        if (draftVersionInput && uploadRes.version) {
                            draftVersionInput.value = uploadRes.version;
                        }
                    }
                }).catch(() => {});
            } else {
                scheduleAutoSave(300);
            }

            // Reset inputs
            docStagingFileInput.value = '';
            docStagingTitle.value = '';
            docStagingExpiry.value = '';
            docStagingType.value = '';

            renderStagedDocuments();
        });
    }

    // ── HYDRATE SAVED DRAFT DATA ON LOAD ──
    const initialDraftData = @json($draft?->form_data ?? []);
    const initialUploadedFiles = @json($draft?->uploaded_files ?? []);
    const initialStep = {{ (int)($draft?->current_step ?? 1) }};

    if (initialDraftData && typeof initialDraftData === 'object' && Object.keys(initialDraftData).length > 0) {
        Object.keys(initialDraftData).forEach(key => {
            const val = initialDraftData[key];
            if (val === null || val === undefined || val === '') return;

            // Radios
            const radios = document.querySelectorAll(`input[type="radio"][name="${key}"]`);
            if (radios.length > 0) {
                radios.forEach(r => {
                    r.checked = (r.value === String(val));
                });
                return;
            }

            // Checkboxes
            const checkbox = document.querySelector(`input[type="checkbox"][name="${key}"]`);
            if (checkbox) {
                checkbox.checked = Boolean(val);
                checkbox.dispatchEvent(new Event('change'));
                return;
            }

            // Text, Select, Textarea
            const el = document.querySelector(`[name="${key}"]`);
            if (el && !el.value) {
                el.value = val;
                el.dispatchEvent(new Event('change'));
            }
        });
    }

    // Restore profile photo preview if draft had one
    if (initialUploadedFiles && initialUploadedFiles.profile_photo && initialUploadedFiles.profile_photo.url) {
        if (photoImg) {
            photoImg.src = initialUploadedFiles.profile_photo.url;
            photoImg.style.display = 'block';
        }
        if (photoIcon) photoIcon.style.display = 'none';
    }

    // Restore staged documents if draft had any
    if (initialUploadedFiles && initialUploadedFiles.documents && Array.isArray(initialUploadedFiles.documents)) {
        initialUploadedFiles.documents.forEach(doc => {
            stagedDocuments.push({
                id: doc.id || docIdCounter++,
                serverDocId: doc.id,
                file: null,
                fileName: doc.fileName,
                fileSize: doc.fileSize,
                sizeFormatted: formatBytes(doc.fileSize || 0),
                type: doc.type,
                category: doc.category,
                title: doc.title,
                expiry: doc.expiry,
                isImage: doc.isImage,
                isPdf: doc.isPdf,
                objectUrl: doc.url,
                isPersisted: true
            });
        });
        renderStagedDocuments();
    }

    // Activate the saved step
    if (initialStep > 1 && initialStep <= totalSteps) {
        showStep(initialStep);
    } else {
        updateCalculatedAge();
    }

    // ── DEPENDENT DROPDOWN / AUTOCOMPLETE HANDLERS (Phase 5) ──
    const qualSelect = document.getElementById('qualificationSelect');
    if (qualSelect) {
        qualSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const parentId = selectedOpt ? selectedOpt.getAttribute('data-id') : null;
            const degreeList = document.getElementById('degreeNamesList');
            if (!degreeList) return;

            if (parentId) {
                fetch(`{{ route('admin.masters.dependent-options') }}?parent_id=${parentId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data) && data.length > 0) {
                            degreeList.innerHTML = '';
                            data.forEach(item => {
                                const opt = document.createElement('option');
                                opt.value = item.name;
                                degreeList.appendChild(opt);
                            });
                        }
                    })
                    .catch(() => {});
            }
        });
    }

    const skillCatSelect = document.getElementById('skillCategorySelect');
    if (skillCatSelect) {
        skillCatSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const parentId = selectedOpt ? selectedOpt.getAttribute('data-id') : null;
            const skillList = document.getElementById('skillNamesList');
            if (!skillList) return;

            if (parentId) {
                fetch(`{{ route('admin.masters.dependent-options') }}?parent_id=${parentId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data) && data.length > 0) {
                            skillList.innerHTML = '';
                            data.forEach(item => {
                                const opt = document.createElement('option');
                                opt.value = item.name;
                                skillList.appendChild(opt);
                            });
                        }
                    })
                    .catch(() => {});
            }
        });
    }
});
</script>
@endpush
@endsection
