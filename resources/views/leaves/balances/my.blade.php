@extends('layouts.app')

@section('title', 'My Leave Balances')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">My Leave Entitlements & Balances</h1>
        <p class="text-muted small mb-0">{{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code }}) &bull; {{ $employee->designation?->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('leaves.applications.create') }}" class="btn btn-accent">
            <i class="bi bi-calendar-plus me-1"></i> Apply for Leave
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    @forelse($balances as $bal)
        <div class="col-md-3">
            <div class="card card-custom h-100 border-top border-4 border-teal">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-light text-dark font-monospace">{{ $bal->leaveType->code }}</span>
                        @if($bal->leaveType->is_paid)
                            <span class="badge bg-success-subtle text-success small">Paid</span>
                        @endif
                    </div>
                    <h6 class="fw-bold text-slate-800 mb-1">{{ $bal->leaveType->name }}</h6>
                    <div class="display-6 fw-bold text-teal my-2">{{ $bal->available_balance }} <span class="fs-6 text-muted fw-normal">days</span></div>
                    
                    <div class="border-top pt-2 mt-2 small text-muted">
                        <div class="d-flex justify-content-between">
                            <span>Accrued:</span>
                            <span class="fw-semibold text-slate-700">{{ $bal->accrued_balance }} d</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Used:</span>
                            <span class="fw-semibold text-danger">{{ $bal->consumed_balance }} d</span>
                        </div>
                        @if((float)$bal->reserved_balance > 0)
                            <div class="d-flex justify-content-between">
                                <span>In Approval:</span>
                                <span class="fw-semibold text-warning-emphasis">{{ $bal->reserved_balance }} d</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">
                No leave balances currently allocated for year {{ $year }}. Please contact HR administration if you believe this is in error.
            </div>
        </div>
    @endforelse
</div>

<div class="card card-custom">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-slate-900">My Leave Activity & Ledger Transactions</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Leave Type</th>
                    <th>Transaction Type</th>
                    <th>Credit</th>
                    <th>Debit</th>
                    <th>Balance After</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr>
                        <td class="text-nowrap">{{ $tx->transaction_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-light text-dark font-monospace">{{ $tx->leaveType->code }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $tx->transaction_type }}</span>
                        </td>
                        <td>
                            @if((float)$tx->credit_days > 0)
                                <span class="text-success fw-bold">+{{ $tx->credit_days }} d</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if((float)$tx->debit_days > 0)
                                <span class="text-danger fw-bold">-{{ $tx->debit_days }} d</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-slate-800">{{ $tx->balance_after }} d</span>
                        </td>
                        <td>
                            <span class="text-muted small">{{ $tx->remarks }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No ledger transactions recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection

