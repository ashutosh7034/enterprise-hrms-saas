@extends('layouts.platform')

@section('title', 'Create Subscription Plan | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.plans.index') }}" class="text-decoration-none">Subscription Plans</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create Plan</li>
        </ol>
    </nav>
    <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Create Commercial Subscription Plan</h3>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Define tier pricing, employee quotas, company limits, and module feature entitlements.</p>
</div>

<div class="row">
    <div class="col-lg-10">
        <div class="platform-card p-4">
            <form method="POST" action="{{ route('platform.plans.store') }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Plan Code <span class="text-danger">*</span></label>
                        <input type="text" name="plan_code" class="form-control @error('plan_code') is-invalid @enderror" value="{{ old('plan_code') }}" placeholder="e.g. PLAN-ENTERPRISE" required>
                        <div class="form-text small">Unique alphanumeric code (auto-uppercased).</div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-dark">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Enterprise Tier" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Available for Assignment)</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" placeholder="Brief marketing summary of this plan tier...">{{ old('description') }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack text-primary me-2"></i>Pricing & Quotas</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', 0) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', 'INR') }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Billing Interval <span class="text-danger">*</span></label>
                        <select name="billing_interval" class="form-select @error('billing_interval') is-invalid @enderror" required>
                            <option value="yearly" {{ old('billing_interval', 'yearly') === 'yearly' ? 'selected' : '' }}>Yearly (Annual)</option>
                            <option value="quarterly" {{ old('billing_interval') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="monthly" {{ old('billing_interval') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Employee Seat Quota <span class="text-danger">*</span></label>
                        <input type="number" name="employee_limit" class="form-control @error('employee_limit') is-invalid @enderror" value="{{ old('employee_limit', 500) }}" required min="1">
                        <div class="form-text small">Maximum total active headcount allowed across all group companies.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Legal Company Quota <span class="text-danger">*</span></label>
                        <input type="number" name="company_limit" class="form-control @error('company_limit') is-invalid @enderror" value="{{ old('company_limit', 5) }}" required min="1">
                        <div class="form-text small">Maximum legal entities permitted under this customer tenant.</div>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Module Feature Entitlements</h5>
                <p class="text-muted small mb-3">Select which HRMS modules are licensed under this tier. Requests by tenants without the module will be rejected with an entitlement error.</p>

                <div class="row g-3 mb-4">
                    @foreach($features as $code => $name)
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="features[]" value="{{ $code }}" id="feature_{{ $code }}" {{ in_array($code, old('features', array_keys($features))) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="feature_{{ $code }}">
                                    {{ $name }}
                                    <div class="text-muted fw-normal small">Module code: <code>{{ $code }}</code></div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex align-items-center gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">Create Subscription Plan</button>
                    <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

