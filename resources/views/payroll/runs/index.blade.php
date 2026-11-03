@extends('layouts.app')

@section('title', 'Payroll Control Tower — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header with Period Selection -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Payroll Control Tower</h4>
            <p class="text-muted small mb-0">{{ $company->name }} ({{ $company->code }}) — Centralized pre-validation, batch calculation & approval station</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="GET" action="{{ route('payroll.runs.index') }}" class="d-flex align-items-center gap-2">
                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = 2025; $y <= 2028; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route('payroll.reports.index') }}" class="btn btn-outline-info btn-sm px-3 shadow-sm">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Control Station Actions & Status Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-teal-50 text-teal-700 rounded-3 border border-teal-100 fs-3">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-slate-800">
                                Pay Cycle: {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) }}
                            </h5>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-light text-muted border">
                                    Period: {{ $period->start_date->format('d M') }} — {{ $period->end_date->format('d M Y') }}
                                </span>
                                <span class="badge bg-light text-muted border">
                                    Cutoff: {{ $period->cutoff_date->format('d M Y') }}
                                </span>
                                <span class="badge {{ $period->status === 'Locked' ? 'bg-danger' : ($period->status === 'Processing' ? 'bg-info' : 'bg-success') }}">
                                    Status: {{ $period->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 text-lg-end d-flex gap-2 justify-content-lg-end flex-wrap">
                    <form action="{{ route('payroll.runs.pre-validate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <button type="submit" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                            <i class="bi bi-shield-check me-1"></i> Pre-Validation Gate
                        </button>
                    </form>

                    @if(!$currentRun || in_array($currentRun->status, ['Draft', 'Pre-Validation Passed', 'Rejected']))
                        <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#executeModal">
                            <i class="bi bi-play-circle me-1"></i> Calculate Payroll
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Active Run Summary KPI Grid (if exists) -->
    @if($currentRun)
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-teal-500">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-teal-100 text-teal-800 fw-bold me-2">{{ $currentRun->run_batch_number }}</span>
                    <span class="fw-bold text-slate-800">Active Run Summary</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ in_array($currentRun->status, ['Approved', 'Locked', 'Disbursed']) ? 'bg-success' : ($currentRun->status === 'Pending Approval' ? 'bg-warning text-dark' : 'bg-info text-dark') }} fs-6">
                        {{ $currentRun->status }}
                    </span>
                    <a href="{{ route('payroll.runs.show', $currentRun) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i> View Ledger
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Processed Staff</div>
                            <div class="fw-bold fs-4 text-slate-800">{{ $currentRun->total_processed_employees }} / {{ $currentRun->total_eligible_employees }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Total Gross Earnings</div>
                            <div class="fw-bold fs-4 text-slate-800">₹{{ number_format($currentRun->total_gross_earnings, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Total Deductions</div>
                            <div class="fw-bold fs-4 text-danger">₹{{ number_format($currentRun->total_deductions, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Net Payout Amount</div>
                            <div class="fw-bold fs-4 text-success">₹{{ number_format($currentRun->total_net_payout, 2) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Approval Actions Bar -->
                <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                    @if($currentRun->status === 'Pre-Validation Passed')
                        <form action="{{ route('payroll.runs.submit', $currentRun) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm px-3 shadow-sm text-dark fw-semibold">
                                <i class="bi bi-send me-1"></i> Submit for Final Approval
                            </button>
                        </form>
                    @endif

                    @if($currentRun->status === 'Pending Approval')
                        <button class="btn btn-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="bi bi-check-lg me-1"></i> Approve Payroll (HO Admin)
                        </button>
                        <button class="btn btn-outline-danger btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-lg me-1"></i> Reject Run
                        </button>
                    @endif

                    @if($currentRun->status === 'Approved')
                        <form action="{{ route('payroll.runs.lock', $currentRun) }}" method="POST" onsubmit="return confirm('Lock this payroll run against all future modifications?');">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-sm px-3 shadow-sm">
                                <i class="bi bi-lock me-1"></i> Lock Payroll Period
                            </button>
                        </form>
                    @endif

                    @if(in_array($currentRun->status, ['Approved', 'Locked']))
                        <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#disburseModal">
                            <i class="bi bi-bank me-1"></i> Mark Disbursed (NEFT)
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Past Execution History -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-slate-800">Payroll Execution History</h6>
            <span class="badge bg-light text-muted border">Company Archive</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Batch Number</th>
                        <th>Period</th>
                        <th>Staff Count</th>
                        <th>Gross Payout</th>
                        <th>Total Deductions</th>
                        <th>Net Payable</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pastRuns as $pRun)
                        <tr>
                            <td class="ps-3 fw-bold text-teal-700">{{ $pRun->run_batch_number }}</td>
                            <td>{{ date('F Y', mktime(0, 0, 0, $pRun->month, 1, $pRun->year)) }}</td>
                            <td>{{ $pRun->total_processed_employees }}</td>
                            <td>₹{{ number_format($pRun->total_gross_earnings, 2) }}</td>
                            <td class="text-danger">₹{{ number_format($pRun->total_deductions, 2) }}</td>
                            <td class="fw-bold text-success">₹{{ number_format($pRun->total_net_payout, 2) }}</td>
                            <td>
                                <span class="badge {{ in_array($pRun->status, ['Approved', 'Locked', 'Disbursed']) ? 'bg-success' : ($pRun->status === 'Pending Approval' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $pRun->status }}
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('payroll.runs.show', $pRun) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No prior payroll runs recorded for this company.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Execute Payroll -->
<div class="modal fade" id="executeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('payroll.runs.execute') }}" method="POST">
            @csrf
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Calculate Payroll Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to run the calculation engine for <strong>{{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) }}</strong>?</p>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i> This will calculate pro-rata salaries, evaluate Phase 3 biometric overtime, apply statutory rules (PF, ESI, PT, LWF, TDS), and queue loan EMIs and asset damage fines.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">Execute Calculation</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Approve Payroll -->
@if($currentRun && $currentRun->status === 'Pending Approval')
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('payroll.runs.approve', $currentRun) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success">Approve Payroll Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are approving <strong>{{ $currentRun->run_batch_number }}</strong> with a total net payable of <strong>₹{{ number_format($currentRun->total_net_payout, 2) }}</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Approval Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Verified by HO Finance. Approved for banking transfer."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm">Confirm Approval</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reject Payroll -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('payroll.runs.reject', $currentRun) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Reject Payroll Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mandatory Rejection Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="State exact discrepancies or corrections required..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 shadow-sm">Confirm Rejection</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Modal: Disburse Payroll -->
@if($currentRun && in_array($currentRun->status, ['Approved', 'Locked']))
<div class="modal fade" id="disburseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('payroll.runs.disburse', $currentRun) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">Mark as Disbursed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Confirm bank transfer disbursement for <strong>₹{{ number_format($currentRun->total_net_payout, 2) }}</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bank UTR / Payment Reference</label>
                        <input type="text" name="payment_reference" class="form-control" required value="NEFT-HDFC-{{ date('Ymd') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">Confirm Disbursement</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

