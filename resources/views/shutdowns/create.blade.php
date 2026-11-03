@extends('layouts.app')

@section('title', 'Request Plant Shutdown — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('plant-shutdowns.index') }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Shutdown List
    </a>
    <h1 class="h3 mt-2 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Request Plant / Site Shutdown</h1>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Specify location, department or worker category scope and compensation policy.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-body p-4">
                <form action="{{ route('plant-shutdowns.store') }}" method="POST">
                    @csrf

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Shutdown Classification & Scope</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label required">Shutdown Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. Annual Blast Furnace Overhaul & Refractory Maintenance" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Shutdown Type</label>
                            <select name="shutdown_type" class="form-select" required>
                                <option value="Planned Maintenance">Planned Maintenance</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Power Failure">Power Failure</option>
                                <option value="Raw Material Shortage">Raw Material Shortage</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Target Plant Location / Site</label>
                            <select name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                                <option value="">-- Select Location --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Specific Department (Optional)</label>
                            <select name="department_id" class="form-select">
                                <option value="">All Departments at Site</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employee Category (Optional)</label>
                            <select name="category_id" class="form-select">
                                <option value="">All Worker Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Timeline & Time Windows</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Start Date</label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->toDateString()) }}" required>
                            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">End Date</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->toDateString()) }}" required>
                            @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Time (Optional)</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time (Optional)</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Compensation Policy & Reason</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Employee Compensation Rule</label>
                            <select name="compensation_rule" class="form-select" required>
                                <option value="Paid Shutdown">Paid Shutdown (Normal Wages Retained)</option>
                                <option value="Adjust Against Leave">Adjust Against Leave Balance</option>
                                <option value="Unpaid Shutdown">Unpaid Shutdown</option>
                                <option value="Comp-Off for Duty">Comp-Off Credit for Duty</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label required">Reason & Technical Justification</label>
                            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" placeholder="Provide detailed explanation of the shutdown reasons and safety requirements..." required>{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('plant-shutdowns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Submit Shutdown Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

