@extends('layouts.app')

@section('title', "Edit Dossier — {$employee->full_name} ({$employee->employee_code})")

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Workforce</a></li>
                <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee->id) }}">{{ $employee->full_name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Master Dossier</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0" style="font-weight: 700; color: #0F172A;">Edit Master Dossier</h4>
            <span class="badge bg-light text-dark border code-font fs-6">{{ $employee->employee_code }}</span>
            @if($employee->onboarding_status === 'Approved')
                <span class="badge bg-success-subtle text-success border">Approved</span>
            @elseif($employee->onboarding_status === 'Corrections Requested')
                <span class="badge bg-danger-subtle text-danger border">Corrections Requested</span>
            @elseif($employee->onboarding_status === 'Submitted for Review')
                <span class="badge bg-warning-subtle text-warning-emphasis border">Under Review</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary border">{{ $employee->onboarding_status ?? 'Draft' }}</span>
            @endif
        </div>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Update full 16-section employee dossier with transactional integrity & change history tracking.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Dossier</span>
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
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

@php
    $addr = $employee->addressRecord;
    $stat = $employee->statutory;
    $bank = $employee->bankDetail;
    $visa = $employee->visaDetail;
    $udf = $employee->user_defined_fields ?? [];
@endphp

<form method="POST" action="{{ route('employees.update', $employee->id) }}" id="employeeEditForm" enctype="multipart/form-data" autocomplete="off">
    @csrf
    @method('PUT')

    <!-- Prevent browser credential manager auto-filling admin credentials -->
    <input type="text" style="display:none" aria-hidden="true" tabindex="-1">
    <input type="password" style="display:none" aria-hidden="true" tabindex="-1">

    <div class="row g-4">
        <!-- Left: Step Navigation Sidebar -->
        <div class="col-lg-3">
            <div class="card card-custom sticky-top" style="top: 85px; z-index: 10;">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-weight: 700; font-size: 13px; color: #1E293B;">DOSSIER SECTIONS</span>
                        <span class="badge bg-primary-subtle text-primary fw-bold" id="stepCounterBadge">Section 1 of 12</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="wizardProgressBar" role="progressbar" style="width: 8.33%;" aria-valuenow="8" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="list-group list-group-flush" id="wizardStepsNav" style="max-height: calc(100vh - 250px); overflow-y: auto;">
                    @php
                        $sections = [
                            1 => ['name' => 'Basic Information', 'icon' => 'bi-person'],
                            2 => ['name' => 'Employment Information', 'icon' => 'bi-briefcase'],
                            3 => ['name' => 'Contact Information', 'icon' => 'bi-telephone'],
                            4 => ['name' => 'Address Information', 'icon' => 'bi-geo-alt'],
                            5 => ['name' => 'Family Summary', 'icon' => 'bi-people'],
                            6 => ['name' => 'Personal & Health', 'icon' => 'bi-person-badge'],
                            7 => ['name' => 'Statutory & Compliance', 'icon' => 'bi-shield-check'],
                            8 => ['name' => 'Banking & Payment', 'icon' => 'bi-bank'],
                            9 => ['name' => 'Visa & Immigration', 'icon' => 'bi-globe'],
                            10 => ['name' => 'Custom Fields (UDF)', 'icon' => 'bi-sliders'],
                            11 => ['name' => 'Revision Reason', 'icon' => 'bi-pencil-square'],
                            12 => ['name' => 'Save Changes', 'icon' => 'bi-check2-circle'],
                        ];
                    @endphp
                    @foreach ($sections as $stepNum => $step)
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 px-3 step-nav-btn {{ $stepNum === 1 ? 'active fw-bold' : '' }}" data-step="{{ $stepNum }}" style="font-size: 13px;">
                            <span class="d-flex align-items-center gap-2">
                                <i class="bi {{ $step['icon'] }}"></i>
                                <span>{{ $stepNum }}. {{ $step['name'] }}</span>
                            </span>
                            <i class="bi bi-chevron-right step-arrow" style="font-size: 11px;"></i>
                        </button>
                    @endforeach
                </div>
                <div class="card-footer bg-light p-3">
                    <button type="submit" class="btn btn-primary w-100 btn-sm shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-save2"></i>
                        <span>Save Dossier Changes</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: Step Form Content -->
        <div class="col-lg-9">

            <!-- SECTION 1: Basic Information -->
            <div class="card card-custom wizard-step-card" id="stepCard-1">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-person me-2 text-primary"></i>1. Basic Information</h6>
                        <small class="text-muted">Primary personal identifiers and demographic profile.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Title</label>
                            <select name="title" class="form-select form-select-sm">
                                <option value="">Select</option>
                                @if(isset($masters['title']) && count($masters['title']) > 0)
                                    @foreach($masters['title'] as $t)
                                        <option value="{{ $t->name }}" {{ old('title', $employee->title) === $t->name ? 'selected' : '' }}>{{ $t->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Mr.', 'Ms.', 'Mrs.', 'Dr.', 'Prof.'] as $t)
                                        <option value="{{ $t }}" {{ old('title', $employee->title) === $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control form-control-sm @error('first_name') is-invalid @enderror" value="{{ old('first_name', $employee->first_name) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control form-control-sm" value="{{ old('middle_name', $employee->middle_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control form-control-sm @error('last_name') is-invalid @enderror" value="{{ old('last_name', $employee->last_name) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Display Name</label>
                            <input type="text" name="display_name" class="form-control form-control-sm" value="{{ old('display_name', $employee->display_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Also Known As (AKA)</label>
                            <input type="text" name="aka_name" class="form-control form-control-sm" value="{{ old('aka_name', $employee->aka_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select form-select-sm" required>
                                @if(isset($masters['gender']) && count($masters['gender']) > 0)
                                    @foreach($masters['gender'] as $g)
                                        <option value="{{ $g->name }}" {{ old('gender', $employee->gender) === $g->name || old('gender', $employee->gender) === $g->code ? 'selected' : '' }}>{{ $g->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Male', 'Female', 'Other'] as $g)
                                        <option value="{{ $g }}" {{ old('gender', $employee->gender) === $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" id="dobInput" class="form-control form-control-sm @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-muted" style="font-size: 12.5px;">Age (Auto)</label>
                            <input type="text" id="calcAgeInput" class="form-control form-control-sm bg-light text-muted" value="{{ $employee->age ? $employee->age . ' Yrs' : '' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Nationality</label>
                            <input type="text" name="nationality" class="form-control form-control-sm" value="{{ old('nationality', $employee->nationality ?? 'Indian') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Place of Birth</label>
                            <input type="text" name="place_of_birth" class="form-control form-control-sm" value="{{ old('place_of_birth', $employee->place_of_birth) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Blood Group</label>
                            <select name="blood_group" class="form-select form-select-sm">
                                <option value="">Select Blood Group</option>
                                @if(isset($masters['blood_group']) && count($masters['blood_group']) > 0)
                                    @foreach($masters['blood_group'] as $bg)
                                        <option value="{{ $bg->name }}" {{ old('blood_group', $employee->blood_group) === $bg->name || old('blood_group', $employee->blood_group) === $bg->code ? 'selected' : '' }}>{{ $bg->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                        <option value="{{ $bg }}" {{ old('blood_group', $employee->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Visible Identification Mark</label>
                            <input type="text" name="identification_mark" class="form-control form-control-sm" value="{{ old('identification_mark', $employee->identification_mark) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Profile Photo</label>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 50px; height: 50px; border-radius: 8px; border: 1px solid #CBD5E1; overflow: hidden; background: #F8FAFC; display: flex; align-items: center; justify-content: center;">
                                    @if($employee->profile_photo_path)
                                        <img src="{{ asset('storage/' . $employee->profile_photo_path) }}" id="photoPreviewImg" style="width: 100%; height: 100%; object-fit: cover;">
                                        <i class="bi bi-person text-muted fs-4" id="photoPlaceholderIcon" style="display:none;"></i>
                                    @else
                                        <img src="" id="photoPreviewImg" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                        <i class="bi bi-person text-muted fs-4" id="photoPlaceholderIcon"></i>
                                    @endif
                                </div>
                                <input type="file" name="profile_photo" id="profile_photo_input" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-end p-3">
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="2">Next: Employment Information &rarr;</button>
                </div>
            </div>

            <!-- SECTION 2: Employment Information -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-2">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-briefcase me-2 text-primary"></i>2. Employment Information</h6>
                        <small class="text-muted">Organizational assignment, site location, department, grade, and reporting hierarchy.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Ticket Number</label>
                            <input type="text" name="ticket_number" class="form-control form-control-sm" value="{{ old('ticket_number', $employee->ticket_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Gate Pass #</label>
                            <input type="text" name="gate_pass_number" class="form-control form-control-sm" value="{{ old('gate_pass_number', $employee->gate_pass_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Job Post Code</label>
                            <input type="text" name="job_post_code" class="form-control form-control-sm" value="{{ old('job_post_code', $employee->job_post_code) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Grade / Band</label>
                            <input type="text" name="grade" class="form-control form-control-sm" value="{{ old('grade', $employee->grade) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Work Site / Location <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select form-select-sm" required>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id', $employee->location_id) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select form-select-sm" required>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Designation <span class="text-danger">*</span></label>
                            <select name="designation_id" class="form-select form-select-sm" required>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}" {{ old('designation_id', $employee->designation_id) == $desig->id ? 'selected' : '' }}>
                                        {{ $desig->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Employee Category <span class="text-danger">*</span></label>
                            <select name="employee_category_id" class="form-select form-select-sm" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('employee_category_id', $employee->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Onboarding Channel <span class="text-danger">*</span></label>
                            <select name="onboarding_channel" id="onboardingChannelSelect" class="form-select form-select-sm" required>
                                @if(isset($masters['onboarding_channel']) && count($masters['onboarding_channel']) > 0)
                                    @foreach($masters['onboarding_channel'] as $ch)
                                        <option value="{{ $ch->name }}" {{ old('onboarding_channel', $employee->onboarding_channel) === $ch->name || old('onboarding_channel', $employee->onboarding_channel) === $ch->code ? 'selected' : '' }}>{{ $ch->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Direct', 'Contractor', 'Referral', 'Agency'] as $ch)
                                        <option value="{{ $ch }}" {{ old('onboarding_channel', $employee->onboarding_channel) === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4 {{ old('onboarding_channel', $employee->onboarding_channel) === 'Contractor' ? '' : 'd-none' }}" id="contractorVendorCol">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Contractor Vendor</label>
                            <select name="contractor_id" class="form-select form-select-sm">
                                <option value="">Select Contractor</option>
                                @foreach($contractors as $con)
                                    <option value="{{ $con->id }}" {{ old('contractor_id', $employee->contractor_id) == $con->id ? 'selected' : '' }}>
                                        {{ $con->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Reporting Manager</label>
                            <select name="reporting_manager_id" class="form-select form-select-sm">
                                <option value="">No Direct Manager</option>
                                @foreach($managers as $mgr)
                                    <option value="{{ $mgr->id }}" {{ old('reporting_manager_id', $employee->reporting_manager_id) == $mgr->id ? 'selected' : '' }}>
                                        {{ $mgr->full_name }} ({{ $mgr->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Skill Level</label>
                            <select name="skill_level" class="form-select form-select-sm">
                                @if(isset($masters['skill_classification']) && count($masters['skill_classification']) > 0)
                                    @foreach($masters['skill_classification'] as $sl)
                                        <option value="{{ $sl->name }}" {{ old('skill_level', $employee->skill_level) === $sl->name || old('skill_level', $employee->skill_level) === $sl->code ? 'selected' : '' }}>{{ $sl->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Unskilled', 'Semi-Skilled', 'Skilled', 'Highly Skilled', 'Expert'] as $sl)
                                        <option value="{{ $sl }}" {{ old('skill_level', $employee->skill_level) === $sl ? 'selected' : '' }}>{{ $sl }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Employment Status <span class="text-danger">*</span></label>
                            <select name="employment_status" class="form-select form-select-sm" required>
                                @foreach(['Draft', 'Pending Verification', 'Active', 'Inactive', 'Resigned', 'Terminated', 'Transferred', 'Probation'] as $st)
                                    <option value="{{ $st }}" {{ old('employment_status', $employee->employment_status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Date of Joining <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" id="joiningDateInput" class="form-control form-control-sm @error('joining_date') is-invalid @enderror" value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-muted" style="font-size: 12.5px;">Service Months</label>
                            <input type="text" id="calcServiceMonthsInput" class="form-control form-control-sm bg-light text-muted" value="{{ $employee->service_months ? $employee->service_months . ' Mos' : '' }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Division / Unit</label>
                            <input type="text" name="division" class="form-control form-control-sm" value="{{ old('division', $employee->division) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Project / Cost Center</label>
                            <input type="text" name="project" class="form-control form-control-sm" value="{{ old('project', $employee->project) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Old Employee Code (Legacy)</label>
                            <input type="text" name="old_employee_code" class="form-control form-control-sm" value="{{ old('old_employee_code', $employee->old_employee_code) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">JV Code (Cr)</label>
                            <input type="text" name="jv_code_cr" class="form-control form-control-sm" value="{{ old('jv_code_cr', $employee->jv_code_cr) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="1">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="3">Next: Contact Information &rarr;</button>
                </div>
            </div>

            <!-- SECTION 3: Contact Information -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-telephone me-2 text-primary"></i>3. Contact Information</h6>
                        <small class="text-muted">Direct phone numbers, corporate/personal emails, and extension communication channels.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Primary Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Personal Mobile</label>
                            <input type="tel" name="personal_phone" class="form-control form-control-sm" value="{{ old('personal_phone', $employee->personal_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Residential Phone</label>
                            <input type="tel" name="residential_phone" class="form-control form-control-sm" value="{{ old('residential_phone', $employee->residential_phone) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Official Office Phone</label>
                            <input type="tel" name="office_phone" class="form-control form-control-sm" value="{{ old('office_phone', $employee->office_phone) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Extension #</label>
                            <input type="text" name="extension_number" class="form-control form-control-sm" value="{{ old('extension_number', $employee->extension_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Primary Email Address</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $employee->email) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Alternate / Personal Email</label>
                            <input type="email" name="alternate_email" class="form-control form-control-sm" value="{{ old('alternate_email', $employee->alternate_email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">CC Email (Notifications)</label>
                            <input type="email" name="cc_email" class="form-control form-control-sm" value="{{ old('cc_email', $employee->cc_email) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Microsoft 365 User Principal Name</label>
                            <input type="text" name="microsoft_user" class="form-control form-control-sm" value="{{ old('microsoft_user', $employee->microsoft_user) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Google Workspace Account</label>
                            <input type="text" name="google_user" class="form-control form-control-sm" value="{{ old('google_user', $employee->google_user) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="2">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="4">Next: Address Information &rarr;</button>
                </div>
            </div>

            <!-- SECTION 4: Address Information -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-geo-alt me-2 text-primary"></i>4. Address Information</h6>
                        <small class="text-muted">Current residential residence and statutory permanent domicile details.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-primary mb-3 fw-bold" style="font-size: 12px; letter-spacing: 0.05em;">Current / Present Residence Address</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Flat / House No / Building</label>
                            <input type="text" name="curr_flat" id="curr_flat" class="form-control form-control-sm" value="{{ old('curr_flat', $addr?->current_flat) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Street / Road / Landmark</label>
                            <input type="text" name="curr_street" id="curr_street" class="form-control form-control-sm" value="{{ old('curr_street', $addr?->current_street) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Area / Locality</label>
                            <input type="text" name="curr_area" id="curr_area" class="form-control form-control-sm" value="{{ old('curr_area', $addr?->current_area) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">City / Town</label>
                            <input type="text" name="curr_city" id="curr_city" class="form-control form-control-sm" value="{{ old('curr_city', $addr?->current_city) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">District</label>
                            <input type="text" name="curr_dist" id="curr_dist" class="form-control form-control-sm" value="{{ old('curr_dist', $addr?->current_district) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">State</label>
                            <input type="text" name="curr_state" id="curr_state" class="form-control form-control-sm" value="{{ old('curr_state', $addr?->current_state) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">PIN Code</label>
                            <input type="text" name="curr_pin" id="curr_pin" class="form-control form-control-sm" value="{{ old('curr_pin', $addr?->current_pincode) }}">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-2.5 rounded bg-light border mb-3">
                        <div class="fw-bold" style="font-size: 13px;">Permanent Address</div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="permanent_same_as_current" value="1" id="sameAddressCheckbox" {{ old('permanent_same_as_current', $addr?->permanent_same_as_current) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="sameAddressCheckbox" style="font-size: 12.5px;">Same as Current Address</label>
                        </div>
                    </div>

                    <div class="row g-3" id="permanentAddressContainer">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Flat / House No / Building</label>
                            <input type="text" name="perm_flat" id="perm_flat" class="form-control form-control-sm" value="{{ old('perm_flat', $addr?->permanent_flat) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Street / Road / Landmark</label>
                            <input type="text" name="perm_street" id="perm_street" class="form-control form-control-sm" value="{{ old('perm_street', $addr?->permanent_street) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Area / Locality</label>
                            <input type="text" name="perm_area" id="perm_area" class="form-control form-control-sm" value="{{ old('perm_area', $addr?->permanent_area) }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">City / Town</label>
                            <input type="text" name="perm_city" id="perm_city" class="form-control form-control-sm" value="{{ old('perm_city', $addr?->permanent_city) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">District</label>
                            <input type="text" name="perm_dist" id="perm_dist" class="form-control form-control-sm" value="{{ old('perm_dist', $addr?->permanent_district) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">State</label>
                            <input type="text" name="perm_state" id="perm_state" class="form-control form-control-sm" value="{{ old('perm_state', $addr?->permanent_state) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">PIN Code</label>
                            <input type="text" name="perm_pin" id="perm_pin" class="form-control form-control-sm" value="{{ old('perm_pin', $addr?->permanent_pincode) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="3">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="5">Next: Family Details &rarr;</button>
                </div>
            </div>

            <!-- SECTION 5: Family Summary -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-5">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-people me-2 text-primary"></i>5. Family & Emergency Summary</h6>
                        <small class="text-muted">Primary parents, spouse, marital status and emergency contact details.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Father's Name</label>
                            <input type="text" name="father_name" class="form-control form-control-sm" value="{{ old('father_name', $employee->father_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control form-control-sm" value="{{ old('mother_name', $employee->mother_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Spouse's Name</label>
                            <input type="text" name="spouse_name" class="form-control form-control-sm" value="{{ old('spouse_name', $employee->spouse_name) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Marital Status</label>
                            <select name="marital_status" class="form-select form-select-sm">
                                <option value="">Select Status</option>
                                @if(isset($masters['marital_status']) && count($masters['marital_status']) > 0)
                                    @foreach($masters['marital_status'] as $ms)
                                        <option value="{{ $ms->name }}" {{ old('marital_status', $employee->marital_status) === $ms->name || old('marital_status', $employee->marital_status) === $ms->code ? 'selected' : '' }}>{{ $ms->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $ms)
                                        <option value="{{ $ms }}" {{ old('marital_status', $employee->marital_status) === $ms ? 'selected' : '' }}>{{ $ms }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Marriage Anniversary Date</label>
                            <input type="date" name="marriage_date" class="form-control form-control-sm" value="{{ old('marriage_date', $employee->marriage_date?->format('Y-m-d')) }}">
                        </div>

                        <div class="col-12"><hr class="my-2"></div>
                        <h6 class="text-uppercase text-danger mb-2 fw-bold" style="font-size: 12px; letter-spacing: 0.05em;">Emergency Contact Person</h6>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Contact Person Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control form-control-sm" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Emergency Phone Number</label>
                            <input type="tel" name="emergency_contact_phone" class="form-control form-control-sm" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Relationship</label>
                            <input type="text" name="emergency_contact_relation" class="form-control form-control-sm" placeholder="e.g. Spouse, Brother" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="4">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="6">Next: Personal & Health &rarr;</button>
                </div>
            </div>

            <!-- SECTION 6: Personal Details & Health -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-6">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-person-badge me-2 text-primary"></i>6. Personal Details & Health</h6>
                        <small class="text-muted">Disability accommodations, health notes, and personal observations.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_specially_abled" value="1" id="divyaangSwitch" {{ old('is_specially_abled', $employee->is_specially_abled) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="divyaangSwitch" style="font-size: 13px;">Employee is Specially Abled / Divyaang</label>
                            </div>
                        </div>

                        <div class="col-12 {{ old('is_specially_abled', $employee->is_specially_abled) ? '' : 'd-none' }}" id="disabilityDetailsBox">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Disability Details / Certificate Information</label>
                            <textarea name="disability_details" class="form-control form-control-sm" rows="2">{{ old('disability_details', $employee->disability_details) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Personal Notes & Special Instructions</label>
                            <textarea name="personal_remarks" class="form-control form-control-sm" rows="3">{{ old('personal_remarks', $employee->personal_remarks) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="5">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="7">Next: Statutory & Compliance &rarr;</button>
                </div>
            </div>

            <!-- SECTION 7: Statutory & Compliance -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-7">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-shield-check me-2 text-primary"></i>7. Statutory & Compliance</h6>
                        <small class="text-muted">UAN, PF, ESIC, Aadhaar, PAN and compliance registrations.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number" class="form-control form-control-sm" placeholder="12 digits" value="{{ old('aadhaar_number', $employee->aadhaar_masked) }}">
                            <small class="text-muted" style="font-size: 11px;">Current: {{ $employee->aadhaar_masked ?? 'Not set' }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">PAN Card Number</label>
                            <input type="text" name="pan_number" class="form-control form-control-sm text-uppercase" placeholder="ABCDE1234F" value="{{ old('pan_number', $employee->pan_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Universal Account Number (UAN)</label>
                            <input type="text" name="uan_number" class="form-control form-control-sm" value="{{ old('uan_number', $stat?->uan_number ?? $employee->uan_number) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">PF Number / Member ID</label>
                            <input type="text" name="pf_number" class="form-control form-control-sm" value="{{ old('pf_number', $stat?->pf_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Family Pension Fund (FPF) #</label>
                            <input type="text" name="fpf_number" class="form-control form-control-sm" value="{{ old('fpf_number', $stat?->fpf_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">VPF Contribution (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="vpf_percentage" class="form-control form-control-sm" value="{{ old('vpf_percentage', $stat?->vpf_percentage) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">ESIC Insurance Number</label>
                            <input type="text" name="esic_number" class="form-control form-control-sm" value="{{ old('esic_number', $stat?->esic_number ?? $employee->esic_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">ESIC Dispensary / IMP Name</label>
                            <input type="text" name="esic_imp_name" class="form-control form-control-sm" value="{{ old('esic_imp_name', $stat?->esic_imp_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Election / Voter ID Card #</label>
                            <input type="text" name="election_card_number" class="form-control form-control-sm" value="{{ old('election_card_number', $stat?->election_card_number) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Ration Card Number</label>
                            <input type="text" name="ration_card_number" class="form-control form-control-sm" value="{{ old('ration_card_number', $stat?->ration_card_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">NPR Number</label>
                            <input type="text" name="npr_number" class="form-control form-control-sm" value="{{ old('npr_number', $stat?->npr_number) }}">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="is_international_worker" value="1" id="intlWorkerSwitch" {{ old('is_international_worker', $stat?->is_international_worker) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="intlWorkerSwitch" style="font-size: 12.5px;">International Worker</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="6">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="8">Next: Banking & Payment &rarr;</button>
                </div>
            </div>

            <!-- SECTION 8: Banking & Payment -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-8">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-bank me-2 text-primary"></i>8. Banking & Payment Details</h6>
                        <small class="text-muted">Salary disbursement destination, bank account details and payment channel.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info py-2 px-3 d-flex align-items-center gap-2 mb-3" style="font-size: 12.5px;">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Compensation segregation strictly enforced (Page 94). Only authorized HR / Payroll officers can view full disbursement details.</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control form-control-sm" value="{{ old('bank_name', $bank?->bank_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Branch Name</label>
                            <input type="text" name="bank_branch" class="form-control form-control-sm" value="{{ old('bank_branch', $bank?->bank_branch) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control form-control-sm text-uppercase" placeholder="HDFC0001234" value="{{ old('bank_ifsc', $bank?->bank_ifsc) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Account Number</label>
                            <input type="text" name="bank_account_number" class="form-control form-control-sm" placeholder="Full account number" value="{{ old('bank_account_number', $bank?->bank_account_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Account Type</label>
                            <select name="bank_account_type" class="form-select form-select-sm">
                                @if(isset($masters['bank_account_type']) && count($masters['bank_account_type']) > 0)
                                    @foreach($masters['bank_account_type'] as $at)
                                        <option value="{{ $at->name }}" {{ old('bank_account_type', $bank?->bank_account_type) === $at->name || old('bank_account_type', $bank?->bank_account_type) === $at->code ? 'selected' : '' }}>{{ $at->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Salary', 'Savings', 'Current'] as $at)
                                        <option value="{{ $at }}" {{ old('bank_account_type', $bank?->bank_account_type) === $at ? 'selected' : '' }}>{{ $at }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Payment Mode</label>
                            <select name="payment_mode" class="form-select form-select-sm">
                                @if(isset($masters['payment_mode']) && count($masters['payment_mode']) > 0)
                                    @foreach($masters['payment_mode'] as $pm)
                                        <option value="{{ $pm->name }}" {{ old('payment_mode', $bank?->payment_mode ?? $employee->payment_mode) === $pm->name || old('payment_mode', $bank?->payment_mode ?? $employee->payment_mode) === $pm->code ? 'selected' : '' }}>{{ $pm->name }}</option>
                                    @endforeach
                                @else
                                    @foreach(['Bank Transfer', 'Cheque', 'Cash', 'NEFT/RTGS'] as $pm)
                                        <option value="{{ $pm }}" {{ old('payment_mode', $bank?->payment_mode ?? $employee->payment_mode) === $pm ? 'selected' : '' }}>{{ $pm }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Name on Bank Passbook / Cheque</label>
                            <input type="text" name="bank_passbook_name" class="form-control form-control-sm" value="{{ old('bank_passbook_name', $bank?->bank_passbook_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Name as per PAN Card</label>
                            <input type="text" name="pan_card_name" class="form-control form-control-sm" value="{{ old('pan_card_name', $bank?->pan_card_name) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="7">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="9">Next: Visa & Immigration &rarr;</button>
                </div>
            </div>

            <!-- SECTION 9: Visa & Immigration -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-9">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-globe me-2 text-primary"></i>9. Visa & Immigration Details</h6>
                        <small class="text-muted">Passport, international work visa, and foreign employment authorizations.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="visa_required" value="1" id="visaRequiredSwitch" {{ old('visa_required', $visa?->visa_required) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="visaRequiredSwitch" style="font-size: 13px;">Visa or Work Permit Tracking Required for this Employee</label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control form-control-sm text-uppercase" value="{{ old('passport_number', $visa?->passport_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Passport Issue Date</label>
                            <input type="date" name="passport_issue_date" class="form-control form-control-sm" value="{{ old('passport_issue_date', $visa?->passport_issue_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Passport Expiry Date</label>
                            <input type="date" name="passport_expiry_date" class="form-control form-control-sm" value="{{ old('passport_expiry_date', $visa?->passport_expiry_date?->format('Y-m-d')) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Visa Type</label>
                            <input type="text" name="visa_type" class="form-control form-control-sm" placeholder="e.g. Work Visa, Business" value="{{ old('visa_type', $visa?->visa_type) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Visa Number</label>
                            <input type="text" name="visa_number" class="form-control form-control-sm" value="{{ old('visa_number', $visa?->visa_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Visa Expiry Date</label>
                            <input type="date" name="visa_expiry_date" class="form-control form-control-sm" value="{{ old('visa_expiry_date', $visa?->visa_expiry_date?->format('Y-m-d')) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Work Permit Number</label>
                            <input type="text" name="work_permit_number" class="form-control form-control-sm" value="{{ old('work_permit_number', $visa?->work_permit_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Work Permit Expiry Date</label>
                            <input type="date" name="work_permit_expiry_date" class="form-control form-control-sm" value="{{ old('work_permit_expiry_date', $visa?->work_permit_expiry_date?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="8">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="10">Next: Custom Fields &rarr;</button>
                </div>
            </div>

            <!-- SECTION 10: Custom Fields (UDF) -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-10">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-sliders me-2 text-primary"></i>10. Custom Fields (User Defined Fields)</h6>
                        <small class="text-muted">Enterprise custom attributes and organization-specific metadata.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @for($i = 1; $i <= 5; $i++)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 12.5px;">Custom Field {{ $i }}</label>
                                <input type="text" name="custom_field_{{ $i }}" class="form-control form-control-sm" value="{{ old("custom_field_{$i}", $udf["custom_field_{$i}"] ?? '') }}">
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="9">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="11">Next: Revision Reason &rarr;</button>
                </div>
            </div>

            <!-- SECTION 11: Revision Reason & Audit Trail -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-11">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-pencil-square me-2 text-primary"></i>11. Revision Reason & Audit Trail</h6>
                        <small class="text-muted">Document the administrative reason for updating this employee's master record.</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 12.5px;">Reason for Profile Revision <span class="text-danger">*</span></label>
                        <input type="text" name="change_reason" class="form-control" placeholder="e.g. Annual promotion, Address change, Bank account correction" value="{{ old('change_reason', 'Administrative Profile Update') }}" required>
                        <small class="text-muted" style="font-size: 11.5px;">This reason will be logged in the immutable Employee Profile Change History ledger.</small>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="10">&larr; Previous</button>
                    <button type="button" class="btn btn-primary btn-sm next-step-btn" data-next="12">Next: Save Changes &rarr;</button>
                </div>
            </div>

            <!-- SECTION 12: Final Review & Save -->
            <div class="card card-custom wizard-step-card d-none" id="stepCard-12">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-check2-circle me-2 text-success"></i>12. Review & Save Master Record</h6>
                        <small class="text-muted">Verify updates and commit changes to the employee master database.</small>
                    </div>
                </div>
                <div class="card-body p-4 text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success display-4"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Ready to Save Updates</h5>
                    <p class="text-muted mx-auto mb-4" style="max-width: 500px; font-size: 13.5px;">
                        All modified sections will be atomically updated. The change history ledger will log modified values along with your administrator signature and timestamp.
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                            <i class="bi bi-check-lg"></i>
                            <span>Commit & Save Master Changes</span>
                        </button>
                        <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-outline-secondary px-4 py-2">
                            Cancel
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-start p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm prev-step-btn" data-prev="11">&larr; Previous</button>
                </div>
            </div>

        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const totalSteps = 12;
    let currentStep = 1;

    function showStep(step) {
        step = Math.max(1, Math.min(totalSteps, step));
        currentStep = step;

        document.querySelectorAll('.wizard-step-card').forEach(card => card.classList.add('d-none'));
        const activeCard = document.getElementById('stepCard-' + step);
        if (activeCard) activeCard.classList.remove('d-none');

        document.querySelectorAll('.step-nav-btn').forEach(btn => {
            const btnStep = parseInt(btn.getAttribute('data-step'));
            if (btnStep === step) {
                btn.classList.add('active', 'fw-bold');
            } else {
                btn.classList.remove('active', 'fw-bold');
            }
        });

        const badge = document.getElementById('stepCounterBadge');
        if (badge) badge.innerText = `Section ${step} of ${totalSteps}`;

        const bar = document.getElementById('wizardProgressBar');
        if (bar) {
            const pct = Math.round((step / totalSteps) * 100);
            bar.style.width = pct + '%';
        }

        if (step === 1 && typeof updateCalculatedAge === 'function') {
            updateCalculatedAge();
        }

        window.scrollTo({ top: 100, behavior: 'smooth' });
    }

    document.querySelectorAll('.step-nav-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetStep = parseInt(this.getAttribute('data-step'));
            showStep(targetStep);
        });
    });

    document.querySelectorAll('.next-step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const next = parseInt(this.getAttribute('data-next'));
            showStep(next);
        });
    });

    document.querySelectorAll('.prev-step-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const prev = parseInt(this.getAttribute('data-prev'));
            showStep(prev);
        });
    });

    // DOB & Age auto-calc
    const dobInput = document.getElementById('dobInput');
    const ageInput = document.getElementById('calcAgeInput');

    function calculateExactAge(dobString) {
        if (!dobString || typeof dobString !== 'string') return null;
        const clean = dobString.trim();
        if (!clean) return null;

        let birthYear = NaN, birthMonth = NaN, birthDay = NaN;

        // Support '-', '/', or '.' separators
        const sep = clean.includes('-') ? '-' : (clean.includes('/') ? '/' : (clean.includes('.') ? '.' : null));
        if (sep) {
            const parts = clean.split(sep);
            if (parts.length === 3) {
                if (parts[0].length === 4) {
                    // YYYY-MM-DD
                    birthYear = parseInt(parts[0], 10);
                    birthMonth = parseInt(parts[1], 10) - 1;
                    birthDay = parseInt(parts[2], 10);
                } else if (parts[2].length === 4) {
                    // DD-MM-YYYY or DD/MM/YYYY
                    birthYear = parseInt(parts[2], 10);
                    birthMonth = parseInt(parts[1], 10) - 1;
                    birthDay = parseInt(parts[0], 10);
                }
            }
        }

        // Fallback to Date parser
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

        // Check if future date
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

        if (!val) {
            ageInput.value = '';
            ageInput.classList.remove('text-danger', 'is-invalid', 'border-danger');
            return;
        }

        const res = calculateExactAge(val);
        if (!res) {
            ageInput.value = '';
            ageInput.classList.remove('text-danger', 'is-invalid', 'border-danger');
            return;
        }

        ageInput.value = res.text;

        if (res.isInvalid) {
            ageInput.classList.add('text-danger', 'is-invalid');
        } else if (res.isUnderage) {
            ageInput.classList.add('text-danger');
            ageInput.classList.remove('is-invalid');
        } else {
            ageInput.classList.remove('text-danger', 'is-invalid', 'border-danger');
        }
    }

    if (dobInput) {
        dobInput.addEventListener('input', updateCalculatedAge);
        dobInput.addEventListener('change', updateCalculatedAge);
        dobInput.addEventListener('blur', updateCalculatedAge);
        dobInput.addEventListener('keyup', updateCalculatedAge);
        dobInput.addEventListener('paste', () => setTimeout(updateCalculatedAge, 50));
        updateCalculatedAge();
    }

    // Joining Date & Service auto-calc
    const joinInput = document.getElementById('joiningDateInput');
    const serviceInput = document.getElementById('calcServiceMonthsInput');
    if (joinInput) {
        joinInput.addEventListener('change', function () {
            if (this.value) {
                const jd = new Date(this.value);
                const now = new Date();
                const months = (now.getFullYear() - jd.getFullYear()) * 12 + (now.getMonth() - jd.getMonth());
                serviceInput.value = Math.max(0, months) + ' Mos';
            }
        });
    }

    // Divyaang Toggle
    const divyaangSwitch = document.getElementById('divyaangSwitch');
    const disabilityBox = document.getElementById('disabilityDetailsBox');
    if (divyaangSwitch) {
        divyaangSwitch.addEventListener('change', function () {
            disabilityBox.classList.toggle('d-none', !this.checked);
        });
    }

    // Onboarding Channel Toggle
    const channelSelect = document.getElementById('onboardingChannelSelect');
    const contractorCol = document.getElementById('contractorVendorCol');
    if (channelSelect) {
        channelSelect.addEventListener('change', function () {
            if (this.value === 'Contractor') {
                contractorCol.classList.remove('d-none');
            } else {
                contractorCol.classList.add('d-none');
            }
        });
    }

    // Permanent address sync
    const sameCheckbox = document.getElementById('sameAddressCheckbox');
    if (sameCheckbox) {
        sameCheckbox.addEventListener('change', function () {
            if (this.checked) {
                document.getElementById('perm_flat').value = document.getElementById('curr_flat').value;
                document.getElementById('perm_street').value = document.getElementById('curr_street').value;
                document.getElementById('perm_area').value = document.getElementById('curr_area').value;
                document.getElementById('perm_city').value = document.getElementById('curr_city').value;
                document.getElementById('perm_dist').value = document.getElementById('curr_dist').value;
                document.getElementById('perm_state').value = document.getElementById('curr_state').value;
                document.getElementById('perm_pin').value = document.getElementById('curr_pin').value;
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
                    photoImg.src = ev.target.result;
                    photoImg.style.display = 'block';
                    if (photoIcon) photoIcon.style.display = 'none';
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
});
</script>
@endpush
@endsection
