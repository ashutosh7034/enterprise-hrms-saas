@extends('layouts.app')

@section('title', 'Edit Template - ' . $template->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="font-monospace small px-2 py-0.5 rounded bg-dark text-white fw-semibold">{{ $template->code }}</span>
                <span class="badge bg-secondary font-monospace">Version {{ $template->version }}</span>
                <span class="badge {{ $template->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $template->status }}</span>
            </div>
            <h4 class="fw-bold mb-0" style="color: #0F172A;">Edit Template: {{ $template->name }}</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Catalog
            </a>
            <a href="{{ route('admin.documents.templates.preview', $template) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-eye me-1"></i> Live Preview
            </a>
            <form method="POST" action="{{ route('admin.documents.templates.duplicate', $template) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-copy me-1"></i> Duplicate
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Edit Form -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 border-0 shadow-sm">
                <form method="POST" action="{{ route('admin.documents.templates.update', $template) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select form-select-sm" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category', $template->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Document Type <span class="text-danger">*</span></label>
                            <input type="text" name="document_type" class="form-control form-control-sm" value="{{ old('document_type', $template->document_type) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select form-select-sm" required>
                                @foreach(['Active', 'Inactive', 'Draft', 'Pending Approval', 'Approved', 'Archived'] as $st)
                                    <option value="{{ $st }}" {{ old('status', $template->status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $template->name) }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $template->description) }}">
                        </div>
                    </div>

                    <!-- Options & Dates -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded border">
                        <div class="col-md-4 form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" value="1" {{ old('requires_approval', $template->requires_approval) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="requires_approval">Requires Approval</label>
                        </div>

                        <div class="col-md-4 form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="is_sensitive_salary" id="is_sensitive_salary" value="1" {{ old('is_sensitive_salary', $template->is_sensitive_salary) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="is_sensitive_salary">Sensitive Salary Gate</label>
                        </div>

                        <div class="col-md-4 form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="header_footer_enabled" id="header_footer_enabled" value="1" {{ old('header_footer_enabled', $template->header_footer_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="header_footer_enabled">Header / Letterhead</label>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label class="form-label small fw-semibold">Effective From</label>
                            <input type="date" name="effective_from" class="form-control form-control-sm" value="{{ old('effective_from', $template->effective_from?->format('Y-m-d')) }}">
                        </div>

                        <div class="col-md-6 mt-2">
                            <label class="form-label small fw-semibold">Effective To</label>
                            <input type="date" name="effective_to" class="form-control form-control-sm" value="{{ old('effective_to', $template->effective_to?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <!-- Change Summary -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Revision Change Summary (if modifying HTML)</label>
                        <input type="text" name="change_summary" class="form-control form-control-sm" placeholder="e.g. Updated standard confidentiality clause and signatory block">
                        <div class="form-text small">If the HTML content below is altered, a new immutable version record will be created automatically.</div>
                    </div>

                    <!-- HTML Content -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-dark">HTML Content Template Blueprint <span class="text-danger">*</span></label>
                        <textarea name="content_html" class="form-control font-monospace" rows="18" required style="font-size: 13px;">{{ old('content_html', $template->content_html) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="small text-muted">Last updated: {{ $template->updated_at?->format('d M Y, h:i A') }}</span>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-teal btn-sm px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Template
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar: Versions & Placeholders -->
        <div class="col-lg-4">
            <!-- Version History Box -->
            <div class="card card-custom p-3 border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-teal me-2"></i>Version History</h6>
                <div class="list-group list-group-flush small">
                    @foreach($template->versions as $ver)
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary font-monospace">Version {{ $ver->version_number }}</span>
                                <span class="text-muted" style="font-size: 11px;">{{ $ver->created_at?->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="mt-1 text-dark">{{ $ver->change_summary ?? 'Version snapshot' }}</div>
                            <div class="text-muted" style="font-size: 11px;">By {{ $ver->creator?->name ?? 'System' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Placeholders -->
            <div class="card card-custom p-3 border-0 shadow-sm">
                <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-braces text-teal me-2"></i>Placeholder Tags</h6>
                <p class="small text-muted mb-2">Click to copy tag to clipboard:</p>
                <div class="accordion accordion-flush small" id="editPlaceholderAccordion">
                    @foreach($placeholders as $group => $tokens)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingEdit{{ Str::slug($group) }}">
                                <button class="accordion-button collapsed py-2 px-0 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdit{{ Str::slug($group) }}">
                                    {{ $group }}
                                </button>
                            </h2>
                            <div id="collapseEdit{{ Str::slug($group) }}" class="accordion-collapse collapse" data-bs-parent="#editPlaceholderAccordion">
                                <div class="accordion-body px-0 py-2">
                                    @foreach($tokens as $tok => $desc)
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <code class="text-teal cursor-pointer" onclick="copyToken('{{ $tok }}')">{{ $tok }}</code>
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
        const el = event.target;
        const originalText = el.innerText;
        el.innerText = 'Copied!';
        setTimeout(() => { el.innerText = originalText; }, 1200);
    });
}
</script>
@endsection

