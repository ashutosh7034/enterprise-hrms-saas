@extends('layouts.app')

@section('title', 'Payroll Reports & Statutory Compliance — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Payroll & Statutory Reports</h4>
            <p class="text-muted small mb-0">Statutory returns, bank payment advice, electronic challans & reconciliation registers</p>
        </div>
        <a href="{{ route('payroll.runs.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Control Tower
        </a>
    </div>

    @if($runs->isEmpty())
        <div class="alert alert-info shadow-sm">
            <i class="bi bi-info-circle me-1"></i> No processed payroll runs found. Please execute or approve a payroll batch from the Control Tower first.
        </div>
    @else
        <div class="row g-4">
            @foreach($runs as $run)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-slate-800">{{ date('F Y', mktime(0, 0, 0, $run->month, 1, $run->year)) }}</h6>
                                <span class="badge bg-teal-50 text-teal-800 border border-teal-200 small mt-1">{{ $run->run_batch_number }}</span>
                            </div>
                            <span class="badge {{ in_array($run->status, ['Approved', 'Locked', 'Disbursed']) ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $run->status }}
                            </span>
                        </div>
                        <div class="card-body pt-0">
                            <div class="text-muted small mb-3">
                                <div>Staff Count: <strong>{{ $run->total_processed_employees }}</strong></div>
                                <div>Net Payout: <strong class="text-success">₹{{ number_format($run->total_net_payout, 2) }}</strong></div>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="{{ route('payroll.reports.register', $run) }}" class="btn btn-sm btn-outline-primary text-start">
                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> Payroll Register
                                </a>
                                <a href="{{ route('payroll.reports.bank-advice', $run) }}" class="btn btn-sm btn-outline-success text-start">
                                    <i class="bi bi-bank me-2"></i> Bank Advice (NEFT CSV)
                                </a>
                                <a href="{{ route('payroll.reports.pf', $run) }}" class="btn btn-sm btn-outline-info text-start">
                                    <i class="bi bi-shield-check me-2"></i> PF Electronic Challan (ECR)
                                </a>
                                <a href="{{ route('payroll.reports.esi', $run) }}" class="btn btn-sm btn-outline-warning text-start text-dark">
                                    <i class="bi bi-heart-pulse me-2"></i> ESI Monthly Return
                                </a>
                                <a href="{{ route('payroll.reports.variance', $run) }}" class="btn btn-sm btn-outline-secondary text-start">
                                    <i class="bi bi-graph-up-arrow me-2"></i> MoM Variance Analysis
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

