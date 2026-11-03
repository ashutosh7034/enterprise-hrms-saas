@extends('layouts.app')

@section('title', "F&F Statement {$fnf->settlement_number} — Enterprise HRMS")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-teal-100 text-teal-800 fw-bold fs-6">{{ $fnf->settlement_number }}</span>
                <span class="badge {{ $fnf->status === 'Settled' ? 'bg-success' : ($fnf->status === 'Finance Approved' ? 'bg-info text-dark' : 'bg-warning text-dark') }} fs-6">
                    {{ $fnf->status }}
                </span>
                <span class="badge {{ $fnf->settlement_type === 'Payable to Employee' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border fs-6">
                    {{ $fnf->settlement_type }}
                </span>
            </div>
            <p class="text-muted small mb-0">{{ $fnf->employee?->full_name }} ({{ $fnf->employee?->employee_code }}) • LWD: {{ $fnf->last_working_date->format('d M Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.fnf.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to F&F
            </a>
            <a href="{{ route('payroll.fnf.download', $fnf) }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>

            @if($fnf->status === 'Draft')
                <form action="{{ route('payroll.fnf.verify', $fnf) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-info btn-sm px-3 shadow-sm text-dark">
                        <i class="bi bi-check-circle me-1"></i> HR Verify
                    </button>
                </form>
            @endif

            @if($fnf->status === 'HR Verified')
                <form action="{{ route('payroll.fnf.approve', $fnf) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="bi bi-check2-all me-1"></i> Finance Approve (HO Admin)
                    </button>
                </form>
            @endif

            @if($fnf->status === 'Finance Approved')
                <form action="{{ route('payroll.fnf.settle', $fnf) }}" method="POST" onsubmit="return confirm('Complete exit settlement? This will finalize employee exit, close loans, and record settlement.');">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-award me-1"></i> Mark Settled & Close Exit
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Scorecards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Gross Payable Exit Dues</div>
                <div class="fw-bold fs-4 text-slate-800">₹{{ number_format($fnf->gross_settlement_amount, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-danger">
                <div class="text-muted small">Total Exit Deductions</div>
                <div class="fw-bold fs-4 text-danger">₹{{ number_format($fnf->total_deductions_amount, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 {{ $fnf->settlement_type === 'Payable to Employee' ? 'border-success' : 'border-danger' }}">
                <div class="text-muted small">Net Settlement Amount</div>
                <div class="fw-bold fs-4 {{ $fnf->settlement_type === 'Payable to Employee' ? 'text-success' : 'text-danger' }}">
                    ₹{{ number_format($fnf->net_settlement_amount, 2) }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Notice Period Status</div>
                <div class="fw-bold fs-5 mt-1">
                    @if($fnf->notice_shortfall_days > 0)
                        <span class="badge bg-danger">{{ $fnf->notice_shortfall_days }}d Shortfall</span>
                    @else
                        <span class="badge bg-success">Served in Full</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Exit Statement Paper Layout -->
    <div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 900px;">
        <div class="card-body p-5">
            <div class="border-bottom pb-3 mb-4 text-center">
                <h4 class="fw-bold text-slate-900 mb-1">{{ $fnf->company?->name }}</h4>
                <div class="badge bg-teal-50 text-teal-800 border border-teal-200 px-3 py-2 fs-6 mt-1">
                    FINAL EXIT SETTLEMENT & CLEARANCE STATEMENT
                </div>
            </div>

            <!-- Employee & Exit Dates Info -->
            <div class="row g-2 mb-4 bg-light p-3 rounded-3 small">
                <div class="col-md-6">
                    <div><strong>Employee:</strong> {{ $fnf->employee?->full_name }} ({{ $fnf->employee?->employee_code }})</div>
                    <div><strong>Department:</strong> {{ $fnf->employee?->department?->name ?? 'N/A' }}</div>
                    <div><strong>Designation:</strong> {{ $fnf->employee?->designation?->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                    <div><strong>Resignation Date:</strong> {{ $fnf->resignation_date->format('d M Y') }}</div>
                    <div><strong>Last Working Day:</strong> {{ $fnf->last_working_date->format('d M Y') }}</div>
                    <div><strong>Notice Required / Served:</strong> {{ $fnf->notice_period_required_days }}d / {{ $fnf->notice_period_served_days }}d</div>
                </div>
            </div>

            <!-- Two-Column Itemized Components Table -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="border rounded-3 overflow-hidden h-100">
                        <div class="bg-light p-2 fw-bold text-slate-800 border-bottom d-flex justify-content-between">
                            <span>Settlement Earnings</span>
                            <span>Amount (₹)</span>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-3">Unpaid Salary ({{ $fnf->unpaid_salary_days }} days)</td>
                                    <td class="pe-3 text-end fw-semibold">₹{{ number_format($fnf->unpaid_salary_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">Leave Encashment ({{ $fnf->leave_encashment_days }} PL days)</td>
                                    <td class="pe-3 text-end fw-semibold">₹{{ number_format($fnf->leave_encashment_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">Gratuity Settlement</td>
                                    <td class="pe-3 text-end fw-semibold">₹{{ number_format($fnf->gratuity_amount, 2) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="border-top bg-light">
                                <tr class="fw-bold">
                                    <td class="ps-3">Gross Exit Payable</td>
                                    <td class="pe-3 text-end fs-6 text-slate-900">₹{{ number_format($fnf->gross_settlement_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="border rounded-3 overflow-hidden h-100">
                        <div class="bg-light p-2 fw-bold text-slate-800 border-bottom d-flex justify-content-between">
                            <span>Exit Deductions & Offsets</span>
                            <span>Amount (₹)</span>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-3">Notice Period Shortfall ({{ $fnf->notice_shortfall_days }}d)</td>
                                    <td class="pe-3 text-end text-danger">₹{{ number_format($fnf->notice_shortfall_deduction, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">Outstanding Loan Principal</td>
                                    <td class="pe-3 text-end text-danger">₹{{ number_format($fnf->loan_outstanding_deduction, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">Asset Damage / Fine Clearance</td>
                                    <td class="pe-3 text-end text-danger">₹{{ number_format($fnf->asset_damage_fine_deduction, 2) }}</td>
                                </tr>
                                @if($fnf->other_deductions_amount > 0)
                                    <tr>
                                        <td class="ps-3">Other Exit Recoveries</td>
                                        <td class="pe-3 text-end text-danger">₹{{ number_format($fnf->other_deductions_amount, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="border-top bg-light">
                                <tr class="fw-bold">
                                    <td class="ps-3">Total Deductions</td>
                                    <td class="pe-3 text-end fs-6 text-danger">₹{{ number_format($fnf->total_deductions_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Net Settlement Banner -->
            <div class="p-3 {{ $fnf->settlement_type === 'Payable to Employee' ? 'bg-teal-50 border-teal-200' : 'bg-danger-subtle border-danger-subtle' }} border rounded-3 d-flex justify-content-between align-items-center mb-4">
                <div>
                    <div class="small fw-semibold text-uppercase {{ $fnf->settlement_type === 'Payable to Employee' ? 'text-teal-900' : 'text-danger' }}">
                        {{ $fnf->settlement_type }}
                    </div>
                    <div class="text-muted small">Status: {{ $fnf->status }}</div>
                </div>
                <div class="text-end">
                    <span class="fs-3 fw-bold {{ $fnf->settlement_type === 'Payable to Employee' ? 'text-teal-800' : 'text-danger' }}">
                        ₹{{ number_format($fnf->net_settlement_amount, 2) }}
                    </span>
                </div>
            </div>

            <!-- Signatory Footer -->
            <div class="row pt-5 text-center text-muted small border-top">
                <div class="col-4">
                    <div class="border-top pt-2">Employee Signature</div>
                </div>
                <div class="col-4">
                    <div class="border-top pt-2">HR Verified: {{ $fnf->verifier?->name ?? 'Pending' }}</div>
                </div>
                <div class="col-4">
                    <div class="border-top pt-2">Finance Approved: {{ $fnf->approver?->name ?? 'Pending' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

