@extends('layouts.app')

@section('title', 'Create Shift Schedule — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('shifts.index') }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Shift Master
    </a>
    <h1 class="h3 mt-2 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Create Shift Schedule</h1>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Define shift working windows, grace buffer, and attendance evaluation thresholds.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-body p-4">
                <form action="{{ route('shifts.store') }}" method="POST">
                    @csrf

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">General Shift Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Shift Code</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. S-GEN, S-NIGHT" required>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Shift Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. General Plant Shift (09:00 - 18:00)" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Timings & Overnight Configuration</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Start Time</label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', '09:00') }}" required>
                            @error('start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">End Time</label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', '18:00') }}" required>
                            @error('end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_overnight" id="is_overnight" value="1" {{ old('is_overnight') ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="is_overnight">Cross-Day / Overnight Shift (+1 Day)</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Duration, Breaks & Grace Parameters</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label required">Break Duration (Mins)</label>
                            <input type="number" name="break_duration_minutes" class="form-control" value="{{ old('break_duration_minutes', 45) }}" min="0" max="180" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Grace Period (Mins)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" value="{{ old('grace_period_minutes', 15) }}" min="0" max="120" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Half-Day Threshold (Mins)</label>
                            <input type="number" name="half_day_threshold_minutes" class="form-control" value="{{ old('half_day_threshold_minutes', 240) }}" min="60" max="480" required>
                            <small class="text-muted">4 hours = 240m</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Full-Day Threshold (Mins)</label>
                            <input type="number" name="full_day_threshold_minutes" class="form-control" value="{{ old('full_day_threshold_minutes', 480) }}" min="120" max="960" required>
                            <small class="text-muted">8 hours = 480m</small>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Weekly Offs & Scope Application</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Default Weekly Off Days</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="weekly_off_days[]" value="{{ $day }}" id="day_{{ $day }}" {{ $day === 'Sunday' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day_{{ $day }}">{{ substr($day, 0, 3) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plant Location / Site Scope (Optional)</label>
                            <select name="location_id" class="form-select">
                                <option value="">All Company Locations (Default)</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department Scope (Optional)</label>
                            <select name="department_id" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee Category Scope (Optional)</label>
                            <select name="category_id" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Validity & Status</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Effective From</label>
                            <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Effective To (Optional)</label>
                            <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active" {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Save Shift Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

