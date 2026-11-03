@extends('layouts.app')

@section('title', 'Leave Balance Audit Ledger')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Balance Audit Ledger</h1>
        <p class="text-muted small mb-0">Immutable transaction log tracking all credits, debits, accruals, and cancellations.</p>
    </div>
    <a href="{{ route('leaves.balances.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Balances
    </a>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('leaves.balances.ledger') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->employee_code }} - {{ $emp->first_name }} {{ $emp->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Transaction Types</option>
                    @foreach(['Opening Balance', 'Monthly Accrual', 'Confirmation Allocation', 'Leave Application', 'Leave Cancellation', 'Carry Forward', 'Expiry', 'Comp-Off Credit', 'Manual Adjustment'] as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
            @if(request()->anyFilled(['employee_id', 'type']))
                <div class="col-md-2">
                    <a href="{{ route('leaves.balances.ledger') }}" class="btn btn-sm btn-link text-muted">Clear</a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Date & Ref</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Transaction Type</th>
                    <th>Credit</th>
                    <th>Debit</th>
                    <th>Balance Before</th>
                    <th>Balance After</th>
                    <th>Actor & Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr>
                        <td>
                            <div class="fw-semibold text-slate-800">{{ $tx->transaction_date->format('d M Y') }}</div>
                            <span class="text-muted small font-monospace">{{ Str::limit($tx->transaction_uuid, 16) }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $tx->employee->first_name }} {{ $tx->employee->last_name }}</div>
                            <span class="badge bg-light text-dark font-monospace">{{ $tx->employee->employee_code }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark font-monospace">{{ $tx->leaveType->code }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-dark">{{ $tx->transaction_type }}</span>
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
                        <td>{{ $tx->balance_before }} d</td>
                        <td>
                            <span class="fw-bold text-slate-900">{{ $tx->balance_after }} d</span>
                        </td>
                        <td>
                            <div class="text-muted small">{{ $tx->remarks }}</div>
                            @if($tx->creator)
                                <div class="text-muted smaller"><i class="bi bi-person"></i> {{ $tx->creator->name }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                            No transactions matching the filter criteria.
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

