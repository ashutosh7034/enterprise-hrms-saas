@extends('layouts.platform')

@section('title', 'Register New Tenant | Platform Control Center')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 13px;">
            <li class="breadcrumb-item"><a href="{{ route('platform.tenants.index') }}" class="text-decoration-none">Customer Tenants</a></li>
            <li class="breadcrumb-item active" aria-current="page">Register New Tenant</li>
        </ol>
    </nav>
    <h3 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Register Customer Tenant</h3>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Establish a secure customer organization boundary with isolated multi-company structure.</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="platform-card p-4">
            <form method="POST" action="{{ route('platform.tenants.store') }}">
                @csrf

                <!-- Basic Identification -->
                <div class="border-bottom pb-3 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Tenant Identification</h5>
                    <p class="text-muted small mb-0">System identifiers used for database scoping and API authentication.</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Tenant Code <span class="text-danger">*</span></label>
                        <input type="text" name="tenant_code" id="tenant_code" class="form-control @error('tenant_code') is-invalid @enderror" placeholder="e.g. TNT-ACME" value="{{ old('tenant_code') }}" required style="font-family: 'JetBrains Mono', monospace; font-size: 13.5px;">
                        <div class="form-text small">Unique alphanumeric code (letters, numbers, dashes). Saved in uppercase.</div>
                        @error('tenant_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required style="font-size: 13.5px;">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Allow Tenant Access)</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended (Immediate Access Block)</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Archived)</option>
                        </select>
                        <div class="form-text small">Suspended tenants are locked out of HRMS login.</div>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Customer / Organization Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="tenant_name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Acme Corporation" value="{{ old('name') }}" required style="font-size: 13.5px;">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold" style="font-size: 13px;">URL Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="tenant_slug" class="form-control @error('slug') is-invalid @enderror" placeholder="e.g. acme-corporation" value="{{ old('slug') }}" required style="font-family: 'JetBrains Mono', monospace; font-size: 13px;">
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
                        <input type="text" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" placeholder="e.g. John Doe" value="{{ old('contact_name') }}" style="font-size: 13.5px;">
                        @error('contact_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" placeholder="e.g. admin@acmecorp.com" value="{{ old('contact_email') }}" style="font-size: 13.5px;">
                        @error('contact_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" placeholder="e.g. +91 98765 43210" value="{{ old('contact_phone') }}" style="font-size: 13.5px;">
                        @error('contact_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Office / Registered Address</label>
                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Corporate headquarters or correspondence address..." style="font-size: 13.5px;">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Internal Platform Notes</label>
                        <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Optional notes for Kanvtech platform administrators..." style="font-size: 13.5px;">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary px-4" style="font-size: 13.5px;">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-medium" style="font-size: 13.5px;">
                        <i class="bi bi-check2-circle me-1"></i> Create Customer Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security & Architecture Side Notice -->
    <div class="col-lg-4">
        <div class="platform-card p-3 mb-3" style="background-color: #F8FAFC; border-left: 4px solid #4F46E5;">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-primary me-1"></i> Multi-Tenant Isolation Guarantee</h6>
            <p class="text-muted small mb-2">
                Every tenant is assigned a distinct primary key (<code class="text-dark">tenants.id</code>).
            </p>
            <ul class="text-muted small ps-3 mb-0" style="line-height: 1.6;">
                <li>Legal companies mapped to this tenant are strictly isolated from all other customer tenants.</li>
                <li>HO Admins of this tenant cannot view or switch to legal companies belonging to another tenant.</li>
                <li>Suspending a tenant prevents all its users from signing in or continuing active sessions.</li>
            </ul>
        </div>

        <div class="platform-card p-3" style="background-color: #FFFBEB; border-left: 4px solid #F59E0B;">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle text-warning me-1"></i> Platform Scope Boundaries</h6>
            <p class="text-muted small mb-0" style="line-height: 1.6;">
                Platform Super Admins maintain customer tenant boundaries and legal entity associations. As per privacy architecture, Platform Super Admins do not access employee compensation, bank details, or private HR operational logs.
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('tenant_name');
        const slugInput = document.getElementById('tenant_slug');
        const codeInput = document.getElementById('tenant_code');

        let slugEdited = false;
        let codeEdited = false;

        slugInput.addEventListener('input', function () {
            slugEdited = true;
        });

        codeInput.addEventListener('input', function () {
            codeEdited = true;
        });

        nameInput.addEventListener('input', function () {
            const val = this.value.trim();
            if (!slugEdited && val) {
                slugInput.value = val.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
            if (!codeEdited && val) {
                const words = val.toUpperCase().replace(/[^A-Z0-9\s]/g, '').split(/\s+/).filter(Boolean);
                const prefix = words.length > 1 ? words.map(w => w[0]).join('').slice(0, 4) : words[0].slice(0, 6);
                codeInput.value = 'TNT-' + prefix;
            }
        });
    });
</script>
@endsection

