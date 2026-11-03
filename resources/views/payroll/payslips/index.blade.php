@extends('layouts.app')

@section('title', 'My Payslips & Salary Statements — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">{{ $isEss ? 'My Payslips' : 'All Employee Payslips' }}</h4>
            <p class="text-muted small mb-0">{{ $isEss ? 'Access and download your verified monthly salary statements' : 'Company-wide verified payslips archive' }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Payslip Number</th>
                        @if(!$isEss)
                            <th>Employee</th>
                        @endif
                        <th>Pay Period</th>
                        <th>Paid Days</th>
                        <th>Gross Earnings</th>
                        <th>Total Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $ps)
                        <tr>
                            <td class="ps-3 fw-bold text-teal-700">{{ $ps->payslip_number }}</td>
                            @if(!$isEss)
                                <td>
                                    <div class="fw-semibold">{{ $ps->employee?->full_name }}</div>
                                    <div class="text-muted small">{{ $ps->employee?->employee_code }} • {{ $ps->employee?->department?->name }}</div>
                                </td>
                            @endif
                            <td>{{ date('F Y', mktime(0, 0, 0, $ps->month, 1, $ps->year)) }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $ps->paid_days }} / {{ $ps->calendar_days }} Days</span>
                            </td>
                            <td class="fw-semibold">₹{{ number_format($ps->gross_earnings, 2) }}</td>
                            <td class="text-danger">₹{{ number_format($ps->total_deductions, 2) }}</td>
                            <td class="fw-bold text-success fs-6">₹{{ number_format($ps->net_salary, 2) }}</td>
                            <td>
                                <span class="badge {{ $ps->payment_status === 'Paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $ps->payment_status }}
                                </span>
                            </td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('payroll.payslips.show', $ps) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('payroll.payslips.download', $ps) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-download"></i> PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isEss ? 8 : 9 }}" class="text-center py-4 text-muted">No payslip records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payslips->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $payslips->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

