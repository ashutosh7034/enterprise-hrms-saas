@extends('layouts.app')

@section('title', "Edit {$employee->full_name}")

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Workforce</a></li>
                <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee->id) }}">{{ $employee->full_name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Profile</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Edit Employee Dossier</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Update personal, organizational, and employment status details.</p>
    </div>
    <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dossier
    </a>
</div>

<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span style="font-weight: 600;">Employee Information • <span class="code-font">{{ $employee->employee_code }}</span></span>
        <span class="badge bg-light text-dark border">{{ $activeCompany->name }}</span>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('employees.update', $employee->id) }}" autocomplete="off">
            @csrf
            @method('PUT')

            <!-- Prevent browser credential manager auto-filling administrator credentials -->
            <input type="text" style="display:none" aria-hidden="true" tabindex="-1">
            <input type="password" style="display:none" aria-hidden="true" tabindex="-1">

            <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                1. Personal Details
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $employee->first_name) }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $employee->middle_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $employee->last_name) }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="Male" {{ old('gender', $employee->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $employee->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $employee->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight: 500;">Email Address</label>
                    <input type="email" name="email" id="edit_candidate_email" class="form-control" value="{{ old('email', $employee->email) }}" autocomplete="off" autocapitalize="none" spellcheck="false">
                </div>
            </div>

            <hr class="my-4" style="border-color: #E2E8F0;">

            <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                2. Organizational Assignment
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Date of Joining <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Work Location / Site <span class="text-danger">*</span></label>
                    <select name="location_id" class="form-select" required>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id', $employee->location_id) == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ $loc->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Department <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select" required>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Designation <span class="text-danger">*</span></label>
                    <select name="designation_id" class="form-select" required>
                        @foreach($designations as $desig)
                            <option value="{{ $desig->id }}" {{ old('designation_id', $employee->designation_id) == $desig->id ? 'selected' : '' }}>
                                {{ $desig->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Category <span class="text-danger">*</span></label>
                    <select name="employee_category_id" class="form-select" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('employee_category_id', $employee->employee_category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Employment Status <span class="text-danger">*</span></label>
                    <select name="employment_status" class="form-select" required>
                        <option value="Active" {{ old('employment_status', $employee->employment_status) === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Probation" {{ old('employment_status', $employee->employment_status) === 'Probation' ? 'selected' : '' }}>Probation</option>
                        <option value="Notice Period" {{ old('employment_status', $employee->employment_status) === 'Notice Period' ? 'selected' : '' }}>Notice Period</option>
                        <option value="Resigned" {{ old('employment_status', $employee->employment_status) === 'Resigned' ? 'selected' : '' }}>Resigned</option>
                        <option value="Terminated" {{ old('employment_status', $employee->employment_status) === 'Terminated' ? 'selected' : '' }}>Terminated</option>
                        <option value="Suspended" {{ old('employment_status', $employee->employment_status) === 'Suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
            </div>

            <hr class="my-4" style="border-color: #E2E8F0;">

            <h6 class="text-uppercase text-muted mb-3" style="font-size: 11px; letter-spacing: 0.05em; font-weight: 700;">
                3. Address Information
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label" style="font-weight: 500;">Street Address</label>
                    <textarea name="address" rows="2" class="form-control">{{ old('address', $employee->address) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $employee->city) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state', $employee->state) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 500;">Pincode</label>
                    <input type="text" name="pincode" class="form-control code-font" value="{{ old('pincode', $employee->pincode) }}">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-brand px-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

