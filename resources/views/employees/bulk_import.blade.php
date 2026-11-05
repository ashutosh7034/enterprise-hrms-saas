@extends('layouts.app')

@section('title', 'Bulk Employee Import — Step-by-Step Roster Ingestion')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 13px;">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Workforce Directory</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bulk Employee Import</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-cloud-arrow-up-fill text-teal me-2"></i>Bulk Employee Import</h4>
                <span class="badge bg-teal-subtle text-teal border border-teal-subtle rounded-pill px-2.5 py-1" style="font-size: 11px;">
                    Fast Mass Ingestion Engine
                </span>
            </div>
            <p class="text-muted mb-0 mt-1" style="font-size: 13.5px;">Seamlessly ingest candidate rosters, contractual workers, and workforce records using validated Excel/CSV templates.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Back to Directory
            </a>
            <a href="{{ route('employees.create') }}" class="btn btn-outline-primary btn-sm px-3 fw-semibold">
                <i class="bi bi-person-plus me-1"></i> Manual Onboarding Wizard
            </a>
        </div>
    </div>

    <!-- Stepper Navigation Bar -->
    <div class="card border rounded-3 shadow-xs mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-center" id="wizardStepper">
                <div class="step-indicator active d-flex align-items-center gap-2" id="stepIndicator-1">
                    <span class="badge rounded-circle bg-teal text-white p-2" style="width: 28px; height: 28px;">1</span>
                    <span class="fw-semibold small text-dark">Download Template</span>
                </div>
                <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
                <div class="step-indicator d-flex align-items-center gap-2" id="stepIndicator-2">
                    <span class="badge rounded-circle bg-light text-secondary border p-2" style="width: 28px; height: 28px;">2</span>
                    <span class="fw-semibold small text-muted">Upload File</span>
                </div>
                <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
                <div class="step-indicator d-flex align-items-center gap-2" id="stepIndicator-3">
                    <span class="badge rounded-circle bg-light text-secondary border p-2" style="width: 28px; height: 28px;">3</span>
                    <span class="fw-semibold small text-muted">Preview & Validate</span>
                </div>
                <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
                <div class="step-indicator d-flex align-items-center gap-2" id="stepIndicator-4">
                    <span class="badge rounded-circle bg-light text-secondary border p-2" style="width: 28px; height: 28px;">4</span>
                    <span class="fw-semibold small text-muted">Confirm & Ingest</span>
                </div>
                <i class="bi bi-chevron-right text-muted d-none d-md-inline"></i>
                <div class="step-indicator d-flex align-items-center gap-2" id="stepIndicator-5">
                    <span class="badge rounded-circle bg-light text-secondary border p-2" style="width: 28px; height: 28px;">5</span>
                    <span class="fw-semibold small text-muted">Import Results</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN STEPPER PANES -->
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- STEP 1 & 2: Template & Upload Card -->
            <div class="card border rounded-3 shadow-sm mb-4" id="uploadStepCard">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark" style="font-size: 14px;">
                        <i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>Upload Workforce File
                    </span>
                    <span class="badge bg-light text-dark border">Accepts .xlsx, .xls, .csv (Max 10MB)</span>
                </div>
                <div class="card-body p-4">
                    <!-- Template Download Buttons -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-1" style="font-size: 13.5px;"><i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Step 1: Download Standard Import Template</h6>
                                <p class="text-muted mb-0 small">Templates include sample workforce records and active company branch & department reference master sheets.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('employees.bulk-import.template', 'xlsx') }}" class="btn btn-outline-success btn-sm fw-semibold">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Excel (.xlsx)
                                </a>
                                <a href="{{ route('employees.bulk-import.template', 'csv') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                                    <i class="bi bi-filetype-csv me-1"></i> CSV (.csv)
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- File Dropzone -->
                    <form id="bulkImportUploadForm" enctype="multipart/form-data">
                        @csrf
                        <div class="border border-2 border-dashed rounded-3 p-5 text-center bg-white" id="dropzoneArea" style="cursor: pointer; transition: all 0.2s;">
                            <i class="bi bi-cloud-arrow-up text-teal" style="font-size: 48px;"></i>
                            <h6 class="fw-bold text-dark mt-2 mb-1">Step 2: Drag & drop your completed file here, or click to browse</h6>
                            <p class="text-muted small mb-3">Upload your populated Excel or CSV spreadsheet to begin automated validation.</p>
                            <input type="file" name="file" id="importFileInput" class="d-none" accept=".xlsx,.xls,.csv,.txt">
                            <button type="button" class="btn btn-outline-primary btn-sm px-4 fw-semibold" onclick="document.getElementById('importFileInput').click();">
                                <i class="bi bi-folder2-open me-1"></i> Browse Files
                            </button>
                            <div id="selectedFileInfo" class="mt-3 fw-semibold text-primary d-none"></div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-teal px-4 fw-semibold" id="validateUploadBtn" disabled style="background-color: #0D9488; color: #fff;">
                                <i class="bi bi-shield-check me-1"></i> Parse & Validate Records &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STEP 3 & 4: Validation Summary & Data Preview Card -->
            <div class="card border rounded-3 shadow-sm mb-4 d-none" id="validationPreviewCard">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="fw-bold text-dark" style="font-size: 14px;">
                        <i class="bi bi-check2-all text-success me-2"></i>Step 3 & 4: Validation Results & Data Preview
                    </span>
                    <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2.5" id="reUploadBtn">
                        <i class="bi bi-arrow-repeat me-1"></i> Upload Different File
                    </button>
                </div>
                <div class="card-body p-4">
                    <!-- Metrics Bar -->
                    <div class="row g-2 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-light rounded border text-center">
                                <small class="text-muted text-uppercase fw-semibold" style="font-size: 10.5px;">Total Rows</small>
                                <div class="fs-4 fw-bold text-dark" id="statTotalRows">0</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-success-subtle rounded border border-success-subtle text-center">
                                <small class="text-success text-uppercase fw-semibold" style="font-size: 10.5px;">Valid Records</small>
                                <div class="fs-4 fw-bold text-success" id="statValidRows">0</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-danger-subtle rounded border border-danger-subtle text-center">
                                <small class="text-danger text-uppercase fw-semibold" style="font-size: 10.5px;">Invalid / Error Rows</small>
                                <div class="fs-4 fw-bold text-danger" id="statInvalidRows">0</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 bg-warning-subtle rounded border border-warning-subtle text-center">
                                <small class="text-warning-emphasis text-uppercase fw-semibold" style="font-size: 10.5px;">Duplicate Records</small>
                                <div class="fs-4 fw-bold text-warning-emphasis" id="statDuplicateRows">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Alert if Blacklist Matched -->
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 d-none" id="blacklistAlertBox">
                        <i class="bi bi-shield-x fs-4"></i>
                        <div>
                            <strong>Security Halt:</strong> Candidate in uploaded batch matches Central Blacklist.
                        </div>
                    </div>

                    <!-- Errors Box & Download Report -->
                    <div id="validationErrorsContainer" class="mb-4 d-none">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-danger mb-0" style="font-size: 13px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Validation Errors Found (<span id="errorCountBadge">0</span>)</h6>
                            <form action="{{ route('employees.bulk-import.export-errors') }}" method="POST" id="downloadErrorsForm">
                                @csrf
                                <input type="hidden" name="batch_token" id="errorBatchTokenInput" value="">
                                <button type="submit" class="btn btn-outline-danger btn-xs py-1 px-2.5">
                                    <i class="bi bi-download me-1"></i> Download Error Report CSV
                                </button>
                            </form>
                        </div>
                        <div class="border rounded bg-light p-2" style="max-height: 180px; overflow-y: auto;">
                            <ul class="list-unstyled mb-0 small text-danger" id="validationErrorsList"></ul>
                        </div>
                    </div>

                    <!-- Preview Table -->
                    <h6 class="fw-bold text-dark mb-2" style="font-size: 13px;"><i class="bi bi-table me-1"></i>Batch Records Preview (First 15 Rows)</h6>
                    <div class="table-responsive border rounded mb-4" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Gender</th>
                                    <th>DOB</th>
                                    <th>Phone</th>
                                    <th>Branch</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>

                    <!-- Step 5 Confirmation Box -->
                    <div class="p-3 bg-teal-subtle rounded border border-teal-subtle d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h6 class="fw-bold text-teal mb-1" style="font-size: 13.5px;"><i class="bi bi-check-circle me-1"></i>Ready to Commit Validated Records</h6>
                            <p class="text-muted mb-0 small" id="confirmNote">Valid records will be ingested into the live workforce database with sequential employee codes.</p>
                        </div>
                        <button type="button" class="btn btn-teal px-4 fw-semibold" id="confirmImportBtn" style="background-color: #0D9488; color: #fff;">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Step 5: Confirm & Import Records
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 6: Import Results Card -->
            <div class="card border rounded-3 shadow-sm mb-4 d-none" id="importResultsCard">
                <div class="card-header bg-white py-3 border-bottom">
                    <span class="fw-bold text-success" style="font-size: 14px;">
                        <i class="bi bi-check-circle-fill me-2"></i>Step 6: Import Successfully Completed
                    </span>
                </div>
                <div class="card-body p-5 text-center">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-check2-all fs-1"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1" id="resultsSuccessTitle">Employees Imported Successfully!</h4>
                    <p class="text-muted mb-4" id="resultsSuccessSub">All validated candidate records have been registered, assigned employee codes, and placed into the workforce roster.</p>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('employees.index') }}" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-people me-1"></i> View Workforce Directory
                        </a>
                        <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="importAnotherBtn">
                            <i class="bi bi-plus-circle me-1"></i> Import Another Batch
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Validation Rules & Instructions Sidebar -->
        <div class="col-lg-4">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <span class="fw-bold text-dark" style="font-size: 13.5px;">
                        <i class="bi bi-shield-check text-teal me-2"></i>Enterprise Ingestion Rules
                    </span>
                </div>
                <div class="card-body p-3" style="font-size: 12.5px;">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Mandatory Columns:</strong> First Name, Last Name, Gender, Date of Birth, Phone, Date of Joining, Branch, Department, Designation.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Statutory Age Rule:</strong> Employees must be at least 18 years old on the date of import.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Automatic Code Generation:</strong> Leave custom employee code column empty to automatically generate formatted sequential codes according to active company policy.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Unique Uniqueness:</strong> Phone numbers, email addresses, and gate pass tokens are strictly validated against existing company employees.</li>
                        <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Central Blacklist Guard:</strong> Every candidate is screened against the centralized cross-company security blacklist.</li>
                        <li><i class="bi bi-check2 text-success me-1"></i> <strong>Forensic Audit Trail:</strong> Every batch and employee creation is logged immutably in the central audit ledger.</li>
                    </ul>
                </div>
            </div>

            <div class="card border rounded-3 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <span class="fw-bold text-dark" style="font-size: 13.5px;">
                        <i class="bi bi-question-circle text-primary me-2"></i>Need Help Formatting?
                    </span>
                </div>
                <div class="card-body p-3 text-muted" style="font-size: 12.5px;">
                    Ensure column headers match the template. Dates should follow <code>YYYY-MM-DD</code> format (e.g. <code>1996-08-25</code>). Check the Reference sheet inside the downloaded Excel template for exact branch and department names.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('importFileInput');
    const dropzone = document.getElementById('dropzoneArea');
    const selectedFileInfo = document.getElementById('selectedFileInfo');
    const validateBtn = document.getElementById('validateUploadBtn');
    const uploadForm = document.getElementById('bulkImportUploadForm');

    const uploadStepCard = document.getElementById('uploadStepCard');
    const validationPreviewCard = document.getElementById('validationPreviewCard');
    const importResultsCard = document.getElementById('importResultsCard');

    let currentBatchToken = null;

    // File selection UI
    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const f = this.files[0];
            selectedFileInfo.textContent = `Selected: ${f.name} (${(f.size / 1024).toFixed(1)} KB)`;
            selectedFileInfo.classList.remove('d-none');
            validateBtn.removeAttribute('disabled');
        }
    });

    // Drag and drop handlers
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.borderColor = '#0D9488';
        dropzone.style.backgroundColor = '#F0FDFA';
    });
    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.style.borderColor = '';
        dropzone.style.backgroundColor = '';
    });
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.style.borderColor = '';
        dropzone.style.backgroundColor = '';
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            fileInput.files = e.dataTransfer.files;
            const f = e.dataTransfer.files[0];
            selectedFileInfo.textContent = `Selected: ${f.name} (${(f.size / 1024).toFixed(1)} KB)`;
            selectedFileInfo.classList.remove('d-none');
            validateBtn.removeAttribute('disabled');
        }
    });

    // Upload & Validate submission
    uploadForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!fileInput.files || !fileInput.files[0]) return;

        validateBtn.setAttribute('disabled', 'true');
        validateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Parsing & Validating...';

        const formData = new FormData(this);

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
            validateBtn.removeAttribute('disabled');
            validateBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Parse & Validate Records &rarr;';

            if (!data.success) {
                alert(data.message || 'Validation error.');
                return;
            }

            currentBatchToken = data.batch_token;
            document.getElementById('errorBatchTokenInput').value = data.batch_token;

            // Update Statistics
            document.getElementById('statTotalRows').textContent = data.total_rows;
            document.getElementById('statValidRows').textContent = data.valid_count;
            document.getElementById('statInvalidRows').textContent = data.invalid_count;
            document.getElementById('statDuplicateRows').textContent = data.duplicate_count;

            // Security Blacklist box
            const blBox = document.getElementById('blacklistAlertBox');
            if (data.blacklisted_count > 0) {
                blBox.classList.remove('d-none');
            } else {
                blBox.classList.add('d-none');
            }

            // Errors container
            const errBox = document.getElementById('validationErrorsContainer');
            const errList = document.getElementById('validationErrorsList');
            errList.innerHTML = '';

            if (data.errors && data.errors.length > 0) {
                errBox.classList.remove('d-none');
                document.getElementById('errorCountBadge').textContent = data.errors.length;
                data.errors.forEach(err => {
                    const li = document.createElement('li');
                    li.className = 'mb-1';
                    li.innerHTML = `<strong>Row ${err.row} (${err.name}):</strong> ${err.message}`;
                    errList.appendChild(li);
                });
            } else {
                errBox.classList.add('d-none');
            }

            // Preview Table
            const tbody = document.getElementById('previewTableBody');
            tbody.innerHTML = '';
            (data.preview_records || []).forEach(r => {
                const tr = document.createElement('tr');
                const statusBadge = r.has_errors 
                    ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Invalid (${r.error_count} errs)</span>`
                    : `<span class="badge bg-success-subtle text-success border border-success-subtle">Valid</span>`;

                tr.innerHTML = `
                    <td class="text-muted fw-semibold">${r.row}</td>
                    <td class="fw-semibold text-dark">${r.name}</td>
                    <td>${r.gender}</td>
                    <td>${r.dob}</td>
                    <td>${r.phone}</td>
                    <td>${r.location}</td>
                    <td>${r.department}</td>
                    <td>${r.designation}</td>
                    <td>${statusBadge}</td>
                `;
                tbody.appendChild(tr);
            });

            // Confirm Button State
            const confirmBtn = document.getElementById('confirmImportBtn');
            if (data.can_proceed && data.valid_count > 0) {
                confirmBtn.removeAttribute('disabled');
                document.getElementById('confirmNote').textContent = `Ready to ingest ${data.valid_count} valid candidate records.`;
            } else {
                confirmBtn.setAttribute('disabled', 'true');
                document.getElementById('confirmNote').textContent = 'No valid records found in file. Please resolve errors and re-upload.';
            }

            // Switch to Step 3 & 4
            uploadStepCard.classList.add('d-none');
            validationPreviewCard.classList.remove('d-none');
            document.getElementById('stepIndicator-2').querySelector('.badge').className = 'badge rounded-circle bg-teal text-white p-2';
            document.getElementById('stepIndicator-3').querySelector('.badge').className = 'badge rounded-circle bg-teal text-white p-2';
        })
        .catch(err => {
            validateBtn.removeAttribute('disabled');
            validateBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Parse & Validate Records &rarr;';
            alert('Upload processing failed. Please check file format.');
        });
    });

    // Re-upload button
    document.getElementById('reUploadBtn').addEventListener('click', function () {
        validationPreviewCard.classList.add('d-none');
        uploadStepCard.classList.remove('d-none');
        fileInput.value = '';
        selectedFileInfo.classList.add('d-none');
        validateBtn.setAttribute('disabled', 'true');
    });

    // Confirm & Commit button
    document.getElementById('confirmImportBtn').addEventListener('click', function () {
        if (!currentBatchToken) return;

        this.setAttribute('disabled', 'true');
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Ingesting Records into Database...';

        fetch('{{ route("employees.bulk-import.confirm") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ batch_token: currentBatchToken })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                validationPreviewCard.classList.add('d-none');
                importResultsCard.classList.remove('d-none');
                document.getElementById('resultsSuccessTitle').textContent = `${data.imported_count} Employees Imported Successfully!`;
                document.getElementById('stepIndicator-4').querySelector('.badge').className = 'badge rounded-circle bg-teal text-white p-2';
                document.getElementById('stepIndicator-5').querySelector('.badge').className = 'badge rounded-circle bg-teal text-white p-2';
            } else {
                alert(data.message || 'Import commit failed.');
                this.removeAttribute('disabled');
                this.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Step 5: Confirm & Import Records';
            }
        })
        .catch(() => {
            alert('Server error occurred during import execution.');
            this.removeAttribute('disabled');
            this.innerHTML = '<i class="bi bi-box-arrow-in-down me-1"></i> Step 5: Confirm & Import Records';
        });
    });

    // Import another batch
    document.getElementById('importAnotherBtn').addEventListener('click', function () {
        importResultsCard.classList.add('d-none');
        uploadStepCard.classList.remove('d-none');
        fileInput.value = '';
        selectedFileInfo.classList.add('d-none');
        validateBtn.setAttribute('disabled', 'true');
        currentBatchToken = null;
    });
});
</script>
@endpush

