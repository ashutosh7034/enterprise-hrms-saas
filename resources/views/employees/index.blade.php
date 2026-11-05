@extends('layouts.app')

@section('title', 'Workforce Directory — Employee Master & Lifecycle')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold text-dark" style="letter-spacing: -0.02em;">Workforce Master Directory</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Manage employee records, employment details, transfers and lifecycle information.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- 1. Primary Action: Add Employee -->
        <a href="{{ route('employees.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-xs fw-semibold px-3">
            <i class="bi bi-person-plus-fill"></i>
            <span>Add Employee</span>
        </a>

        <!-- 1b. Drafts Action: Onboarding Drafts -->
        @php
            $draftCount = \App\Models\OnboardingDraft::where('company_id', app(\App\Services\CompanyContextService::class)->getActiveCompanyId())->where('status', 'draft')->count();
        @endphp
        <a href="{{ route('employees.drafts.index') }}" class="btn btn-outline-teal d-flex align-items-center gap-2 shadow-xs fw-semibold px-3" style="color: #0D9488; border-color: #0D9488;" title="View and resume in-progress onboarding drafts">
            <i class="bi bi-clock-history"></i>
            <span>Drafts</span>
            @if($draftCount > 0)
                <span class="badge text-white rounded-pill px-1.5 py-0.5" style="background-color: #0D9488; font-size: 11px;">{{ $draftCount }}</span>
            @endif
        </a>

        <!-- 2. Secondary Action: Bulk Import -->
        <div class="btn-group shadow-xs">
            <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2 fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                <i class="bi bi-cloud-arrow-up-fill"></i>
                <span>Bulk Import</span>
            </button>
            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                        <i class="bi bi-window-stack text-teal"></i>
                        <span>Open Quick Import Wizard</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('employees.bulk-import.index') }}">
                        <i class="bi bi-arrows-fullscreen text-primary"></i>
                        <span>Dedicated Import Workspace</span>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li class="dropdown-header text-uppercase" style="font-size: 10.5px;">Download Templates</li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('employees.bulk-import.template', 'xlsx') }}">
                        <i class="bi bi-file-earmark-excel text-success"></i>
                        <span>Excel Template (.xlsx)</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('employees.bulk-import.template', 'csv') }}">
                        <i class="bi bi-filetype-csv text-secondary"></i>
                        <span>CSV Template (.csv)</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 3. Export Action: Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 shadow-xs fw-semibold px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Export current filtered view">
                <i class="bi bi-download"></i>
                <span>Export</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                        <i class="bi bi-filetype-csv text-success fs-6"></i>
                        <span>Export to CSV (.csv)</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                        <i class="bi bi-file-earmark-excel text-success fs-6"></i>
                        <span>Export to Excel (.xlsx)</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- 4. Consolidated Employee Code Settings -->
        <a href="{{ route('admin.employee-codes.settings') }}" class="btn btn-outline-dark d-flex align-items-center gap-2 shadow-xs fw-semibold px-3" title="Format configuration, pattern rules, and code master register">
            <i class="bi bi-upc-scan"></i>
            <span>Employee Code Settings</span>
        </a>
    </div>
</div>

