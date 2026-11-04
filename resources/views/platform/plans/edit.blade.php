@extends('layouts.platform')

@section('title', 'Edit Plan ' . $plan->name . ' | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.plans.index') }}" class="text-decoration-none">Subscription Plans</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit {{ $plan->name }}</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <h3 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.02em;">Edit Plan: {{ $plan->name }}</h3>
        <span class="code-pill fs-6">{{ $plan->plan_code }}</span>
    </div>
</div>

<div class="row">
    <div class="col-lg-10">
        <div class="platform-card p-4">
            <form method="POST" action="{{ route('platform.plans.update', $plan) }}">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Plan Code</label>
                        <input type="text" class="form-control bg-light" value="{{ $plan->plan_code }}" disabled readonly>
                        <div class="form-text small">Immutable identifier.</div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-dark">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-dark">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $plan->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $plan->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $plan->description) }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack text-primary me-2"></i>Pricing & Quotas</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Price <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $plan->price) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Currency <span class="text-danger">*</span></label>
                        <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $plan->currency) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Billing Interval <span class="text-danger">*</span></label>
                        <select name="billing_interval" class="form-select @error('billing_interval') is-invalid @enderror" required>
                            <option value="yearly" {{ old('billing_interval', $plan->billing_interval) === 'yearly' ? 'selected' : '' }}>Yearly (Annual)</option>
                            <option value="quarterly" {{ old('billing_interval', $plan->billing_interval) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="monthly" {{ old('billing_interval', $plan->billing_interval) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Employee Seat Quota <span class="text-danger">*</span></label>
                        <input type="number" name="employee_limit" class="form-control @error('employee_limit') is-invalid @enderror" value="{{ old('employee_limit', $plan->employee_limit) }}" required min="1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Legal Company Quota <span class="text-danger">*</span></label>
                        <input type="number" name="company_limit" class="form-control @error('company_limit') is-invalid @enderror" value="{{ old('company_limit', $plan->company_limit) }}" required min="1">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Module Feature Entitlements</h5>

                @php
                    $planFeatureMap = $plan->features->where('is_enabled', true)->pluck('feature_code')->toArray();
                @endphp

                <div class="row g-3 mb-4">
                    @foreach($features as $code => $name)
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded bg-light">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="features[]" value="{{ $code }}" id="feature_{{ $code }}" {{ in_array($code, old('features', $planFeatureMap)) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold text-dark cursor-pointer" for="feature_{{ $code }}">
                                    {{ $name }}
                                    <div class="text-muted fw-normal small">Module code: <code>{{ $code }}</code></div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex align-items-center gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">Save Changes</button>
                    <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

