@extends('layouts.app')

@section('title', 'Employee Code Configuration — Admin Controlled')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 13px;">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Employee Master</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Code Configuration</li>
                </ol>
            </nav>
            <h4 class="mb-1 fw-bold text-dark"><i class="bi bi-upc-scan text-primary me-2"></i>Employee Code Configuration</h4>
            <p class="text-muted mb-0" style="font-size: 13.5px;">Configure sequential generation patterns, company prefixes, branch tokens, and formatting scopes.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.employee-codes.master') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                <i class="bi bi-table me-1"></i> View Code Master List
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-primary btn-sm px-3 fw-semibold">
                <i class="bi bi-people me-1"></i> Employee Directory
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Configuration Form -->
        <div class="col-lg-8">
            <div class="card border rounded-3 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark" style="font-size: 14.5px;">
                        <i class="bi bi-sliders text-primary me-2"></i>Code Generation Parameters
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                        Company: {{ $configuration->company?->name }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.employee-codes.update') }}" method="POST" id="codeConfigForm">
                        @csrf

                        <!-- Live Preview Callout -->
                        <div class="p-3 mb-4 rounded-3 border bg-light d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                            <div>
                                <small class="text-muted fw-semibold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Generated Code Live Preview</small>
                                <span class="badge bg-dark fs-5 font-monospace px-3 py-2 mt-1" id="livePreviewBadge">{{ $preview }}</span>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" id="refreshPreviewBtn">
                                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Preview
                            </button>
                        </div>

                        <!-- Format Template -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size: 13.5px;">
                                Format Template Pattern <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="format_template" id="formatTemplateInput" class="form-control font-monospace" value="{{ old('format_template', $configuration->format_template) }}" required>
                            
                            <!-- Available Tokens -->
                            <div class="mt-2">
                                <small class="text-muted d-block mb-1" style="font-size: 12px;">Click a placeholder token to insert into template:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-light btn-sm border text-primary token-insert-btn" data-token="{COMPANY}">{COMPANY}</button>
                                    <button type="button" class="btn btn-light btn-sm border text-primary token-insert-btn" data-token="{BRANCH}">{BRANCH}</button>
                                    <button type="button" class="btn btn-light btn-sm border text-primary token-insert-btn" data-token="{FY}">{FY}</button>
                                    <button type="button" class="btn btn-light btn-sm border text-primary token-insert-btn" data-token="{DEPARTMENT}">{DEPARTMENT}</button>
                                    <button type="button" class="btn btn-light btn-sm border text-primary token-insert-btn" data-token="{PREFIX}">{PREFIX}</button>
                                    <button type="button" class="btn btn-light btn-sm border text-primary token-insert-btn" data-token="{SEQUENCE}">{SEQUENCE}</button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">General Prefix <span class="text-danger">*</span></label>
                                <input type="text" name="prefix" id="prefixInput" class="form-control form-control-sm" value="{{ old('prefix', $configuration->prefix) }}" placeholder="e.g. EMP" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Company Prefix</label>
                                <input type="text" name="company_prefix" id="companyPrefixInput" class="form-control form-control-sm" value="{{ old('company_prefix', $configuration->company_prefix) }}" placeholder="e.g. VE">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Default Branch Prefix</label>
                                <input type="text" name="branch_prefix" id="branchPrefixInput" class="form-control form-control-sm" value="{{ old('branch_prefix', $configuration->branch_prefix) }}" placeholder="e.g. MUM">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Default Department Prefix</label>
                                <input type="text" name="department_prefix" id="departmentPrefixInput" class="form-control form-control-sm" value="{{ old('department_prefix', $configuration->department_prefix) }}" placeholder="e.g. HR">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Separator</label>
                                <select name="separator" id="separatorSelect" class="form-select form-select-sm">
                                    <option value="-" {{ old('separator', $configuration->separator) === '-' ? 'selected' : '' }}>Hyphen (-)</option>
                                    <option value="/" {{ old('separator', $configuration->separator) === '/' ? 'selected' : '' }}>Slash (/)</option>
                                    <option value="_" {{ old('separator', $configuration->separator) === '_' ? 'selected' : '' }}>Underscore (_)</option>
                                    <option value="." {{ old('separator', $configuration->separator) === '.' ? 'selected' : '' }}>Period (.)</option>
                                    <option value="" {{ old('separator', $configuration->separator) === '' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Sequence Number Length <span class="text-danger">*</span></label>
                                <input type="number" name="number_length" id="numLengthInput" class="form-control form-control-sm" min="3" max="8" value="{{ old('number_length', $configuration->number_length) }}" required>
                                <small class="text-muted" style="font-size: 11px;">e.g. 4 produces 0001, 5 produces 00001</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Starting Sequence Number <span class="text-danger">*</span></label>
                                <input type="number" name="starting_sequence_number" id="startingSeqInput" class="form-control form-control-sm" min="1" value="{{ old('starting_sequence_number', $configuration->starting_sequence_number) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 13px;">Uniqueness Scope <span class="text-danger">*</span></label>
                                <select name="uniqueness_scope" class="form-select form-select-sm" required>
                                    <option value="company" {{ old('uniqueness_scope', $configuration->uniqueness_scope) === 'company' ? 'selected' : '' }}>Company Level</option>
                                    <option value="tenant" {{ old('uniqueness_scope', $configuration->uniqueness_scope) === 'tenant' ? 'selected' : '' }}>Tenant Level (Across Group Companies)</option>
                                    <option value="global" {{ old('uniqueness_scope', $configuration->uniqueness_scope) === 'global' ? 'selected' : '' }}>Global Platform Level</option>
                                </select>
                            </div>
                        </div>

                        <!-- Advanced Reset Rules & Manual Toggle -->
                        <div class="border rounded-3 p-3 mb-4 bg-light">
                            <h6 class="fw-bold mb-3 text-dark" style="font-size: 13px;"><i class="bi bi-arrow-repeat text-primary me-2"></i>Sequence Resets & Manual Allocation Rules</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="reset_sequence_annually" value="1" id="resetAnnualSwitch" {{ old('reset_sequence_annually', $configuration->reset_sequence_annually) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="resetAnnualSwitch" style="font-size: 13px;">Reset Sequence Annually (Per Financial Year)</label>
                                    </div>
                                    <small class="text-muted d-block ms-4" style="font-size: 11.5px;">Restarts numbering from starting sequence each financial year.</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="reset_sequence_per_branch" value="1" id="resetBranchSwitch" {{ old('reset_sequence_per_branch', $configuration->reset_sequence_per_branch) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="resetBranchSwitch" style="font-size: 13px;">Reset Sequence Per Branch / Site</label>
                                    </div>
                                    <small class="text-muted d-block ms-4" style="font-size: 11.5px;">Maintains independent counters for each operational branch location.</small>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_manual_employee_code" value="1" id="allowManualSwitch" {{ old('allow_manual_employee_code', $configuration->allow_manual_employee_code) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="allowManualSwitch" style="font-size: 13px;">Allow Manual Employee Code Entry</label>
                                    </div>
                                    <small class="text-muted d-block ms-4" style="font-size: 11.5px;">Permits authorized HR Admins to assign legacy or custom codes during onboarding.</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                                <i class="bi bi-save me-1"></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Reference Card -->
        <div class="col-lg-4">
            <!-- Format Templates Quick Presets -->
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <span class="fw-bold text-dark" style="font-size: 14px;"><i class="bi bi-magic text-primary me-2"></i>Standard Preset Formats</span>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted mb-3" style="font-size: 12.5px;">Click any preset to copy it into your template:</p>
                    <div class="list-group list-group-flush border rounded-2" style="font-size: 13px;">
                        <button type="button" class="list-group-item list-group-item-action preset-select-btn" data-template="{COMPANY}-{PREFIX}-{SEQUENCE}" data-separator="-">
                            <div class="fw-semibold text-dark">Format A — Company & Sequential</div>
                            <small class="text-muted font-monospace">VE-EMP-0001</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action preset-select-btn" data-template="{COMPANY}-{FY}-{SEQUENCE}" data-separator="-">
                            <div class="fw-semibold text-dark">Format B — Company & Financial Year</div>
                            <small class="text-muted font-monospace">VE-2026-0001</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action preset-select-btn" data-template="{COMPANY}-{BRANCH}-{PREFIX}-{SEQUENCE}" data-separator="-">
                            <div class="fw-semibold text-dark">Format C — Branch / Site Qualified</div>
                            <small class="text-muted font-monospace">VE-MUM-EMP-0001</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action preset-select-btn" data-template="{COMPANY}-{DEPARTMENT}-{FY}-{SEQUENCE}" data-separator="-">
                            <div class="fw-semibold text-dark">Format D — Department & Year</div>
                            <small class="text-muted font-monospace">VE-HR-2026-001</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action preset-select-btn" data-template="{PREFIX}/{FY}/{SEQUENCE}" data-separator="/">
                            <div class="fw-semibold text-dark">Format E — Slash Separated Standard</div>
                            <small class="text-muted font-monospace">EMP/2026/00001</small>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Concurrency & Safety Guarantee Info -->
            <div class="card border rounded-3 shadow-sm bg-light">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-dark mb-2" style="font-size: 13.5px;"><i class="bi bi-shield-check text-success me-2"></i>Concurrency & Safety Guarantee</h6>
                    <ul class="text-muted ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                        <li><strong>Zero Duplicates:</strong> Sequential allocation is locked with database-level pessimistic transactions (`lockForUpdate`).</li>
                        <li><strong>Historical Preservation:</strong> Updating patterns only impacts new onboarding requests. Existing employee codes remain strictly immutable.</li>
                        <li><strong>Sequence Audit:</strong> Sequence numbers are never reused upon candidate deletion or cancellation.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const templateInput = document.getElementById('formatTemplateInput');
    const livePreviewBadge = document.getElementById('livePreviewBadge');
    const refreshBtn = document.getElementById('refreshPreviewBtn');

    function updatePreview() {
        const formData = new FormData(document.getElementById('codeConfigForm'));
        
        fetch('{{ route("admin.employee-codes.preview") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.preview) {
                livePreviewBadge.textContent = data.preview;
            }
        })
        .catch(err => console.error('Preview error', err));
    }

    // Insert token button handlers
    document.querySelectorAll('.token-insert-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const token = this.getAttribute('data-token');
            const start = templateInput.selectionStart;
            const end = templateInput.selectionEnd;
            const val = templateInput.value;
            templateInput.value = val.substring(0, start) + token + val.substring(end);
            templateInput.focus();
            templateInput.setSelectionRange(start + token.length, start + token.length);
            updatePreview();
        });
    });

    // Preset selection handlers
    document.querySelectorAll('.preset-select-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            templateInput.value = this.getAttribute('data-template');
            const sep = this.getAttribute('data-separator');
            if (sep) {
                document.getElementById('separatorSelect').value = sep;
            }
            updatePreview();
        });
    });

    // Dynamic inputs debounce preview
    let debounceTimer;
    document.querySelectorAll('#codeConfigForm input, #codeConfigForm select').forEach(el => {
        el.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updatePreview, 300);
        });
    });

    if (refreshBtn) {
        refreshBtn.addEventListener('click', updatePreview);
    }
});
</script>
@endpush
@endsection

