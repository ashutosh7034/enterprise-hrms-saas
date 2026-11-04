@extends('layouts.platform')

@section('title', 'Subscription Plans | Platform Control Center')

@section('content')
<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 13px;">
                <li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Subscription Plans</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Commercial Subscription Plans</h3>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Manage commercial SaaS subscription tiers, resource quotas, and module feature entitlements.</p>
    </div>
    <div>
        <a href="{{ route('platform.plans.create') }}" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 shadow-sm" style="font-size: 13.5px; border-radius: 8px;">
            <i class="bi bi-plus-lg"></i>
            <span>Create New Plan</span>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    @forelse($plans as $plan)
        <div class="col-lg-4 col-md-6">
            <div class="platform-card p-4 h-100 d-flex flex-column justify-content-between position-relative" style="border-top: 4px solid {{ $plan->status === 'active' ? '#4F46E5' : '#94A3B8' }};">
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="code-pill">{{ $plan->plan_code }}</span>
                        @if($plan->status === 'active')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Inactive</span>
                        @endif
                    </div>

                    <h4 class="fw-bold text-dark mb-1">{{ $plan->name }}</h4>
                    <p class="text-muted small mb-3">{{ $plan->description ?: 'No description provided.' }}</p>

                    <div class="mb-4">
                        <span class="fs-3 fw-bold text-dark">{{ $plan->currency }} {{ number_format($plan->price, 2) }}</span>
                        <span class="text-muted small">/ {{ $plan->billing_interval }}</span>
                    </div>

                    <div class="p-3 bg-light rounded mb-3" style="font-size: 13px;">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><i class="bi bi-people me-1"></i> Employee Limit:</span>
                            <span class="fw-bold text-dark">{{ number_format($plan->employee_limit) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><i class="bi bi-buildings me-1"></i> Company Limit:</span>
                            <span class="fw-bold text-dark">{{ number_format($plan->company_limit) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted"><i class="bi bi-key me-1"></i> Active Subscribers:</span>
                            <span class="fw-bold text-primary">{{ $plan->subscriptions_count }}</span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark small text-uppercase mb-2" style="letter-spacing: 0.05em;">Included Modules</h6>
                    <div class="d-flex flex-wrap gap-1 mb-4">
                        @foreach($plan->features as $feature)
                            @if($feature->is_enabled)
                                <span class="badge bg-light text-dark border small py-1 px-2" style="font-weight: 500;">
                                    <i class="bi bi-check-circle-fill text-success me-1" style="font-size: 9px;"></i>{{ $feature->feature_name }}
                                </span>
                            @else
                                <span class="badge bg-light text-muted border small py-1 px-2 text-decoration-line-through opacity-50">
                                    {{ $feature->feature_name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small">Updated: {{ $plan->updated_at ? $plan->updated_at->format('d M Y') : '—' }}</span>
                    <a href="{{ route('platform.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary px-3 py-1">
                        <i class="bi bi-pencil me-1"></i> Edit Plan
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="platform-card p-5 text-center text-muted">
                <i class="bi bi-layers text-secondary fs-1 mb-3"></i>
                <h5>No subscription plans configured yet</h5>
                <p class="small mb-3">Create commercial tiers to license the Enterprise HRMS platform to customer organizations.</p>
                <a href="{{ route('platform.plans.create') }}" class="btn btn-primary btn-sm">Create First Plan</a>
            </div>
        </div>
    @endforelse
</div>
@endsection

