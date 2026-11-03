@extends('layouts.app')

@section('title', 'Create Leave Type')

@section('content')
<div class="mb-4">
    <a href="{{ route('leaves.types.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Leave Types
    </a>
    <h1 class="h4 mb-1 text-slate-900 font-bold mt-2">New Leave Type Configuration</h1>
    <p class="text-muted small mb-0">Define leave quota, accrual schedule, and policy constraints.</p>
</div>

<div class="card card-custom">
    <div class="card-body p-4">
        <form action="{{ route('leaves.types.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Leave Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="e.g. CL, SL, PL" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Leave Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Casual Leave" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select">
                        <option value="Active" {{ old('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Policy details, eligibility summary...">{{ old('description') }}</textarea>
                </div>
            </div>

            <h6 class="text-slate-800 fw-bold border-bottom pb-2 mb-3">Entitlement & Accrual Engine</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Annual Entitlement (Days) <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" name="annual_entitlement" value="{{ old('annual_entitlement', '12.00') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Monthly Accrual Rate (Days) <span class="text-danger">*</span></label>
                    <input type="number" step="0.25" name="monthly_accrual_rate" value="{{ old('monthly_accrual_rate', '1.00') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Accrual Frequency</label>
                    <select name="accrual_frequency" class="form-select">
                        <option value="Monthly" {{ old('accrual_frequency') === 'Monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="Quarterly" {{ old('accrual_frequency') === 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="Yearly" {{ old('accrual_frequency') === 'Yearly' ? 'selected' : '' }}>Yearly</option>
                        <option value="None" {{ old('accrual_frequency') === 'None' ? 'selected' : '' }}>None (Lump-Sum)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Accrual Timing</label>
                    <select name="accrual_timing" class="form-select">
                        <option value="Start of Month" {{ old('accrual_timing') === 'Start of Month' ? 'selected' : '' }}>Start of Month (Advance)</option>
                        <option value="End of Month" {{ old('accrual_timing') === 'End of Month' ? 'selected' : '' }}>End of Month (Earned)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="is_paid" {{ old('is_paid', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_paid">Paid Leave</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_accrual_enabled" value="1" id="is_accrual_enabled" {{ old('is_accrual_enabled', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_accrual_enabled">Enable Monthly Accrual</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="requires_confirmation" value="1" id="requires_confirmation" {{ old('requires_confirmation') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="requires_confirmation">Requires Confirmation</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_compoff" value="1" id="is_compoff" {{ old('is_compoff') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_compoff">Is Comp-Off Type</label>
                    </div>
                </div>
            </div>

            <h6 class="text-slate-800 fw-bold border-bottom pb-2 mb-3">Sandwich & Constraint Rules</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Max Consecutive Days</label>
                    <input type="number" name="max_consecutive_days" value="{{ old('max_consecutive_days', 0) }}" class="form-control" placeholder="0 = Unlimited">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Min Advance Notice (Days)</label>
                    <input type="number" name="min_notice_days" value="{{ old('min_notice_days', 0) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Max Backdated Days</label>
                    <input type="number" name="max_backdated_days" value="{{ old('max_backdated_days', 0) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_sandwich_enabled" value="1" id="is_sandwich_enabled" {{ old('is_sandwich_enabled', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_sandwich_enabled">Apply Sandwich Rule</label>
                    </div>
                </div>
            </div>

            <h6 class="text-slate-800 fw-bold border-bottom pb-2 mb-3">Carry Forward & Notice Period</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_carry_forward_enabled" value="1" id="is_carry_forward_enabled" {{ old('is_carry_forward_enabled') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_carry_forward_enabled">Enable Carry-Forward</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Carry-Forward Limit (Days)</label>
                    <input type="number" step="0.5" name="carry_forward_limit" value="{{ old('carry_forward_limit', '0.00') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_applicable_in_notice_period" value="1" id="is_applicable_in_notice_period" {{ old('is_applicable_in_notice_period') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_applicable_in_notice_period">Permitted in Notice Period</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" value="{{ old('effective_from', date('Y-01-01')) }}" class="form-control" required>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('leaves.types.index') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-accent px-4">Save Leave Type</button>
            </div>
        </form>
    </div>
</div>
@endsection

