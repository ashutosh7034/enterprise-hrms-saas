@extends('layouts.app')

@section('title', "Loan {$loan->loan_application_number} — Enterprise HRMS")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-teal-100 text-teal-800 fw-bold fs-6">{{ $loan->loan_application_number }}</span>
                <span class="badge {{ $loan->status === 'Active' ? 'bg-success' : ($loan->status === 'Closed' ? 'bg-secondary' : ($loan->status === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark')) }} fs-6">
                    {{ $loan->status }}
                </span>
            </div>
            <p class="text-muted small mb-0">{{ $loan->employee?->full_name }} ({{ $loan->employee?->employee_code }}) • Applied: {{ $loan->created_at->format('d M Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.loans.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Loans
            </a>
            @if(in_array($loan->status, ['Applied', 'Manager Approved']))
                <form action="{{ route('payroll.loans.approve', $loan) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm px-3 shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Approve Loan
                    </button>
                </form>
                <button class="btn btn-outline-danger btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-lg me-1"></i> Reject
                </button>
            @endif

            @if($loan->status === 'HR Approved')
                <form action="{{ route('payroll.loans.disburse', $loan) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-cash-stack me-1"></i> Disburse & Activate EMI
                    </button>
                </form>
            @endif

            @if($loan->status === 'Active')
                <form action="{{ route('payroll.loans.foreclose', $loan) }}" method="POST" onsubmit="return confirm('Foreclose and mark this loan as fully recovered?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark btn-sm px-3 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Early Foreclosure
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

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Sanctioned Principal</div>
                <div class="fw-bold fs-4 text-slate-800">₹{{ number_format($loan->principal_amount, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Monthly EMI Amount</div>
                <div class="fw-bold fs-4 text-teal-800">₹{{ number_format($loan->monthly_emi, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-success">
                <div class="text-muted small">Recovered in Payroll</div>
                <div class="fw-bold fs-4 text-success">₹{{ number_format($loan->total_recovered, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-danger">
                <div class="text-muted small">Outstanding Balance</div>
                <div class="fw-bold fs-4 text-danger">₹{{ number_format($loan->remaining_balance, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Loan Purpose & Approval Notes -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-slate-800 mb-2">Loan Particulars & Purpose</h6>
            <p class="text-muted mb-3">{{ $loan->reason ?? 'Standard employee salary advance application.' }}</p>
            <div class="row g-2 small text-muted">
                <div class="col-md-4">Loan Type: <span class="fw-semibold text-dark">{{ $loan->loan_type }}</span></div>
                <div class="col-md-4">Tenure: <span class="fw-semibold text-dark">{{ $loan->tenure_months }} Months</span></div>
                <div class="col-md-4">Disbursed Date: <span class="fw-semibold text-dark">{{ $loan->disbursed_date?->format('d M Y') ?? 'Pending Disbursement' }}</span></div>
            </div>
        </div>
    </div>

    <!-- EMI Amortization Schedule Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-slate-800">Monthly EMI Deduction Schedule ({{ $loan->emiSchedules->count() }} Installments)</h6>
            <span class="badge bg-light text-muted border">Automatic Payroll Linkage</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Installment #</th>
                        <th>Due Month / Year</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>Total EMI</th>
                        <th>Deduction Status</th>
                        <th class="pe-3">Processed Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->emiSchedules as $sch)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $sch->installment_number }} of {{ $loan->tenure_months }}</td>
                            <td>{{ date('F Y', mktime(0, 0, 0, $sch->due_month, 1, $sch->due_year)) }}</td>
                            <td>₹{{ number_format($sch->principal_component, 2) }}</td>
                            <td>₹{{ number_format($sch->interest_component, 2) }}</td>
                            <td class="fw-bold text-slate-900">₹{{ number_format($sch->emi_amount, 2) }}</td>
                            <td>
                                @if($sch->status === 'Deducted in Payroll')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Deducted</span>
                                @elseif($sch->status === 'Paid via FNF')
                                    <span class="badge bg-secondary">Paid in FNF</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Pending</span>
                                @endif
                            </td>
                            <td class="pe-3 text-muted small">{{ $sch->deducted_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No EMI schedule generated yet. Schedule will generate upon disbursement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Reject Loan -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('payroll.loans.reject', $loan) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Reject Loan Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mandatory Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="State reason for declining loan request..."></textarea>
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
@endsection

