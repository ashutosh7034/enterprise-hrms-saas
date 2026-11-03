@extends('layouts.app')

@section('title', 'Onboard Candidate')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Workforce</a></li>
                <li class="breadcrumb-item active" aria-current="page">New Candidate Onboarding</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Workforce Onboarding Form</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Register a new direct or contractor workforce member with automated cross-company blacklist & duplicate checks.</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Roster
    </a>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Candidate Dossier Registration</span>
                <span class="badge bg-secondary-subtle text-secondary border float-end">Entity: {{ $activeCompany->name }} ({{ $activeCompany->code }})</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('employees.store') }}" id="onboardingForm" autocomplete="off">
                    @csrf

                    <!-- Prevent browser credential manager auto-filling administrator credentials into candidate fields -->
                    <input type="text" style="display:none" aria-hidden="true" tabindex="-1">
                    <input type="password" style="display:none" aria-hidden="true" tabindex="-1">

                    <!-- Section 1: Personal Details -->
                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                        1. Personal Details
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name') }}">
                            @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Date of Birth (Min 18 Yrs) <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" max="{{ now()->subYears(18)->format('Y-m-d') }}" required>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="10-digit mobile number" value="{{ old('phone') }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Email Address</label>
                            <input type="email" name="email" id="candidate_email" class="form-control @error('email') is-invalid @enderror" placeholder="candidate@example.com" value="{{ old('email', '') }}" autocomplete="off" autocapitalize="none" spellcheck="false">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: #E2E8F0;">

                    <!-- Section 2: Statutory Identity & Compliance -->
                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                        2. Statutory Compliance & Tokens (UIDAI & Income Tax)
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Aadhaar Number (12 Digits)</label>
                            <input type="text" name="aadhaar_number" id="candidate_aadhaar" maxlength="14" class="form-control code-font @error('aadhaar_number') is-invalid @enderror" placeholder="1234 5678 9012" value="{{ old('aadhaar_number', '') }}" autocomplete="off" style="-webkit-text-security: disc;">
                            <div class="form-text" style="font-size: 11.5px; color: #64748B;">
                                <i class="bi bi-shield-lock me-1"></i> Never stored raw. Automatically converted to SHA-256 token and masked (XXXX-XXXX-1234).
                            </div>
                            @error('aadhaar_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">PAN Number</label>
                            <input type="text" name="pan_number" id="candidate_pan" maxlength="10" class="form-control code-font text-uppercase @error('pan_number') is-invalid @enderror" placeholder="ABCDE1234F" value="{{ old('pan_number', '') }}" autocomplete="off">
                            <div class="form-text" style="font-size: 11.5px; color: #64748B;">
                                Standard 10-character alphanumeric Indian PAN format.
                            </div>
                            @error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: #E2E8F0;">

                    <!-- Section 3: Job Placement & Department -->
                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                        3. Job Placement & Categorization
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Date of Joining <span class="text-danger">*</span></label>
                            <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror" value="{{ old('joining_date', now()->format('Y-m-d')) }}" required>
                            @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Work Location / Site <span class="text-danger">*</span></label>
                            <select name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->code }}) {{ $loc->is_ground_staff ? '• Ground Staff Site' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Designation <span class="text-danger">*</span></label>
                            <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                                <option value="">Select Designation</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>
                                        {{ $desig->name }} (Level: {{ $desig->level ?? 'Standard' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Employee Category <span class="text-danger">*</span></label>
                            <select name="employee_category_id" class="form-select @error('employee_category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('employee_category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }} ({{ $cat->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Onboarding Channel <span class="text-danger">*</span></label>
                            <select name="onboarding_channel" id="onboarding_channel" class="form-select @error('onboarding_channel') is-invalid @enderror" required onchange="toggleContractorField()">
                                <option value="Direct" {{ old('onboarding_channel', 'Direct') === 'Direct' ? 'selected' : '' }}>Direct</option>
                                <option value="Contractor" {{ old('onboarding_channel') === 'Contractor' ? 'selected' : '' }}>Contractor</option>
                                <option value="Referral" {{ old('onboarding_channel') === 'Referral' ? 'selected' : '' }}>Referral</option>
                                <option value="Agency" {{ old('onboarding_channel') === 'Agency' ? 'selected' : '' }}>Agency</option>
                            </select>
                            @error('onboarding_channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6" id="contractor_wrapper" style="display: {{ old('onboarding_channel') === 'Contractor' ? 'block' : 'none' }};">
                            <label class="form-label" style="font-weight: 500;">Contractor / Agency Vendor <span class="text-danger">*</span></label>
                            <select name="contractor_id" class="form-select @error('contractor_id') is-invalid @enderror">
                                <option value="">Select Contractor Master</option>
                                @foreach($contractors as $con)
                                    <option value="{{ $con->id }}" {{ old('contractor_id') == $con->id ? 'selected' : '' }}>
                                        {{ $con->name }} (Code: {{ $con->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('contractor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: #E2E8F0;">

                    <!-- Section 4: Physical Access & Gate Pass -->
                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                        4. Physical Site Access & Gate Pass
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Gate Pass Number</label>
                            <input type="text" name="gate_pass_number" class="form-control code-font @error('gate_pass_number') is-invalid @enderror" placeholder="e.g. GP-2026-0045" value="{{ old('gate_pass_number') }}">
                            <div class="form-text" style="font-size: 11.5px; color: #64748B;">
                                Optional at onboarding. Must be unique per company. Full audit history tracked.
                            </div>
                            @error('gate_pass_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: #E2E8F0;">

                    <!-- Section 5: Address -->
                    <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                        5. Permanent Address
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label" style="font-weight: 500;">Street Address</label>
                            <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Pincode</label>
                            <input type="text" name="pincode" maxlength="10" class="form-control code-font" value="{{ old('pincode') }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">
                            <i class="bi bi-check2-circle me-1"></i> Complete Onboarding Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Security & Traceability Info Sidebar -->
    <div class="col-lg-3">
        <div class="card card-custom mb-3">
            <div class="card-header bg-white" style="font-size: 13px; font-weight: 600;">
                <i class="bi bi-shield-check text-success me-1"></i> Trust & Compliance Guards
            </div>
            <div class="card-body p-3" style="font-size: 12.5px; color: #475569;">
                <div class="mb-3">
                    <strong class="d-block text-dark">Central Blacklist Scan</strong>
                    On submission, candidate identity is evaluated against the multi-company central blacklist. Exact PAN or Aadhaar matches result in immediate onboarding halt.
                </div>
                <div class="mb-3">
                    <strong class="d-block text-dark">Duplicate Detection</strong>
                    Ensures an active employee cannot be onboarded twice within {{ $activeCompany->code }}.
                </div>
                <div class="mb-3">
                    <strong class="d-block text-dark">Auto-Generated Code</strong>
                    Sequential code (e.g. <code>{{ $activeCompany->code }}-EMP-1001</code>) generated automatically in transaction.
                </div>
                <div>
                    <strong class="d-block text-dark">eKYC Lifecycle</strong>
                    Initialized with <code>Pending Verification</code>. 14-day grace period is provided if first verification fails.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleContractorField() {
        const channel = document.getElementById('onboarding_channel').value;
        const wrapper = document.getElementById('contractor_wrapper');
        if (channel === 'Contractor') {
            wrapper.style.display = 'block';
        } else {
            wrapper.style.display = 'none';
        }
    }
</script>
@endsection

