@extends('layouts.app')

@section('title', 'Propose Blacklist Entry')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('blacklist.index') }}">Central Blacklist</a></li>
                <li class="breadcrumb-item active" aria-current="page">Propose Entry</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Propose Central Blacklist Record</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Submit an individual for inclusion on the cross-company blacklist. Requires HO Admin approval.</p>
    </div>
    <a href="{{ route('blacklist.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Blacklist
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Incident & Individual Details</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('blacklist.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Full Legal Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Full name of individual" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">PAN Number</label>
                            <input type="text" name="pan_number" maxlength="10" class="form-control text-uppercase code-font @error('pan_number') is-invalid @enderror" placeholder="ABCDE1234F" value="{{ old('pan_number') }}">
                            @error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Aadhaar Number (12 Digits)</label>
                            <input type="password" name="aadhaar_number" maxlength="14" class="form-control code-font @error('aadhaar_number') is-invalid @enderror" placeholder="1234 5678 9012" value="{{ old('aadhaar_number') }}">
                            <div class="form-text" style="font-size: 11px;">Will be converted to SHA-256 token and masked.</div>
                            @error('aadhaar_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Mobile Phone</label>
                            <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Mobile number" value="{{ old('phone') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Prior Associated Entity</label>
                            <select name="previous_company_id" class="form-select @error('previous_company_id') is-invalid @enderror">
                                <option value="">Select Entity (if applicable)</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}" {{ old('previous_company_id') == $comp->id ? 'selected' : '' }}>
                                        {{ $comp->name }} ({{ $comp->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('previous_company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Prior Employee / Gate Pass Code</label>
                            <input type="text" name="previous_employee_code" class="form-control code-font" placeholder="e.g. VE-EMP-0012 or GP-348" value="{{ old('previous_employee_code') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Reason Category <span class="text-danger">*</span></label>
                            <select name="reason_category" class="form-select @error('reason_category') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                <option value="Serious Misconduct" {{ old('reason_category') === 'Serious Misconduct' ? 'selected' : '' }}>Serious Misconduct</option>
                                <option value="Financial / Asset Misconduct" {{ old('reason_category') === 'Financial / Asset Misconduct' ? 'selected' : '' }}>Financial / Asset Misconduct</option>
                                <option value="Absconding" {{ old('reason_category') === 'Absconding' ? 'selected' : '' }}>Absconding</option>
                                <option value="Policy Termination" {{ old('reason_category') === 'Policy Termination' ? 'selected' : '' }}>Policy Termination</option>
                                <option value="Fraudulent Documents" {{ old('reason_category') === 'Fraudulent Documents' ? 'selected' : '' }}>Fraudulent Documents</option>
                                <option value="Identity Fraud" {{ old('reason_category') === 'Identity Fraud' ? 'selected' : '' }}>Identity Fraud</option>
                                <option value="Other" {{ old('reason_category') === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('reason_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 500;">Detailed Incident Notes & Evidence <span class="text-danger">*</span></label>
                        <textarea name="evidence_notes" rows="4" class="form-control @error('evidence_notes') is-invalid @enderror" placeholder="Describe specific violation events, dates, police complaint details, stolen asset codes, or inquiry committee findings..." required minlength="10">{{ old('evidence_notes') }}</textarea>
                        @error('evidence_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('blacklist.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-shield-slash me-1"></i> Submit for HO Authorization
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header bg-white" style="font-weight: 600;">
                <i class="bi bi-shield-lock me-1 text-danger"></i> Compliance Workflow
            </div>
            <div class="card-body" style="font-size: 13px; color: #475569;">
                <p>
                    <strong>1. Cross-Company Enforcement:</strong> Once approved by the Head Office Admin, this entry will block candidate onboarding across all current and future group entities.
                </p>
                <p>
                    <strong>2. Dual-Layer Matching:</strong> Exact PAN or Aadhaar tokens trigger automated hard halts. Name & DOB similarities trigger potential match review queues.
                </p>
                <p class="mb-0">
                    <strong>3. Whitelisting:</strong> De-listing requires authorized executive clearance with mandatory recorded justification. Records remain permanently in the audit ledger.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

