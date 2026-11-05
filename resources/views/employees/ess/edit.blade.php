@extends('layouts.app')

@section('title', 'Edit My Profile — ESS')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.employee') }}">ESS Portal</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ess.profile.show') }}">My Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Permitted Fields</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Update Self-Service Profile</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Keep your contact information, residential addresses, emergency contacts and identity records up to date.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('ess.profile.show') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i>
            <span>Back to My Profile</span>
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
            <div>
                <strong>Please resolve the following errors:</strong>
                <ul class="mb-0 mt-1 ps-3" style="font-size: 13px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    $addr = $employee->addressRecord;
    $stat = $employee->statutory;
    $bank = $employee->bankDetail;
    $visa = $employee->visaDetail;
@endphp

<form method="POST" action="{{ route('ess.profile.update') }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-lg-8">

            <!-- Card 1: Personal & Demographic -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-person me-2 text-primary"></i>1. Personal Profile Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Display Name</label>
                            <input type="text" name="display_name" class="form-control form-control-sm" value="{{ old('display_name', $employee->display_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Also Known As (AKA)</label>
                            <input type="text" name="aka_name" class="form-control form-control-sm" value="{{ old('aka_name', $employee->aka_name) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Blood Group</label>
                            <select name="blood_group" class="form-select form-select-sm">
                                <option value="">Select Blood Group</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                    <option value="{{ $bg }}" {{ old('blood_group', $employee->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Marital Status</label>
                            <select name="marital_status" class="form-select form-select-sm">
                                <option value="">Select Status</option>
                                @foreach(['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $ms)
                                    <option value="{{ $ms }}" {{ old('marital_status', $employee->marital_status) === $ms ? 'selected' : '' }}>{{ $ms }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Marriage Anniversary</label>
                            <input type="date" name="marriage_date" class="form-control form-control-sm" value="{{ old('marriage_date', $employee->marriage_date?->format('Y-m-d')) }}">
                        </div>

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

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Visible Identification Mark</label>
                            <input type="text" name="identification_mark" class="form-control form-control-sm" value="{{ old('identification_mark', $employee->identification_mark) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Profile Photo</label>
                            <input type="file" name="profile_photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Contact Information -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-telephone me-2 text-primary"></i>2. Contact Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Personal Mobile Phone</label>
                            <input type="tel" name="personal_phone" class="form-control form-control-sm" value="{{ old('personal_phone', $employee->personal_phone ?? $employee->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Residential Phone</label>
                            <input type="tel" name="residential_phone" class="form-control form-control-sm" value="{{ old('residential_phone', $employee->residential_phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Alternate / Personal Email</label>
                            <input type="email" name="alternate_email" class="form-control form-control-sm" value="{{ old('alternate_email', $employee->alternate_email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">CC Email (Notifications)</label>
                            <input type="email" name="cc_email" class="form-control form-control-sm" value="{{ old('cc_email', $employee->cc_email) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Address Information -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-geo-alt me-2 text-primary"></i>3. Residential Addresses</h6>
                </div>
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-primary mb-3 fw-bold" style="font-size: 11.5px;">Present Residence</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Flat / House No</label>
                            <input type="text" name="curr_flat" id="curr_flat" class="form-control form-control-sm" value="{{ old('curr_flat', $addr?->current_flat) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Street / Landmark</label>
                            <input type="text" name="curr_street" id="curr_street" class="form-control form-control-sm" value="{{ old('curr_street', $addr?->current_street) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Area / Locality</label>
                            <input type="text" name="curr_area" id="curr_area" class="form-control form-control-sm" value="{{ old('curr_area', $addr?->current_area) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">City</label>
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
                        <div class="fw-bold" style="font-size: 13px;">Permanent Domicile Address</div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="permanent_same_as_current" value="1" id="sameAddressCheckbox" {{ old('permanent_same_as_current', $addr?->permanent_same_as_current) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="sameAddressCheckbox" style="font-size: 12.5px;">Same as Present Address</label>
                        </div>
                    </div>

                    <div class="row g-3" id="permAddrRow">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Flat / House No</label>
                            <input type="text" name="perm_flat" id="perm_flat" class="form-control form-control-sm" value="{{ old('perm_flat', $addr?->permanent_flat) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Street / Landmark</label>
                            <input type="text" name="perm_street" id="perm_street" class="form-control form-control-sm" value="{{ old('perm_street', $addr?->permanent_street) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Area / Locality</label>
                            <input type="text" name="perm_area" id="perm_area" class="form-control form-control-sm" value="{{ old('perm_area', $addr?->permanent_area) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">City</label>
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
            </div>

            <!-- Card 4: Emergency Contact -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-heart-pulse me-2 text-danger"></i>4. Primary Emergency Contact</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="emergency_contact_name" class="form-control form-control-sm" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Emergency Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="emergency_contact_phone" class="form-control form-control-sm" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Relationship</label>
                            <input type="text" name="emergency_contact_relation" class="form-control form-control-sm" placeholder="e.g. Spouse, Brother, Father" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 5: Bank Disbursement Information -->
            <div class="card card-custom mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-bank me-2 text-primary"></i>5. Salary Disbursement Bank Account</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control form-control-sm" value="{{ old('bank_name', $bank?->bank_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Branch</label>
                            <input type="text" name="bank_branch" class="form-control form-control-sm" value="{{ old('bank_branch', $bank?->bank_branch) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control form-control-sm text-uppercase" value="{{ old('bank_ifsc', $bank?->bank_ifsc) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Account Number</label>
                            <input type="text" name="bank_account_number" class="form-control form-control-sm" placeholder="Full account number" value="{{ old('bank_account_number', $bank?->bank_account_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size: 12.5px;">Name on Bank Passbook / Cheque</label>
                            <input type="text" name="bank_passbook_name" class="form-control form-control-sm" value="{{ old('bank_passbook_name', $bank?->bank_passbook_name) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('ess.profile.show') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-save2 me-1"></i> Save Changes
                </button>
            </div>
        </div>

        <!-- Right Side: Read-Only Info & Submission Help -->
        <div class="col-lg-4">
            <div class="card card-custom sticky-top" style="top: 85px;">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-shield-lock me-2 text-secondary"></i>HR Managed Attributes</h6>
                </div>
                <div class="card-body p-3" style="font-size: 13px;">
                    <div class="alert alert-light border py-2 px-3 mb-3 text-muted" style="font-size: 12px;">
                        The following core employment attributes are managed centrally by HR Operations and cannot be altered via self-service:
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 45%;">Employee Code</td>
                            <td class="code-font fw-bold">{{ $employee->employee_code }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Legal Entity</td>
                            <td>{{ $employee->company->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Department</td>
                            <td>{{ $employee->department?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Designation</td>
                            <td>{{ $employee->designation?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Work Site</td>
                            <td>{{ $employee->location?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Date of Joining</td>
                            <td>{{ $employee->joining_date ? $employee->joining_date->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Employment Status</td>
                            <td><span class="badge bg-success-subtle text-success border">{{ $employee->employment_status }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gate Pass Number</td>
                            <td class="code-font">{{ $employee->gate_pass_number ?? '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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
});
</script>
@endpush
@endsection