<!-- Search & Filters -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small fw-semibold mb-1">Search Directory</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Code, name, phone, ticket #..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">Branch / Location</label>
                <select name="location_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">Designation</label>
                <select name="designation_id" class="form-select form-select-sm">
                    <option value="">All Designations</option>
                    @foreach($designations as $desig)
                        <option value="{{ $desig->id }}" {{ request('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-semibold mb-1">Employment Type</label>
                <select name="employment_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="Permanent" {{ request('employment_type') == 'Permanent' ? 'selected' : '' }}>Permanent</option>
                    <option value="Intern" {{ request('employment_type') == 'Intern' ? 'selected' : '' }}>Intern</option>
                    <option value="Contract" {{ request('employment_type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                    <option value="Probationary" {{ request('employment_type') == 'Probationary' ? 'selected' : '' }}>Probationary</option>
                    <option value="Trainee" {{ request('employment_type') == 'Trainee' ? 'selected' : '' }}>Trainee</option>
                </select>
            </div>

            <!-- Second Row Filters -->
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">Confirmation Status</label>
                <select name="confirmation_status" class="form-select form-select-sm">
                    <option value="">All Confirmation</option>
                    <option value="Confirmed" {{ request('confirmation_status') == 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="On Probation" {{ request('confirmation_status') == 'On Probation' ? 'selected' : '' }}>On Probation</option>
                    <option value="Confirmation Due" {{ request('confirmation_status') == 'Confirmation Due' ? 'selected' : '' }}>Confirmation Due</option>
                    <option value="Probation Extended" {{ request('confirmation_status') == 'Probation Extended' ? 'selected' : '' }}>Probation Extended</option>
                    <option value="Not Applicable" {{ request('confirmation_status') == 'Not Applicable' ? 'selected' : '' }}>Not Applicable</option>
                    <option value="Rejected / Discontinued" {{ request('confirmation_status') == 'Rejected / Discontinued' ? 'selected' : '' }}>Rejected / Discontinued</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">Reporting Manager</label>
                <select name="reporting_manager_id" class="form-select form-select-sm">
                    <option value="">All Managers</option>
                    @foreach($managers as $mgr)
                        <option value="{{ $mgr->id }}" {{ request('reporting_manager_id') == $mgr->id ? 'selected' : '' }}>{{ $mgr->full_name }} ({{ $mgr->employee_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">DOJ From</label>
                <input type="date" name="doj_from" class="form-control form-control-sm" value="{{ request('doj_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">DOJ To</label>
                <input type="date" name="doj_to" class="form-control form-control-sm" value="{{ request('doj_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-semibold mb-1">Account Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="Suspended" {{ request('status') == 'Suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="Exited" {{ request('status') == 'Exited' ? 'selected' : '' }}>Exited</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-brand btn-sm w-100"><i class="bi bi-funnel-fill me-1"></i>Apply</button>
                @if(request()->anyFilled(['search', 'department_id', 'location_id', 'designation_id', 'employment_type', 'confirmation_status', 'reporting_manager_id', 'doj_from', 'doj_to', 'status']))
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Employees Master Table -->
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold fs-6">Master Workforce Roster</span>
            <span class="badge bg-primary-subtle text-primary border rounded-pill">{{ $employees->total() }} Employees</span>
        </div>
        <div class="text-muted small">
            Displaying Page {{ $employees->currentPage() }} of {{ $employees->lastPage() }}
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
                <tr style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.3px;">
                    <th style="min-width: 140px;">Code / ID</th>
                    <th style="min-width: 200px;">Employee</th>
                    <th>Company</th>
                    <th style="min-width: 160px;">Branch (Current vs Joining)</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Type</th>
                    <th style="min-width: 110px;">DOJ</th>
                    <th style="min-width: 130px;">Confirmation</th>
                    <th style="min-width: 140px;">Manager</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                    <tr>
                        <td>
                            <div class="fw-bold code-font text-dark">{{ $emp->employee_code }}</div>
                            <div class="text-muted small" style="font-size: 11px;">ID: #{{ $emp->id }}</div>
                            @if($emp->code_generation_method)
                                <span class="badge bg-light text-muted border" style="font-size: 9.5px;">{{ $emp->code_generation_method }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: #0D9488; color: #FFFFFF; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 13px; overflow: hidden; flex-shrink: 0;">
                                    @if($emp->profile_photo_path)
                                        <img src="{{ asset('storage/' . $emp->profile_photo_path) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        {{ $emp->initials }}
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('employees.show', $emp->id) }}" class="text-decoration-none fw-semibold text-dark">
                                        {{ $emp->title ? $emp->title . ' ' : '' }}{{ $emp->full_name }}
                                    </a>
                                    @if($emp->ticket_number)
                                        <div class="text-muted small" style="font-size: 11px;">Ticket: {{ $emp->ticket_number }}</div>
                                    @elseif($emp->email)
                                        <div class="text-muted small" style="font-size: 11px;">{{ $emp->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border code-font">{{ $emp->company?->code ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $emp->location?->name ?? '—' }}</div>
                            @if($emp->joiningLocation && $emp->joining_location_id !== $emp->location_id)
                                <div class="small text-muted" style="font-size: 11px;" title="Original Joining Branch">
                                    <i class="bi bi-geo-alt-fill text-warning me-1"></i>Joined: {{ $emp->joiningLocation->name }}
                                </div>
                            @else
                                <span class="text-muted small" style="font-size: 11px;">Base Branch</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-dark">{{ $emp->department?->name ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-dark">{{ $emp->designation?->title ?? '—' }}</div>
                        </td>
                        <td>
                            @php
                                $type = $emp->employment_type ?? $emp->employee_type ?? 'Permanent';
                            @endphp
                            @if($type === 'Permanent')
                                <span class="badge bg-success-subtle text-success border">Permanent</span>
                            @elseif($type === 'Intern')
                                <span class="badge bg-info-subtle text-info border">Intern</span>
                            @elseif($type === 'Contract')
                                <span class="badge bg-warning-subtle text-warning-emphasis border">Contract</span>
                            @elseif($type === 'Probationary')
                                <span class="badge bg-purple-subtle text-primary border">Probationary</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">{{ $type }}</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->date_of_joining)
                                <div class="fw-medium text-dark">{{ $emp->date_of_joining->format('d M Y') }}</div>
                                <div class="text-muted small" style="font-size: 11px;">{{ $emp->date_of_joining->diffForHumans(null, true) }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $cStatus = $emp->confirmation_status ?? 'On Probation';
                            @endphp
                            @if($cStatus === 'Confirmed')
                                <span class="badge bg-success-subtle text-success border"><i class="bi bi-patch-check-fill me-1"></i>Confirmed</span>
                            @elseif($cStatus === 'Confirmation Due')
                                <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-clock-history me-1"></i>Due</span>
                            @elseif($cStatus === 'Probation Extended')
                                <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-arrow-repeat me-1"></i>Extended</span>
                            @elseif($cStatus === 'On Probation')
                                <span class="badge bg-info-subtle text-info border"><i class="bi bi-hourglass-split me-1"></i>On Probation</span>
                            @elseif($cStatus === 'Not Applicable')
                                <span class="badge bg-light text-muted border">N/A</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">{{ $cStatus }}</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->reportingManager)
                                <div class="text-dark fw-medium">{{ $emp->reportingManager->full_name }}</div>
                                <div class="text-muted code-font" style="font-size: 11px;">{{ $emp->reportingManager->employee_code }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($emp->status === 'Active')
                                <span class="badge bg-success-subtle text-success border">Active</span>
                            @elseif($emp->status === 'Inactive')
                                <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                            @elseif($emp->status === 'Suspended')
                                <span class="badge bg-danger-subtle text-danger border">Suspended</span>
                            @else
                                <span class="badge bg-light text-muted border">{{ $emp->status ?? 'Active' }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown d-inline-block">
                                <a href="{{ route('employees.show', $emp->id) }}" class="btn btn-outline-secondary btn-sm p-1 px-2" title="View Master Dossier">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <button class="btn btn-outline-primary btn-sm p-1 px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Lifecycle Actions">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><h6 class="dropdown-header small text-uppercase">Lifecycle Actions</h6></li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('employees.show', $emp->id) }}#lifecycle">
                                            <i class="bi bi-speedometer2 me-2 text-primary"></i>Lifecycle Hub
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('employees.show', $emp->id) }}?action=transfer#lifecycle">
                                            <i class="bi bi-arrow-left-right me-2 text-warning"></i>Transfer Branch
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('employees.show', $emp->id) }}?action=convert#lifecycle">
                                            <i class="bi bi-person-check-fill me-2 text-success"></i>Convert Intern
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('employees.show', $emp->id) }}?action=confirm#lifecycle">
                                            <i class="bi bi-patch-check me-2 text-info"></i>Manage Confirmation
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('employees.edit', $emp->id) }}">
                                            <i class="bi bi-pencil me-2 text-secondary"></i>Edit Dossier
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-people display-6 d-block mb-2 text-secondary"></i>
                            <div style="font-size: 15px; font-weight: 500;">No employees found matching filter criteria</div>
                            <div style="font-size: 13px;">Onboard a new candidate or adjust active filters.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $employees->links() }}
        </div>
    @endif
</div>

<!-- ======================================================== -->
<!-- BULK EMPLOYEE IMPORT MODAL (Consolidated Step Flow)      -->
<!-- ======================================================== -->
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-teal text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" id="bulkImportModalLabel">Bulk Employee Import Wizard</h6>
                        <small style="opacity: 0.85; font-size: 11.5px;">Step-by-step roster ingestion with automated duplicate detection & schema validation</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- STEP 1 & 2: Template Download & File Upload -->
                <div id="modalStepUpload">
                    <!-- Template Download Strip -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="fw-bold text-dark d-block" style="font-size: 13px;"><i class="bi bi-download text-teal me-1"></i>Step 1: Download Standard Template</span>
                                <small class="text-muted" style="font-size: 11.5px;">Includes sample data and company reference codes.</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('employees.bulk-import.template', 'xlsx') }}" class="btn btn-outline-success btn-xs py-1 px-2.5 fw-semibold">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Excel (.xlsx)
                                </a>
                                <a href="{{ route('employees.bulk-import.template', 'csv') }}" class="btn btn-outline-secondary btn-xs py-1 px-2.5 fw-semibold">
                                    <i class="bi bi-filetype-csv me-1"></i> CSV (.csv)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- File Drop Area -->
                    <form id="modalUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="border border-2 border-dashed rounded-3 p-4 text-center bg-white" id="modalDropzone" style="cursor: pointer;">
                            <i class="bi bi-file-earmark-arrow-up text-teal fs-1"></i>
                            <h6 class="fw-bold text-dark mt-2 mb-1" style="font-size: 14px;">Step 2: Choose Excel or CSV Roster File</h6>
                            <p class="text-muted small mb-2">Drag and drop file here, or click to browse</p>
                            <input type="file" name="file" id="modalFileInput" class="d-none" accept=".xlsx,.xls,.csv,.txt">
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="document.getElementById('modalFileInput').click();">
                                <i class="bi bi-folder2-open me-1"></i> Select File
                            </button>
                            <div id="modalSelectedFileInfo" class="mt-2 fw-semibold text-primary small d-none"></div>
                        </div>
                    </form>
                </div>

                <!-- STEP 3 & 4: Validation Results & Data Preview -->
                <div id="modalStepValidate" class="d-none">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                        <span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bi bi-check2-circle text-teal me-1"></i>Step 3 & 4: Validation Metrics & Preview</span>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2" id="modalReUploadBtn">
                            <i class="bi bi-arrow-repeat me-1"></i> Change File
                        </button>
                    </div>

                    <!-- Metrics Badges -->
                    <div class="row g-2 mb-3">
                        <div class="col-3">
                            <div class="p-2 bg-light rounded border text-center">
                                <small class="text-muted d-block" style="font-size: 10px;">TOTAL</small>
                                <strong class="fs-5 text-dark" id="modalStatTotal">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 bg-success-subtle rounded border border-success-subtle text-center">
                                <small class="text-success d-block" style="font-size: 10px;">VALID</small>
                                <strong class="fs-5 text-success" id="modalStatValid">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 bg-danger-subtle rounded border border-danger-subtle text-center">
                                <small class="text-danger d-block" style="font-size: 10px;">INVALID</small>
                                <strong class="fs-5 text-danger" id="modalStatInvalid">0</strong>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="p-2 bg-warning-subtle rounded border border-warning-subtle text-center">
                                <small class="text-warning-emphasis d-block" style="font-size: 10px;">DUPLICATE</small>
                                <strong class="fs-5 text-warning-emphasis" id="modalStatDuplicate">0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Error Alert & Download Error Report -->
                    <div id="modalErrorsBox" class="p-2.5 bg-danger-subtle rounded border border-danger-subtle mb-3 d-none">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                            <span class="small fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Validation Errors (<span id="modalErrCount">0</span>)</span>
                            <form action="{{ route('employees.bulk-import.export-errors') }}" method="POST" id="modalErrForm">
                                @csrf
                                <input type="hidden" name="batch_token" id="modalBatchTokenInput" value="">
                                <button type="submit" class="btn btn-danger btn-xs py-0.5 px-2">Download Error CSV</button>
                            </form>
                        </div>
                        <div style="max-height: 100px; overflow-y: auto;">
                            <ul class="mb-0 ps-3 small text-danger" id="modalErrorsList" style="font-size: 11.5px;"></ul>
                        </div>
                    </div>

                    <!-- Records Preview Table -->
                    <div class="table-responsive border rounded mb-3" style="max-height: 200px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 11.5px;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>DOB</th>
                                    <th>Phone</th>
                                    <th>Branch</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="modalPreviewBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- STEP 5 & 6: Success Completion Result -->
                <div id="modalStepSuccess" class="text-center py-4 d-none">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-2" style="width: 56px; height: 56px;">
                        <i class="bi bi-check2-all fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1" id="modalSuccessHeading">Import Completed!</h5>
                    <p class="text-muted small mb-3" id="modalSuccessDesc">Employee records have been committed to the live directory.</p>
                    <a href="{{ route('employees.index') }}" class="btn btn-teal btn-sm px-4 fw-semibold" style="background-color: #0D9488; color: #fff;">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Workforce Directory
                    </a>
                </div>
            </div>

            <div class="modal-footer bg-light py-2.5 px-4 d-flex justify-content-between">
                <a href="{{ route('employees.bulk-import.index') }}" class="small text-teal text-decoration-none fw-semibold">
                    <i class="bi bi-arrows-fullscreen me-1"></i> Open Full Import Screen
                </a>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-teal btn-sm px-4 fw-semibold" id="modalActionBtn" disabled style="background-color: #0D9488; color: #fff;">
                        <i class="bi bi-shield-check me-1"></i> Validate File
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('modalFileInput');
    const selectedInfo = document.getElementById('modalSelectedFileInfo');
    const actionBtn = document.getElementById('modalActionBtn');
    const uploadForm = document.getElementById('modalUploadForm');

    const stepUpload = document.getElementById('modalStepUpload');
    const stepValidate = document.getElementById('modalStepValidate');
    const stepSuccess = document.getElementById('modalStepSuccess');

    let modalToken = null;
    let currentPhase = 'upload'; // 'upload' | 'confirm'

    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            selectedInfo.textContent = `File: ${this.files[0].name} (${(this.files[0].size / 1024).toFixed(1)} KB)`;
            selectedInfo.classList.remove('d-none');
            actionBtn.removeAttribute('disabled');
            actionBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Parse & Validate';
            currentPhase = 'upload';
        }
    });

    actionBtn.addEventListener('click', function () {
        if (currentPhase === 'upload') {
            if (!fileInput.files || !fileInput.files[0]) return;

            actionBtn.setAttribute('disabled', 'true');
            actionBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Validating...';

            const formData = new FormData(uploadForm);
            fetch('{{ route("employees.bulk-import.validate") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                actionBtn.removeAttribute('disabled');
                if (!data.success) {
                    alert(data.message || 'Validation failed.');
                    actionBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Parse & Validate';
                    return;
                }

                modalToken = data.batch_token;
                document.getElementById('modalBatchTokenInput').value = data.batch_token;

                // Update Stats
                document.getElementById('modalStatTotal').textContent = data.total_rows;
                document.getElementById('modalStatValid').textContent = data.valid_count;
                document.getElementById('modalStatInvalid').textContent = data.invalid_count;
                document.getElementById('modalStatDuplicate').textContent = data.duplicate_count;

                // Errors
                const errBox = document.getElementById('modalErrorsBox');
                const errList = document.getElementById('modalErrorsList');
                errList.innerHTML = '';
                if (data.errors && data.errors.length > 0) {
                    errBox.classList.remove('d-none');
                    document.getElementById('modalErrCount').textContent = data.errors.length;
                    data.errors.slice(0, 15).forEach(e => {
                        const li = document.createElement('li');
                        li.textContent = `Row ${e.row} (${e.name}): ${e.message}`;
                        errList.appendChild(li);
                    });
                } else {
                    errBox.classList.add('d-none');
                }

                // Table
                const tbody = document.getElementById('modalPreviewBody');
                tbody.innerHTML = '';
                (data.preview_records || []).slice(0, 10).forEach(r => {
                    const tr = document.createElement('tr');
                    const badge = r.has_errors 
                        ? `<span class="badge bg-danger text-white">Err</span>`
                        : `<span class="badge bg-success text-white">Valid</span>`;
                    tr.innerHTML = `
                        <td>${r.row}</td>
                        <td class="fw-semibold">${r.name}</td>
                        <td>${r.gender}</td>
                        <td>${r.dob}</td>
                        <td>${r.phone}</td>
                        <td>${r.location}</td>
                        <td>${r.department}</td>
                        <td>${badge}</td>
                    `;
                    tbody.appendChild(tr);
                });

                // Transition UI
                stepUpload.classList.add('d-none');
                stepValidate.classList.remove('d-none');

                if (data.can_proceed && data.valid_count > 0) {
                    currentPhase = 'confirm';
                    actionBtn.innerHTML = `<i class="bi bi-box-arrow-in-down me-1"></i> Confirm & Import (${data.valid_count})`;
                } else {
                    actionBtn.setAttribute('disabled', 'true');
                    actionBtn.innerHTML = 'Cannot Ingest (0 Valid Records)';
                }
            })
            .catch(() => {
                actionBtn.removeAttribute('disabled');
                actionBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Parse & Validate';
                alert('File validation error occurred.');
            });
        } else if (currentPhase === 'confirm') {
            if (!modalToken) return;

            actionBtn.setAttribute('disabled', 'true');
            actionBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Ingesting...';

            fetch('{{ route("employees.bulk-import.confirm") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ batch_token: modalToken })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    stepValidate.classList.add('d-none');
                    stepSuccess.classList.remove('d-none');
                    document.getElementById('modalSuccessHeading').textContent = `${data.imported_count} Employees Successfully Ingested!`;
                    actionBtn.classList.add('d-none');
                } else {
                    alert(data.message || 'Import failed.');
                    actionBtn.removeAttribute('disabled');
                    actionBtn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Retry Confirm';
                }
            })
            .catch(() => {
                alert('Server error occurred during execution.');
                actionBtn.removeAttribute('disabled');
                actionBtn.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Retry Confirm';
            });
        }
    });

    document.getElementById('modalReUploadBtn').addEventListener('click', function () {
        stepValidate.classList.add('d-none');
        stepUpload.classList.remove('d-none');
        fileInput.value = '';
        selectedInfo.classList.add('d-none');
        actionBtn.setAttribute('disabled', 'true');
        actionBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Parse & Validate';
        currentPhase = 'upload';
    });
});
</script>
@endpush
