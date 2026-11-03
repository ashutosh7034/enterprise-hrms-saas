@extends('layouts.app')

@section('title', 'Create Document Template')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Create New Document Template</h4>
            <p class="text-muted small mb-0">Author a new regulatory letter or form with dynamic token binding</p>
        </div>
        <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Catalog
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 border-0 shadow-sm">
                <form method="POST" action="{{ route('admin.documents.templates.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select form-select-sm" required>
                                <option value="">-- Choose Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Document Type <span class="text-danger">*</span></label>
                            <input type="text" name="document_type" class="form-control form-control-sm" placeholder="e.g. Offer Letter, Joining Form" value="{{ old('document_type') }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Unique Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-sm font-monospace" placeholder="e.g. LETTER_CUSTOM_01" value="{{ old('code') }}" required style="text-transform: uppercase;">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Custom Relocation Agreement" value="{{ old('name') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Brief explanation of policy context" value="{{ old('description') }}">
                        </div>
                    </div>

                    <!-- Options & Dates -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded border">
                        <div class="col-md-4 form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" value="1" {{ old('requires_approval') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="requires_approval">Requires Approval</label>
                            <div class="text-muted" style="font-size: 11px;">Must be reviewed by HR/Admin before issuance</div>
                        </div>

                        <div class="col-md-4 form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="is_sensitive_salary" id="is_sensitive_salary" value="1" {{ old('is_sensitive_salary') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="is_sensitive_salary">Sensitive Salary Gate</label>
                            <div class="text-muted" style="font-size: 11px;">Restricts placeholder access to HR Executive & HO Admin</div>
                        </div>

                        <div class="col-md-4 form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="header_footer_enabled" id="header_footer_enabled" value="1" {{ old('header_footer_enabled', '1') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="header_footer_enabled">Header / Letterhead</label>
                            <div class="text-muted" style="font-size: 11px;">Embeds corporate header and verification footer</div>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label class="form-label small fw-semibold">Effective From</label>
                            <input type="date" name="effective_from" class="form-control form-control-sm" value="{{ old('effective_from', date('Y-m-d')) }}">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label class="form-label small fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" class="form-control form-control-sm" value="{{ old('effective_to') }}">
                        </div>
                    </div>

                    <!-- Content Editor -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-dark mb-0">HTML Content & Placeholder Blueprint <span class="text-danger">*</span></label>
                            <span class="text-muted small">Use tags like <code>{employee_name}</code>, <code>{company_name}</code></span>
                        </div>
                        <textarea name="content_html" id="content_html" class="form-control font-monospace" rows="18" required style="font-size: 13px;">{{ old('content_html', '<div class="letter-container">
    <div class="letter-header text-center">
        <h2>{company_name}</h2>
        <p class="company-meta">{company_address} | CIN: {company_cin}</p>
        <hr class="header-divider"/>
    </div>
    <div class="doc-meta row">
        <div class="col-6"><strong>Ref No:</strong> {document_number}</div>
        <div class="col-6 text-end"><strong>Date:</strong> {document_date}</div>
    </div>
    <div class="recipient-block mt-3">
        <p><strong>To,</strong><br/>
        <strong>{employee_name}</strong> ({employee_code})<br/>
        Designation: {designation} | Department: {department}</p>
    </div>
    <div class="letter-subject mt-3 mb-3">
        <strong>Subject: Official Communication</strong>
    </div>
    <div class="letter-body">
        <p>Dear {first_name},</p>
        <p>Your content here...</p>
        <p class="mt-4">For <strong>{company_name}</strong></p>
        <div class="signatory-block mt-4">
            <p><strong>{signatory_name}</strong><br/>
            {signatory_designation}</p>
        </div>
    </div>
    <div class="letter-footer mt-5 pt-3 border-top text-muted small text-center">
        <p>Verification Code: <strong>{verification_code}</strong> | Authenticate: {verification_url}</p>
    </div>
</div>') }}</textarea>
                    </div>

                    <!-- Form Submission -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="small text-muted"><i class="bi bi-info-circle me-1"></i>Saved template starts at Version 1 in Active state.</span>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-teal btn-sm px-4 shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Save & Publish Template
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Placeholder Palette Sidebar -->
        <div class="col-lg-4">
            <div class="card card-custom p-3 border-0 shadow-sm sticky-top" style="top: 20px;">
                <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-braces text-teal me-2"></i>Placeholder Directory</h6>
                <p class="small text-muted mb-3">Click on any placeholder tag to copy it to clipboard:</p>

                <div class="accordion accordion-flush small" id="placeholderAccordion">
                    @foreach($placeholders as $group => $tokens)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ Str::slug($group) }}">
                                <button class="accordion-button collapsed py-2 px-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ Str::slug($group) }}">
                                    {{ $group }} ({{ count($tokens) }})
                                </button>
                            </h2>
                            <div id="collapse{{ Str::slug($group) }}" class="accordion-collapse collapse" data-bs-parent="#placeholderAccordion">
                                <div class="accordion-body px-0 py-2">
                                    @foreach($tokens as $tok => $desc)
                                        <div class="d-flex justify-content-between align-items-center mb-1 p-1 rounded hover-bg">
                                            <code class="text-teal fw-bold cursor-pointer" onclick="copyToken('{{ $tok }}')" title="Click to copy">{{ $tok }}</code>
                                            <span class="text-muted" style="font-size: 11px;">{{ $desc }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToken(token) {
    navigator.clipboard.writeText(token).then(() => {
        // Visual feedback
        const el = event.target;
        const originalText = el.innerText;
        el.innerText = 'Copied!';
        setTimeout(() => { el.innerText = originalText; }, 1200);
    });
}
</script>
@endsection

