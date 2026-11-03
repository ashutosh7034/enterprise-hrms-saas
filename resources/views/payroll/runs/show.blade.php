@extends('layouts.app')

@section('title', "Payroll Run {$run->run_batch_number} — Enterprise HRMS")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-teal-100 text-teal-800 fw-bold fs-6">{{ $run->run_batch_number }}</span>
                <span class="badge {{ in_array($run->status, ['Approved', 'Locked', 'Disbursed']) ? 'bg-success' : ($run->status === 'Pending Approval' ? 'bg-warning text-dark' : 'bg-info text-dark') }} fs-6">
                    {{ $run->status }}
                </span>
            </div>
            <p class="text-muted small mb-0">Period: {{ date('F Y', mktime(0, 0, 0, $run->month, 1, $run->year)) }} — Processed by {{ $run->processor?->name ?? 'Admin' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.runs.index', ['year' => $run->year, 'month' => $run->month]) }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Control Tower
            </a>
            <a href="{{ route('payroll.reports.register', $run) }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Full Register
            </a>
            <a href="{{ route('payroll.reports.bank-advice', $run) }}" class="btn btn-outline-success btn-sm px-3 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Bank Advice (CSV)
            </a>
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
                <div class="text-muted small">Processed Employees</div>
                <div class="fw-bold fs-4 text-slate-800">{{ $run->total_processed_employees }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Total Gross Earnings</div>
                <div class="fw-bold fs-4 text-slate-800">₹{{ number_format($run->total_gross_earnings, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-danger">
                <div class="text-muted small">Total Deductions</div>
                <div class="fw-bold fs-4 text-danger">₹{{ number_format($run->total_deductions, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-success">
                <div class="text-muted small">Total Net Payable</div>
                <div class="fw-bold fs-4 text-success">₹{{ number_format($run->total_net_payout, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Employee Payslip Ledger -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-slate-800">Employee Payroll Ledger ({{ $employees->total() }})</h6>
            <span class="badge bg-light text-muted border">Itemized Calculations</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Days (Paid / LOP)</th>
                        <th>OT (Hrs / ₹)</th>
                        <th>Gross Pay</th>
                        <th>PF (EE)</th>
                        <th>ESI (EE)</th>
                        <th>PT</th>
                        <th>TDS</th>
                        <th>Loans / Fines</th>
                        <th>Total Ded.</th>
                        <th>Net Salary</th>
                        <th class="pe-3 text-end">Payslip</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold">{{ $emp->employee?->full_name }}</div>
                                <div class="text-muted small">{{ $emp->employee?->employee_code }} • {{ $emp->employee?->department?->name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success">{{ $emp->paid_days }}d</span>
                                @if($emp->loss_of_pay_days > 0)
                                    <span class="badge bg-danger-subtle text-danger">{{ $emp->loss_of_pay_days }}d LOP</span>
                                @endif
                            </td>
                            <td>
                                @if($emp->overtime_amount > 0)
                                    <span class="badge bg-warning text-dark">{{ $emp->overtime_hours }}h (₹{{ number_format($emp->overtime_amount, 2) }})</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="fw-semibold">₹{{ number_format($emp->gross_earnings, 2) }}</td>
                            <td class="text-muted">₹{{ number_format($emp->pf_employee, 2) }}</td>
                            <td class="text-muted">₹{{ number_format($emp->esi_employee, 2) }}</td>
                            <td class="text-muted">₹{{ number_format($emp->professional_tax, 2) }}</td>
                            <td class="text-muted">₹{{ number_format($emp->income_tax_tds, 2) }}</td>
                            <td>
                                @if($emp->loan_emi_deduction > 0 || $emp->fine_recovery_deduction > 0)
                                    <span class="badge bg-danger-subtle text-danger">₹{{ number_format($emp->loan_emi_deduction + $emp->fine_recovery_deduction, 2) }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="fw-semibold text-danger">₹{{ number_format($emp->total_deductions, 2) }}</td>
                            <td class="fw-bold text-success fs-6">₹{{ number_format($emp->net_salary, 2) }}</td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('payroll.payslips.show', $emp) }}" class="btn btn-sm btn-outline-primary me-1" title="View Payslip">
                                    <i class="bi bi-file-text"></i>
                                </a>
                                <a href="{{ route('payroll.payslips.download', $emp) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF">
                                    <i class="bi bi-download"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No employees recorded in this payroll batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    <!-- Approval Audit History -->
    @if($run->approvalSteps->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-slate-800">Approval Workflow Audit Trail</h6>
            </div>
            <div class="card-body pt-0">
                <div class="timeline">
                    @foreach($run->approvalSteps as $step)
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="badge bg-teal-100 text-teal-800 p-2 rounded-circle">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-slate-800">{{ $step->stage }}: {{ $step->action }} by {{ $step->actor?->name }}</div>
                                <div class="text-muted small">{{ $step->action_at->format('d M Y H:i') }} • Remarks: {{ $step->remarks ?? 'None' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

