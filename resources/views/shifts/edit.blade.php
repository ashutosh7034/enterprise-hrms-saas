@extends('layouts.app')

@section('title', 'Edit Shift Schedule — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('shifts.index') }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Shift Master
    </a>
    <h1 class="h3 mt-2 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Edit Shift: {{ $shift->name }}</h1>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Update shift timings, grace buffer and attendance thresholds.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-body p-4">
                <form action="{{ route('shifts.update', $shift->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">General Shift Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Shift Code</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $shift->code) }}" required>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Shift Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $shift->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Timings & Overnight Configuration</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', substr($shift->start_time, 0, 5)) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', substr($shift->end_time, 0, 5)) }}" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_overnight" id="is_overnight" value="1" {{ old('is_overnight', $shift->is_overnight) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="is_overnight">Cross-Day / Overnight Shift</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Duration, Breaks & Grace Parameters</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label required">Break (Mins)</label>
                            <input type="number" name="break_duration_minutes" class="form-control" value="{{ old('break_duration_minutes', $shift->break_duration_minutes) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Grace (Mins)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" value="{{ old('grace_period_minutes', $shift->grace_period_minutes) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Half-Day (Mins)</label>
                            <input type="number" name="half_day_threshold_minutes" class="form-control" value="{{ old('half_day_threshold_minutes', $shift->half_day_threshold_minutes) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Full-Day (Mins)</label>
                            <input type="number" name="full_day_threshold_minutes" class="form-control" value="{{ old('full_day_threshold_minutes', $shift->full_day_threshold_minutes) }}" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Weekly Offs & Scope Application</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Weekly Off Days</label>
                            <div class="d-flex flex-wrap gap-2">
                                @php $offs = (array) $shift->weekly_off_days; @endphp
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="weekly_off_days[]" value="{{ $day }}" id="day_{{ $day }}" {{ in_array($day, $offs) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day_{{ $day }}">{{ substr($day, 0, 3) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location / Site Scope</label>
                            <select name="location_id" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id', $shift->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department Scope</label>
                            <select name="department_id" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $shift->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee Category Scope</label>
                            <select name="category_id" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $shift->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Validity & Status</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required">Effective From</label>
                            <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $shift->effective_from?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Effective To</label>
                            <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to', $shift->effective_to?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="Active" {{ old('status', $shift->status) === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ old('status', $shift->status) === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

