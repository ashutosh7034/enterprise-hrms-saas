@extends('layouts.app')

@section('title', 'Create Sandwich Leave Policy')

@section('content')
<div class="mb-4">
    <a href="{{ route('leaves.sandwich-policies.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Sandwich Policies
    </a>
    <h1 class="h4 mb-1 text-slate-900 font-bold mt-2">New Sandwich Policy Rule</h1>
    <p class="text-muted small mb-0">Specify sandwich conditions, priority, and intervening off-day inclusions.</p>
</div>

<div class="card card-custom">
    <div class="card-body p-4">
        <form action="{{ route('leaves.sandwich-policies.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Policy Name <span class="text-danger">*</span></label>
                    <input type="text" name="policy_name" value="{{ old('policy_name') }}" class="form-control" placeholder="e.g. Standard Weekend Sandwich Rule" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Leave Type</label>
                    <select name="leave_type_id" class="form-select">
                        <option value="">All Leave Types (Global)</option>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}" {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Priority (1-100) <span class="text-danger">*</span></label>
                    <input type="number" name="priority" value="{{ old('priority', 1) }}" class="form-control" min="1" max="100" required>
                    <div class="text-muted small">Higher priority overrides lower ones</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Specific Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Specific Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Specific Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="is_enabled" {{ old('is_enabled', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_enabled">Policy Enabled</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="include_intervening_weekly_offs" value="1" id="include_intervening_weekly_offs" {{ old('include_intervening_weekly_offs', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="include_intervening_weekly_offs">Include Intervening Weekly Offs as Leave</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="include_intervening_holidays" value="1" id="include_intervening_holidays" {{ old('include_intervening_holidays', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="include_intervening_holidays">Include Intervening Holidays as Leave</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" value="{{ old('effective_from', date('Y-01-01')) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Effective To</label>
                    <input type="date" name="effective_to" value="{{ old('effective_to') }}" class="form-control">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('leaves.sandwich-policies.index') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-accent px-4">Save Policy</button>
            </div>
        </form>
    </div>
</div>
@endsection

