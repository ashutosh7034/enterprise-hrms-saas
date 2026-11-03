@extends('layouts.app')

@section('title', 'Full & Final (F&F) Exit Settlements — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Full & Final (F&F) Settlements</h4>
            <p class="text-muted small mb-0">Employee exit clearance, notice shortfall recovery, leave encashment, loan balance offsets & gratuity calculation</p>
        </div>
        <a href="{{ route('payroll.fnf.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-person-x me-1"></i> Prepare F&F Settlement
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Settlement #</th>
                        <th>Employee</th>
                        <th>Resignation / LWD</th>
                        <th>Gross Settlement</th>
                        <th>Total Deductions</th>
                        <th>Net Amount</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlements as $fnf)
                        <tr>
                            <td class="ps-3 fw-bold text-teal-700">{{ $fnf->settlement_number }}</td>
                            <td>
                                <div class="fw-semibold">{{ $fnf->employee?->full_name }}</div>
                                <div class="text-muted small">{{ $fnf->employee?->employee_code }} • {{ $fnf->employee?->department?->name }}</div>
                            </td>
                            <td>
                                <div>{{ $fnf->resignation_date->format('d M Y') }}</div>
                                <div class="text-muted small">LWD: {{ $fnf->last_working_date->format('d M Y') }}</div>
                            </td>
                            <td class="fw-semibold">₹{{ number_format($fnf->gross_settlement_amount, 2) }}</td>
                            <td class="text-danger">₹{{ number_format($fnf->total_deductions_amount, 2) }}</td>
                            <td class="fw-bold fs-6 {{ $fnf->settlement_type === 'Payable to Employee' ? 'text-success' : 'text-danger' }}">
                                ₹{{ number_format($fnf->net_settlement_amount, 2) }}
                            </td>
                            <td>
                                <span class="badge {{ $fnf->settlement_type === 'Payable to Employee' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} border">
                                    {{ $fnf->settlement_type }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $fnf->status === 'Settled' ? 'bg-success' : ($fnf->status === 'Finance Approved' ? 'bg-info text-dark' : 'bg-warning text-dark') }}">
                                    {{ $fnf->status }}
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('payroll.fnf.show', $fnf) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> View Statement
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No exit settlement statements recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($settlements->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

