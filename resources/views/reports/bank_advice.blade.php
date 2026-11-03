@extends('layouts.app')

@section('title', 'Bank Transfer Advice (NEFT/RTGS)')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}" class="text-teal text-decoration-none">Reports Hub</a></li>
            <li class="breadcrumb-item text-muted">Disbursement & Banking</li>
            <li class="breadcrumb-item active fw-medium text-dark">Bank Transfer Advice</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Bank Transfer Advice (NEFT / RTGS)</h4>
            <p class="text-muted small mb-0">Salary Net Disbursement Statement for Banking Upload & Authorization</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-sm btn-brand dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i> Export Disbursement File
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('admin.reports.export', ['type' => 'BANK_TRANSFER_ADVICE', 'format' => 'csv', 'payroll_run_id' => $activeRunId]) }}">
                            <i class="bi bi-filetype-csv me-2 text-success"></i> Download NEFT CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('admin.reports.export', ['type' => 'BANK_TRANSFER_ADVICE', 'format' => 'pdf', 'payroll_run_id' => $activeRunId]) }}">
                            <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> Download Bank Authorization PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Security Alert: Sensitive Banking Data -->
<div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 12.5px;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
        <span>
            <strong>Banking Data Protection Active:</strong>
            @if($canViewUnmasked)
                <span class="text-success fw-semibold">Privileged Access Granted:</span> Full unmasked account numbers visible to Finance & HO Admin.
            @else
                <span class="text-warning-emphasis fw-semibold">Standard Role Masking:</span> Bank account numbers masked (<code>XXXXXX1234</code>) to prevent PII exposure.
            @endif
        </span>
    </div>
    <span class="badge bg-light text-dark border">RBAC Gated</span>
</div>

<!-- Payroll Batch Selector Bar -->
<div class="card card-custom mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.reports.bank-advice') }}" class="row g-3 align-items-center">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Select Payroll Disbursement Batch</label>
                <select name="payroll_run_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @forelse($payrollRuns as $run)
                        <option value="{{ $run->id }}" {{ $activeRunId == $run->id ? 'selected' : '' }}>
                            Batch #{{ $run->run_batch_number }} — {{ \Carbon\Carbon::create($run->year, $run->month, 1)->format('F Y') }} ({{ $run->status }})
                        </option>
                    @empty
                        <option value="">No completed payroll runs found</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-7 text-md-end text-muted small pt-md-3">
                @if(!empty($reportData['summary']))
                    <span>Payees: <strong class="text-dark">{{ $reportData['summary']['Payees Count'] ?? 0 }}</strong></span> &nbsp;•&nbsp;
                    <span>Net Transfer: <strong class="text-success">{{ $reportData['summary']['Total Net Payout'] ?? 'INR 0.00' }}</strong></span>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Advice Dataset Table -->
<div class="card card-custom">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-dark"><i class="bi bi-bank me-2 text-teal"></i>Bank Transfer Statement</span>
        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 11px;">Payment Format: NEFT / RTGS</span>
    </div>
    <div class="card-body p-0">
        @if(empty($reportData['rows']))
            <div class="p-5 text-center text-muted">
                <i class="bi bi-wallet2 fs-1 text-secondary d-block mb-2"></i>
                <h6 class="fw-semibold">No Disbursement Records in Selected Batch</h6>
                <p class="small text-muted mb-0">Select an approved or disbursed payroll run to inspect payment advice.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            @foreach($reportData['headers'] as $h)
                                <th>{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['rows'] as $i => $row)
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>
                                <td><code>{{ $row['Employee Code'] }}</code></td>
                                <td class="fw-semibold text-dark">{{ $row['Employee Name'] }}</td>
                                <td>{{ $row['Department'] }}</td>
                                <td>{{ $row['Bank Name'] }}</td>
                                <td class="code-font fw-medium {{ $canViewUnmasked ? 'text-dark' : 'text-muted' }}">{{ $row['Account Number'] }}</td>
                                <td class="code-font">{{ $row['IFSC Code'] }}</td>
                                <td class="fw-bold text-success">{{ $row['Net Amount (INR)'] }}</td>
                                <td><code>{{ $row['Payment Reference'] }}</code></td>
                                <td>
                                    <span class="badge {{ $row['Disbursement Status'] === 'Paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                        {{ $row['Disbursement Status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

