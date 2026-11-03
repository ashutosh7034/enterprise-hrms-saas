@extends('layouts.app')

@section('title', 'Generate HR Document')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Document Generation Wizard</h4>
            <p class="text-muted small mb-0">Select an approved template and workforce recipient to compose, snapshot, and publish</p>
        </div>
        <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Hub
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2 small mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-custom p-4 border-0 shadow-sm">
                <form method="POST" action="{{ route('admin.documents.store') }}">
                    @csrf

                    <!-- 1. Select Template -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-dark">1. Select Document Template <span class="text-danger">*</span></label>
                        <select name="template_id" id="template_id" class="form-select" required onchange="updateTemplateMeta(this)">
                            <option value="">-- Choose from catalog (Letters & Forms) --</option>
                            @php
                                $groupedTemplates = $templates->groupBy('category');
                            @endphp
                            @foreach($groupedTemplates as $category => $catTemplates)
                                <optgroup label="{{ $category }}">
                                    @foreach($catTemplates as $tmpl)
                                        <option value="{{ $tmpl->id }}" 
                                            data-category="{{ $tmpl->category }}"
                                            data-desc="{{ $tmpl->description }}"
                                            data-approval="{{ $tmpl->requires_approval ? '1' : '0' }}"
                                            data-salary="{{ $tmpl->is_sensitive_salary ? '1' : '0' }}"
                                            {{ (old('template_id', $selectedTemplate?->id) == $tmpl->id) ? 'selected' : '' }}>
                                            {{ $tmpl->name }} ({{ $tmpl->code }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <div id="template-info-box" class="mt-2 p-2 rounded bg-light border small text-muted" style="display: none;">
                            <span id="tmpl-meta-badge"></span>
                            <span id="tmpl-desc-text" class="ms-2"></span>
                        </div>
                    </div>

                    <!-- 2. Select Employee Recipient -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-dark">2. Select Employee Recipient <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employee_id" class="form-select" required>
                            <option value="">-- Choose Employee --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ (old('employee_id', $selectedEmployee?->id) == $emp->id) ? 'selected' : '' }}>
                                    {{ $emp->full_name }} ({{ $emp->employee_code }}) &bull; {{ $emp->designation?->name }} &bull; {{ $emp->department?->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small">All personal placeholders ({employee_name}, {address}, {designation}) will be automatically resolved from the active employee profile.</div>
                    </div>

                    <!-- 3. Document Action Dates & Signatory Options -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-dark">Effective Date</label>
                            <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', date('Y-m-d')) }}">
                            <div class="form-text small">Date on which the document or terms take legal effect.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-dark">Authorized Signatory Name</label>
                            <input type="text" name="signatory_name" class="form-control" placeholder="e.g. Authorized Signatory / HR Head" value="{{ old('signatory_name', 'Authorized Signatory') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-dark">Signatory Designation</label>
                            <input type="text" name="signatory_designation" class="form-control" placeholder="e.g. Head - Human Resources" value="{{ old('signatory_designation', 'Head - Human Resources') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-dark">Internal Dispatch Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional audit memo" value="{{ old('remarks') }}">
                        </div>
                    </div>

                    <!-- Submit Actions -->
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            <i class="bi bi-shield-lock me-1 text-teal"></i>
                            An immutable cryptographic snapshot will be generated upon submission.
                        </span>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-teal px-4 shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Generate & Issue Document
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Guidelines -->
        <div class="col-lg-4">
            <div class="card card-custom p-3 border-0 shadow-sm mb-3">
                <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle text-teal me-2"></i>Generation Workflow</h6>
                <ul class="small text-muted ps-3 mb-0">
                    <li class="mb-2"><strong>Deterministic Snapshots:</strong> Once generated, the text, formatting, and metadata are frozen permanently to preserve regulatory compliance.</li>
                    <li class="mb-2"><strong>Approval Gates:</strong> Templates marked as <em>Requires Approval</em> are placed in the Approval Queue before PDF issuance.</li>
                    <li class="mb-2"><strong>Tamper-Proof Code:</strong> Every document receives a unique 12-character verification token for third-party validation.</li>
                    <li><strong>Storage:</strong> PDFs are stored privately and streamed strictly via authenticated access controllers.</li>
                </ul>
            </div>

            <div class="card card-custom p-3 border-0 shadow-sm bg-light">
                <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-qr-code text-teal me-2"></i>Public Verification</h6>
                <p class="small text-muted mb-0">
                    Recipients or third-party institutions can authenticate issued documents anytime at:
                    <br/><code class="text-teal">{{ url('/verify-document') }}</code>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function updateTemplateMeta(select) {
    const opt = select.options[select.selectedIndex];
    const box = document.getElementById('template-info-box');
    const badge = document.getElementById('tmpl-meta-badge');
    const desc = document.getElementById('tmpl-desc-text');

    if (!opt.value) {
        box.style.display = 'none';
        return;
    }

    const approval = opt.getAttribute('data-approval') === '1';
    const salary = opt.getAttribute('data-salary') === '1';
    const descText = opt.getAttribute('data-desc') || 'Standard template';

    let badges = '';
    if (approval) {
        badges += '<span class="badge bg-warning text-dark me-1">Approval Required</span>';
    } else {
        badges += '<span class="badge bg-success me-1">Direct Issuance</span>';
    }

    if (salary) {
        badges += '<span class="badge bg-danger">Sensitive Compensation</span>';
    }

    badge.innerHTML = badges;
    desc.textContent = descText;
    box.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('template_id');
    if (sel && sel.value) {
        updateTemplateMeta(sel);
    }
});
</script>
@endsection

