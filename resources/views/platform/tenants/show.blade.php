@extends('layouts.platform')

@section('title', $tenant->name . ' | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.tenants.index') }}" class="text-decoration-none">Customer Tenants</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $tenant->name }}</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 52px; height: 52px; background: linear-gradient(135deg, #4F46E5 0%, #0D9488 100%); font-size: 20px;">
                {{ strtoupper(substr($tenant->name, 0, 1)) }}
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold mb-0" style="color: #0F172A; letter-spacing: -0.02em;">{{ $tenant->name }}</h3>
                    <span class="code-pill fs-6">{{ $tenant->tenant_code }}</span>
                    @if($tenant->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11.5px;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i> Active
                        </span>
                    @elseif($tenant->status === 'suspended')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1" style="font-size: 11.5px;">
                            <i class="bi bi-exclamation-circle-fill me-1" style="font-size: 8px;"></i> Suspended
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 11.5px;">
                            <i class="bi bi-slash-circle me-1" style="font-size: 8px;"></i> Inactive
                        </span>
                    @endif
                </div>
                <div class="text-muted small mt-1">
                    Slug: <code class="text-dark">{{ $tenant->slug }}</code>
                    <span class="mx-2">•</span>
                    Onboarded: {{ $tenant->created_at ? $tenant->created_at->format('d M Y, h:i A') : '—' }}
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('platform.subscriptions.create', $tenant) }}" class="btn btn-outline-primary px-3 py-2 d-flex align-items-center gap-1" style="font-size: 13px;">
                <i class="bi bi-key"></i> Assign / Change Plan
            </a>
            <a href="{{ route('platform.tenants.edit', $tenant) }}" class="btn btn-outline-secondary px-3 py-2 d-flex align-items-center gap-1" style="font-size: 13px;">
                <i class="bi bi-pencil"></i> Edit Tenant
            </a>

            <!-- Quick Status Change Form -->
            <form method="POST" action="{{ route('platform.tenants.status', $tenant) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to change this tenant\'s operational status?');">
                @csrf
                @if($tenant->status === 'active')
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="btn btn-outline-warning text-dark px-3 py-2 d-flex align-items-center gap-1" style="font-size: 13px;">
                        <i class="bi bi-pause-circle"></i> Suspend Tenant
                    </button>
                @else
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn btn-success px-3 py-2 d-flex align-items-center gap-1" style="font-size: 13px;">
                        <i class="bi bi-play-circle"></i> Activate Tenant
                    </button>
                @endif
            </form>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Tenant Information -->
    <div class="col-lg-6">
        <div class="platform-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Account Overview</h5>
            <dl class="row mb-0" style="font-size: 13.5px;">
                <dt class="col-sm-4 text-muted fw-normal">Tenant Code</dt>
                <dd class="col-sm-8"><span class="code-pill fw-semibold">{{ $tenant->tenant_code }}</span></dd>

                <dt class="col-sm-4 text-muted fw-normal">Organization</dt>
                <dd class="col-sm-8 fw-semibold text-dark">{{ $tenant->name }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Slug Identifier</dt>
                <dd class="col-sm-8"><code>{{ $tenant->slug }}</code></dd>

                <dt class="col-sm-4 text-muted fw-normal">Operational Status</dt>
                <dd class="col-sm-8">
                    @if($tenant->status === 'active')
                        <span class="text-success fw-medium"><i class="bi bi-check-circle-fill me-1"></i> Active (User access allowed)</span>
                    @elseif($tenant->status === 'suspended')
                        <span class="text-warning fw-medium"><i class="bi bi-exclamation-triangle-fill me-1"></i> Suspended (User access blocked)</span>
                    @else
                        <span class="text-secondary fw-medium"><i class="bi bi-slash-circle me-1"></i> Inactive</span>
                    @endif
                </dd>

                <dt class="col-sm-4 text-muted fw-normal">Internal Notes</dt>
                <dd class="col-sm-8 text-muted">{{ $tenant->notes ?: 'No notes recorded.' }}</dd>
            </dl>
        </div>
    </div>

    <!-- Contact & Address -->
    <div class="col-lg-6">
        <div class="platform-card p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Primary Contact Details</h5>
            <dl class="row mb-0" style="font-size: 13.5px;">
                <dt class="col-sm-4 text-muted fw-normal">Contact Person</dt>
                <dd class="col-sm-8 fw-semibold text-dark">{{ $tenant->contact_name ?: '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Email Address</dt>
                <dd class="col-sm-8">
                    @if($tenant->contact_email)
                        <a href="mailto:{{ $tenant->contact_email }}" class="text-decoration-none">{{ $tenant->contact_email }}</a>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </dd>

                <dt class="col-sm-4 text-muted fw-normal">Phone Number</dt>
                <dd class="col-sm-8 text-dark">{{ $tenant->contact_phone ?: '—' }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Registered Address</dt>
                <dd class="col-sm-8 text-dark">{{ $tenant->address ?: '—' }}</dd>
            </dl>
        </div>
    </div>
</div>

<!-- Active Subscription & License Status -->
<div class="platform-card mb-4" style="border-left: 5px solid #0D9488;">
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-key text-teal me-2" style="color: #0D9488;"></i>Subscription License & Entitlements</h5>
                @if($subscription)
                    @if($subscription->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                    @elseif($subscription->status === 'grace_period')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Grace Period</span>
                    @elseif($subscription->status === 'expired')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Expired</span>
                    @elseif($subscription->status === 'suspended')
                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Suspended</span>
                    @else
                        <span class="badge bg-light text-dark border px-2 py-1">{{ ucfirst($subscription->status) }}</span>
                    @endif
                @else
                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Unlicensed</span>
                @endif
            </div>
            <p class="text-muted small mb-0 mt-1">Commercial tier allocation, resource limits, and module access control.</p>
        </div>
        <div>
            @if($subscription)
                <a href="{{ route('platform.subscriptions.show', $subscription) }}" class="btn btn-sm btn-outline-primary px-3">
                    Subscription Details <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @else
                <a href="{{ route('platform.subscriptions.create', $tenant) }}" class="btn btn-sm btn-primary px-3">
                    Assign Plan Tier
                </a>
            @endif
        </div>
    </div>

    @if($subscription)
        <div class="p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <dl class="row mb-0" style="font-size: 13.5px;">
                        <dt class="col-sm-4 text-muted fw-normal">Subscribed Tier</dt>
                        <dd class="col-sm-8 fw-bold text-dark">{{ $subscription->plan->name }} <span class="code-pill ms-1">{{ $subscription->plan->plan_code }}</span></dd>

                        <dt class="col-sm-4 text-muted fw-normal">Subscription Code</dt>
                        <dd class="col-sm-8"><code>{{ $subscription->subscription_code }}</code></dd>

                        <dt class="col-sm-4 text-muted fw-normal">Billing Cycle</dt>
                        <dd class="col-sm-8 text-capitalize">{{ $subscription->plan->billing_interval }} ({{ $subscription->plan->currency }} {{ number_format($subscription->plan->price, 2) }})</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Effective Dates</dt>
                        <dd class="col-sm-8 text-dark">
                            {{ $subscription->starts_at ? $subscription->starts_at->format('d M Y') : '—' }} to
                            <strong>{{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : '—' }}</strong>
                        </dd>

                        @if($subscription->grace_period_ends_at)
                            <dt class="col-sm-4 text-muted fw-normal">Grace Period</dt>
                            <dd class="col-sm-8 text-muted">Until {{ $subscription->grace_period_ends_at->format('d M Y') }}</dd>
                        @endif
                    </dl>
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted"><i class="bi bi-people me-1"></i> Employee Headcount Limit:</span>
                            <span class="fw-bold text-dark">{{ number_format($subscription->plan->employee_limit) }} Active Seats</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted"><i class="bi bi-buildings me-1"></i> Legal Company Limit:</span>
                            <span class="fw-bold text-dark">{{ number_format($subscription->plan->company_limit) }} Entities</span>
                        </div>
                    </div>

                    <div class="small fw-semibold text-muted mb-2 text-uppercase" style="letter-spacing: 0.04em;">Entitled HRMS Modules</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($subscription->plan->features as $feature)
                            @if($feature->is_enabled)
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 11px;">
                                    <i class="bi bi-check2 me-1"></i>{{ $feature->feature_name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="p-4 text-center text-muted">
            <p class="mb-2">No active subscription plan is currently allocated to this tenant.</p>
            <a href="{{ route('platform.subscriptions.create', $tenant) }}" class="btn btn-sm btn-primary">Provision Subscription Plan</a>
        </div>
    @endif
</div>

<!-- Associated Legal Companies (Group Companies) -->
<div class="platform-card mb-4">
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-buildings text-primary me-2"></i>Associated Legal Companies ({{ $tenant->companies->count() }})</h5>
            <p class="text-muted small mb-0">Legal business entities operating under this customer tenant boundary.</p>
        </div>
        <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">
            <i class="bi bi-shield-lock-fill me-1"></i> Strict Data Boundary
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                    <th class="ps-4">ID</th>
                    <th>Legal Entity Name</th>
                    <th>Company Code</th>
                    <th>CIN / Reg Number</th>
                    <th>Contact Email</th>
                    <th>Contact Phone</th>
                    <th>Status</th>
                    <th class="pe-4">Added Date</th>
                </tr>
            </thead>
            <tbody style="font-size: 13.5px;">
                @forelse($tenant->companies as $company)
                    <tr>
                        <td class="ps-4 fw-semibold text-muted">#{{ $company->id }}</td>
                        <td class="fw-semibold text-dark">{{ $company->name }}</td>
                        <td><span class="code-pill">{{ $company->code }}</span></td>
                        <td class="text-muted" style="font-size: 12.5px;">{{ $company->cin_number ?: '—' }}</td>
                        <td class="text-muted">{{ $company->email ?: '—' }}</td>
                        <td class="text-muted">{{ $company->phone ?: '—' }}</td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11.5px;">
                                {{ $company->status }}
                            </span>
                        </td>
                        <td class="text-muted pe-4" style="font-size: 12.5px;">
                            {{ $company->created_at ? $company->created_at->format('d M Y') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <p class="mb-0">No legal companies are currently mapped to this tenant.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3 bg-light border-top">
        <div class="d-flex align-items-center gap-2 small text-muted">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <span><strong>Zero Operational Exposure:</strong> Platform Super Admins oversee tenancy structure and company boundaries. Employee payroll, bank account details, and private employee personal files are strictly isolated inside the tenant boundary.</span>
        </div>
    </div>
</div>

<!-- Platform Audit Trail for this Tenant -->
<div class="platform-card">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-journal-text text-primary me-2"></i>Platform Tenant Audit Trail</h5>
        <p class="text-muted small mb-0">Record of administrative actions performed on this tenant by Kanvtech platform operators.</p>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr style="font-size: 12px; letter-spacing: 0.03em; text-transform: uppercase; color: #64748B;">
                    <th class="ps-4">Timestamp</th>
                    <th>Action</th>
                    <th>Platform Operator</th>
                    <th>IP Address</th>
                    <th class="pe-4">Details</th>
                </tr>
            </thead>
            <tbody style="font-size: 13px;">
                @forelse($auditLogs as $log)
                    <tr>
                        <td class="ps-4 text-muted">{{ $log->created_at ? $log->created_at->format('d M Y, h:i:s A') : '—' }}</td>
                        <td>
                            <span class="code-pill fw-semibold">{{ $log->action }}</span>
                        </td>
                        <td>
                            <div class="fw-medium text-dark">{{ $log->platformUser->name ?? 'System' }}</div>
                            <div class="text-muted small">{{ $log->platformUser->email ?? '—' }}</div>
                        </td>
                        <td><code class="text-muted">{{ $log->ip_address ?: '—' }}</code></td>
                        <td class="pe-4">
                            @if($log->metadata)
                                <code class="text-dark" style="font-size: 11.5px;">{{ json_encode($log->metadata) }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <p class="mb-0">No platform audit logs recorded for this tenant yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

