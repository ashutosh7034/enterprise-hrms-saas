@extends('layouts.platform')

@section('title', 'Assign Plan to ' . $tenant->name . ' | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.tenants.index') }}" class="text-decoration-none">Customer Tenants</a></li>
            <li class="breadcrumb-item"><a href="{{ route('platform.tenants.show', $tenant) }}" class="text-decoration-none">{{ $tenant->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Assign Subscription</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <h3 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.02em;">Assign Subscription Plan: {{ $tenant->name }}</h3>
        <span class="code-pill fs-6">{{ $tenant->tenant_code }}</span>
    </div>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="platform-card p-4">
            <form method="POST" action="{{ route('platform.subscriptions.store', $tenant) }}">
                @csrf

                @if($currentSubscription)
                    <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center gap-2 small">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            <strong>Active Subscription Present:</strong> Tenant currently holds <strong>{{ $currentSubscription->plan->name }}</strong> ({{ $currentSubscription->subscription_code }}). Assigning a new plan will transition the current record to expired and activate the new terms.
                        </div>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Select Subscription Plan <span class="text-danger">*</span></label>
                        <select name="subscription_plan_id" class="form-select @error('subscription_plan_id') is-invalid @enderror" required>
                            <option value="">-- Choose Plan Tier --</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('subscription_plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} — {{ $plan->currency }} {{ number_format($plan->price) }}/{{ $plan->billing_interval }} (Limit: {{ $plan->employee_limit }} Emps)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Initial Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Immediate Entitlement)</option>
                            <option value="trial" {{ old('status') === 'trial' ? 'selected' : '' }}>Trial (Free Trial Period)</option>
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending Payment</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Starts At</label>
                        <input type="date" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', date('Y-m-d')) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Ends At (Optional override)</label>
                        <input type="date" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at') }}">
                        <div class="form-text small">Leave blank to use plan interval.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-dark">Grace Days</label>
                        <input type="number" name="grace_days" class="form-control @error('grace_days') is-invalid @enderror" value="{{ old('grace_days', 14) }}" min="0" max="90">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Renewal Mode <span class="text-danger">*</span></label>
                        <select name="renewal_type" class="form-select @error('renewal_type') is-invalid @enderror" required>
                            <option value="manual" {{ old('renewal_type', 'manual') === 'manual' ? 'selected' : '' }}>Manual Renewal</option>
                            <option value="automatic" {{ old('renewal_type') === 'automatic' ? 'selected' : '' }}>Automatic Recurring</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark">Payment Settlement Mode</label>
                        <select name="payment_provider" class="form-select @error('payment_provider') is-invalid @enderror">
                            <option value="manual_bank_transfer">Direct Bank Transfer / NEFT / RTGS</option>
                            <option value="cheque">Corporate Cheque</option>
                            <option value="complimentary">Complimentary / Platform Waiver</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check p-2 bg-light border rounded">
                            <input class="form-check-input ms-1 me-2" type="checkbox" name="is_paid" value="1" id="is_paid" {{ old('is_paid', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark cursor-pointer" for="is_paid">
                                Mark Initial Invoice as Paid & Generate Settlement Transaction Receipt
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Internal Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Contract reference, purchase order, custom terms...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">Confirm & Assign Subscription</button>
                    <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

