@extends('layouts.app')

@section('title', 'Employee Code Settings — Format & Master Register')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 13px;">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Workforce Directory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Employee Code Settings</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-upc-scan text-teal me-2"></i>Employee Code Settings</h4>
                <span class="badge bg-teal-subtle text-teal border border-teal-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">
                    Company: {{ $configuration->company?->name }} ({{ $configuration->company?->code }})
                </span>
            </div>
            <p class="text-muted mb-0 mt-1" style="font-size: 13.5px;">Unified administrative hub for code numbering patterns, sequence resets, prefix scopes, and master register.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.employee-codes.export') }}" class="btn btn-outline-success btn-sm px-3 fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Master CSV
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Workforce Directory
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tabbed Navigation Header -->
    <ul class="nav nav-pills mb-4 gap-2 p-1 bg-white rounded-3 border shadow-xs" id="codeSettingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold {{ $activeTab !== 'master' ? 'active' : '' }}" id="config-tab" data-bs-toggle="pill" data-bs-target="#config-pane" type="button" role="tab" aria-selected="{{ $activeTab !== 'master' ? 'true' : 'false' }}">
                <i class="bi bi-sliders me-2"></i>Format & Sequence Configuration
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 fw-semibold {{ $activeTab === 'master' ? 'active' : '' }}" id="master-tab" data-bs-toggle="pill" data-bs-target="#master-pane" type="button" role="tab" aria-selected="{{ $activeTab === 'master' ? 'true' : 'false' }}">
                <i class="bi bi-table me-2"></i>Employee Code Master List <span class="badge bg-secondary ms-1 rounded-pill">{{ $employees->total() }}</span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="codeSettingsTabsContent">

        <!-- TAB 1: CODE CONFIGURATION -->
        <div class="tab-pane fade {{ $activeTab !== 'master' ? 'show active' : '' }}" id="config-pane" role="tabpanel" aria-labelledby="config-tab">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border rounded-3 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark" style="font-size: 14.5px;">
                                <i class="bi bi-gear-wide-connected text-primary me-2"></i>Sequential Pattern Parameters
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5">
                                Active Policy: {{ $configuration->is_active ? 'Enforced' : 'Paused' }}
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

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Format Template Pattern <span class="text-danger">*</span></label>
                                        <input type="text" name="format_template" id="formatTemplateInput" class="form-control font-monospace" value="{{ old('format_template', $configuration->format_template) }}" required>
                                        <div class="form-text" style="font-size: 11.5px;">Tokens: <code>{COMPANY}</code>, <code>{PREFIX}</code>, <code>{BRANCH}</code>, <code>{DEPT}</code>, <code>{FY}</code>, <code>{SEQUENCE}</code></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Base Prefix <span class="text-danger">*</span></label>
                                        <input type="text" name="prefix" id="prefixInput" class="form-control text-uppercase" value="{{ old('prefix', $configuration->prefix) }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Token Separator</label>
                                        <input type="text" name="separator" id="separatorInput" class="form-control" value="{{ old('separator', $configuration->separator) }}" maxlength="3">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Company Prefix Token</label>
                                        <input type="text" name="company_prefix" id="companyPrefixInput" class="form-control text-uppercase" value="{{ old('company_prefix', $configuration->company_prefix) }}" placeholder="e.g. VRE">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Branch Prefix Default</label>
                                        <input type="text" name="branch_prefix" id="branchPrefixInput" class="form-control text-uppercase" value="{{ old('branch_prefix', $configuration->branch_prefix) }}" placeholder="e.g. PN">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Department Prefix Default</label>
                                        <input type="text" name="department_prefix" id="deptPrefixInput" class="form-control text-uppercase" value="{{ old('department_prefix', $configuration->department_prefix) }}" placeholder="e.g. ENG">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Sequence Number Length (Padding) <span class="text-danger">*</span></label>
                                        <select name="number_length" id="numLengthSelect" class="form-select">
                                            @for($i = 3; $i <= 8; $i++)
                                                <option value="{{ $i }}" {{ old('number_length', $configuration->number_length) == $i ? 'selected' : '' }}>
                                                    {{ $i }} Digits (e.g. {{ str_pad('1', $i, '0', STR_PAD_LEFT) }})
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Starting Sequence Number <span class="text-danger">*</span></label>
                                        <input type="number" name="starting_sequence_number" id="startSeqInput" class="form-control" value="{{ old('starting_sequence_number', $configuration->starting_sequence_number) }}" min="1" required>
                                    </div>

                                    <div class="col-12"><hr class="my-3"></div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Uniqueness Verification Scope <span class="text-danger">*</span></label>
                                        <select name="uniqueness_scope" class="form-select">
                                            <option value="company" {{ old('uniqueness_scope', $configuration->uniqueness_scope) === 'company' ? 'selected' : '' }}>Company Scope (Isolated per company entity)</option>
                                            <option value="tenant" {{ old('uniqueness_scope', $configuration->uniqueness_scope) === 'tenant' ? 'selected' : '' }}>Tenant Scope (Unique across all group companies)</option>
                                            <option value="global" {{ old('uniqueness_scope', $configuration->uniqueness_scope) === 'global' ? 'selected' : '' }}>Global Platform Scope</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size: 13px;">Financial Year Format</label>
                                        <input type="text" name="financial_year_prefix" class="form-control" value="{{ old('financial_year_prefix', $configuration->financial_year_prefix ?? date('Y')) }}" placeholder="2026 or 2627">
                                    </div>

                                    <div class="col-12">
                                        <div class="bg-light p-3 rounded-3 border">
                                            <h6 class="fw-bold mb-2 text-dark" style="font-size: 13px;"><i class="bi bi-clock-history me-1 text-primary"></i>Sequence Reset Governance</h6>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="reset_sequence_per_company" value="1" id="resetCompany" {{ old('reset_sequence_per_company', $configuration->reset_sequence_per_company) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="resetCompany" style="font-size: 13px;">
                                                    Independent Sequence per Enterprise Company (Recommended for multi-company isolation)
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="reset_sequence_per_branch" value="1" id="resetBranch" {{ old('reset_sequence_per_branch', $configuration->reset_sequence_per_branch) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="resetBranch" style="font-size: 13px;">
                                                    Reset Sequence per Site/Branch Location
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="reset_sequence_annually" value="1" id="resetAnnual" {{ old('reset_sequence_annually', $configuration->reset_sequence_annually) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="resetAnnual" style="font-size: 13px;">
                                                    Reset Sequence Annually with Financial Year (April 1st)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_manual_employee_code" value="1" id="allowManual" {{ old('allow_manual_employee_code', $configuration->allow_manual_employee_code) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="allowManual" style="font-size: 13px;">
                                                    Allow Manual Employee Code Override on Onboarding (Audited)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 text-end mt-4">
                                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                            <i class="bi bi-check2-circle me-1"></i> Save Configuration Policy
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Template Tokens Reference Card -->
                <div class="col-lg-4">
                    <div class="card border rounded-3 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bi bi-info-circle text-primary me-2"></i>Template Tokens</span>
                        </div>
                        <div class="card-body p-3" style="font-size: 12.5px;">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><code>{COMPANY}</code>: Active enterprise code (e.g. <code>VRE</code>)</li>
                                <li class="mb-2"><code>{PREFIX}</code>: Base classification prefix (e.g. <code>EMP</code>)</li>
                                <li class="mb-2"><code>{BRANCH}</code>: Site / Branch identifier (e.g. <code>PUN</code>)</li>
                                <li class="mb-2"><code>{DEPT}</code>: Department abbreviation (e.g. <code>ENG</code>)</li>
                                <li class="mb-2"><code>{FY}</code>: Financial year code (e.g. <code>2627</code>)</li>
                                <li><code>{SEQUENCE}</code>: Zero-padded sequential counter (e.g. <code>1001</code>)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card border rounded-3 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom">
                            <span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bi bi-shield-lock text-success me-2"></i>Concurrency Guarantee</span>
                        </div>
                        <div class="card-body p-3 text-muted" style="font-size: 12.5px;">
                            Sequential counters utilize row-level pessimistic locking (<code>SELECT ... FOR UPDATE</code>) on <code>employee_code_sequences</code> to prevent duplicate codes during concurrent candidate onboarding.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: CODE MASTER REGISTER -->
        <div class="tab-pane fade {{ $activeTab === 'master' ? 'show active' : '' }}" id="master-pane" role="tabpanel" aria-labelledby="master-tab">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('admin.employee-codes.settings') }}" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="master">
                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search Code, Name..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="location_id" class="form-select form-select-sm">
                                <option value="">All Branches / Sites</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="code_generation_method" class="form-select form-select-sm">
                                <option value="">All Generation Methods</option>
                                <option value="auto" {{ request('code_generation_method') === 'auto' ? 'selected' : '' }}>Automatic Sequence</option>
                                <option value="manual" {{ request('code_generation_method') === 'manual' ? 'selected' : '' }}>Manual Override</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-dark btn-sm px-3 flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                            <a href="{{ route('admin.employee-codes.settings', ['tab' => 'master']) }}" class="btn btn-outline-secondary btn-sm px-2.5" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border rounded-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light text-muted text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-3 py-3">Employee Code</th>
                                <th>Candidate / Employee</th>
                                <th>Branch Site</th>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Generation Method</th>
                                <th>Assigned Date</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $emp)
                                <tr>
                                    <td class="ps-3 py-3">
                                        <span class="badge bg-dark-subtle text-dark border font-monospace px-2.5 py-1.5" style="font-size: 12.5px;">
                                            {{ $emp->employee_code }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('employees.show', $emp->id) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $emp->full_name }}
                                        </a>
                                        <div class="text-muted" style="font-size: 11.5px;">ID: #{{ $emp->id }}</div>
                                    </td>
                                    <td>{{ $emp->location?->name ?? '—' }}</td>
                                    <td>{{ $emp->department?->name ?? '—' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $emp->employee_type }}</span></td>
                                    <td>
                                        @if($emp->code_generation_method === 'manual')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                <i class="bi bi-hand-index me-1"></i>Manual Override
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                                <i class="bi bi-cpu me-1"></i>Automatic Sequence
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $emp->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('employees.show', $emp->id) }}" class="btn btn-outline-secondary btn-xs py-1 px-2" title="View Dossier">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No employee codes match the specified filter criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($employees->hasPages())
                    <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} assigned codes</small>
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const refreshBtn = document.getElementById('refreshPreviewBtn');
    const previewBadge = document.getElementById('livePreviewBadge');
    
    function fetchLivePreview() {
        const payload = {
            format_template: document.getElementById('formatTemplateInput')?.value,
            prefix: document.getElementById('prefixInput')?.value,
            company_prefix: document.getElementById('companyPrefixInput')?.value,
            branch_prefix: document.getElementById('branchPrefixInput')?.value,
            department_prefix: document.getElementById('deptPrefixInput')?.value,
            number_length: document.getElementById('numLengthSelect')?.value,
            starting_sequence_number: document.getElementById('startSeqInput')?.value,
            separator: document.getElementById('separatorInput')?.value,
            _token: '{{ csrf_token() }}'
        };

        if (previewBadge) previewBadge.textContent = 'Generating...';

        fetch('{{ route("admin.employee-codes.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.preview && previewBadge) {
                previewBadge.textContent = data.preview;
            }
        })
        .catch(() => {
            if (previewBadge) previewBadge.textContent = 'Preview Error';
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', fetchLivePreview);
    }
});
</script>
@endpush

