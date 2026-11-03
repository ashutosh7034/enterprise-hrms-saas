@extends('layouts.app')

@section('title', "Bank Payment Advice — {$run->run_batch_number}")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Bank Payment Advice (NEFT / RTGS)</h4>
            <p class="text-muted small mb-0">{{ $run->company?->name }} • Batch: {{ $run->run_batch_number }} • Total Net: ₹{{ number_format($run->total_net_payout, 2) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.reports.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Reports
            </a>
            <a href="{{ route('payroll.reports.bank-advice', ['run' => $run->id, 'export' => 'csv']) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Export Bank CSV
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Employee ID</th>
                        <th>Employee Full Name</th>
                        <th class="text-end">Net Salary Payable (₹)</th>
                        <th>Payment Status</th>
                        <th class="pe-3">Reference / UTR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $rec['employee_code'] }}</td>
                            <td class="fw-semibold">{{ $rec['employee_name'] }}</td>
                            <td class="text-end fw-bold text-success fs-6">₹{{ number_format($rec['net_salary'], 2) }}</td>
                            <td>
                                <span class="badge {{ $rec['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $rec['payment_status'] }}
                                </span>
                            </td>
                            <td class="pe-3 text-muted small">{{ $rec['payment_reference'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No records available in this payment advice batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

