@extends('layouts.platform')

@section('title', 'Edit Tenant ' . $tenant->name . ' | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.tenants.index') }}" class="text-decoration-none">Customer Tenants</a></li>
            <li class="breadcrumb-item"><a href="{{ route('platform.tenants.show', $tenant) }}" class="text-decoration-none">{{ $tenant->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Tenant</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Edit Tenant: {{ $tenant->name }}</h3>
            <p class="text-muted mb-0" style="font-size: 13.5px;">Update customer tenant metadata, contact information, and operational state.</p>
        </div>
        <span class="code-pill fs-6">{{ $tenant->tenant_code }}</span>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="platform-card p-4">
            <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}">
                @csrf
                @method('PUT')

                <!-- Basic Identification -->
                <div class="border-bottom pb-3 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Tenant Identification</h5>
                    <p class="text-muted small mb-0">System identifiers used for database scoping and API authentication.</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Tenant Code <span class="text-danger">*</span></label>
                        <input type="text" name="tenant_code" id="tenant_code" class="form-control @error('tenant_code') is-invalid @enderror" value="{{ old('tenant_code', $tenant->tenant_code) }}" required style="font-family: 'JetBrains Mono', monospace; font-size: 13.5px;">
                        <div class="form-text small">Unique alphanumeric code (letters, numbers, dashes). Saved in uppercase.</div>
                        @error('tenant_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required style="font-size: 13.5px;">
                            <option value="active" {{ old('status', $tenant->status) === 'active' ? 'selected' : '' }}>Active (Allow Tenant Access)</option>
                            <option value="suspended" {{ old('status', $tenant->status) === 'suspended' ? 'selected' : '' }}>Suspended (Immediate Access Block)</option>
                            <option value="inactive" {{ old('status', $tenant->status) === 'inactive' ? 'selected' : '' }}>Inactive (Archived)</option>
                        </select>
                        <div class="form-text small">Changing status triggers an immediate audit log entry.</div>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Customer / Organization Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $tenant->name) }}" required style="font-size: 13.5px;">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold" style="font-size: 13px;">URL Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $tenant->slug) }}" required style="font-family: 'JetBrains Mono', monospace; font-size: 13px;">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Primary Contact Information -->
                <div class="border-bottom pb-3 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Primary Account Contact</h5>
                    <p class="text-muted small mb-0">Authorized representative for tenant license, subscription, and administrative notices.</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Contact Person Name</label>
                        <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name', $tenant->contact_name) }}" style="font-size: 13.5px;">
                        @error('contact_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', $tenant->contact_email) }}" style="font-size: 13.5px;">
                        @error('contact_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone', $tenant->contact_phone) }}" style="font-size: 13.5px;">
                        @error('contact_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Office / Registered Address</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" style="font-size: 13.5px;">{{ old('address', $tenant->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Internal Platform Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" style="font-size: 13.5px;">{{ old('notes', $tenant->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-outline-secondary px-4" style="font-size: 13.5px;">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-medium" style="font-size: 13.5px;">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="platform-card p-3 mb-3">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary me-1"></i> Tenant Metadata</h6>
            <div class="d-flex justify-content-between py-2 border-bottom small">
                <span class="text-muted">Tenant ID</span>
                <span class="fw-semibold text-dark">#{{ $tenant->id }}</span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom small">
                <span class="text-muted">Associated Companies</span>
                <span class="badge bg-light text-dark border">{{ $tenant->companies()->count() }} Legal Entities</span>
            </div>
            <div class="d-flex justify-content-between py-2 border-bottom small">
                <span class="text-muted">Created At</span>
                <span class="text-dark">{{ $tenant->created_at ? $tenant->created_at->format('d M Y, h:i A') : '—' }}</span>
            </div>
            <div class="d-flex justify-content-between py-2 small">
                <span class="text-muted">Last Updated</span>
                <span class="text-dark">{{ $tenant->updated_at ? $tenant->updated_at->format('d M Y, h:i A') : '—' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

