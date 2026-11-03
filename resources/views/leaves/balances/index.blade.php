@extends('layouts.app')

@section('title', 'Leave Balances Administration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Balances & Adjustments</h1>
        <p class="text-muted small mb-0">Monitor employee leave entitlements, available quotas, and record auditable balance adjustments.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('leaves.balances.ledger') }}" class="btn btn-outline-secondary">
            <i class="bi bi-journal-text me-1"></i> Balance Ledger
        </a>
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#adjustmentModal">
            <i class="bi bi-sliders me-1"></i> Manual Adjustment
        </button>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('leaves.balances.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = (int)date('Y') - 1; $y <= (int)date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <select name="leave_type_id" class="form-select form-select-sm">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }} ({{ $lt->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search by employee name or code...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Opening</th>
                    <th>Accrued</th>
                    <th>Carried Fwd</th>
                    <th>Adjustments</th>
                    <th>Consumed</th>
                    <th>Reserved</th>
                    <th>Available Quota</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $bal)
                    <tr>
                        <td>
                            <div class="fw-semibold text-slate-800">{{ $bal->employee->first_name }} {{ $bal->employee->last_name }}</div>
                            <span class="badge bg-light text-dark font-monospace">{{ $bal->employee->employee_code }}</span>
                            <span class="text-muted small ms-1">{{ $bal->employee->department?->name }}</span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $bal->leaveType->code }}</span>
                            <div class="text-muted small">{{ $bal->leaveType->name }}</div>
                        </td>
                        <td>{{ $bal->opening_balance }} d</td>
                        <td>{{ $bal->accrued_balance }} d</td>
                        <td>{{ $bal->carry_forward_balance }} d</td>
                        <td>
                            <span class="text-success">+{{ $bal->adjustment_credit }}</span> / 
                            <span class="text-danger">-{{ $bal->adjustment_debit }}</span>
                        </td>
                        <td class="text-danger fw-semibold">{{ $bal->consumed_balance }} d</td>
                        <td>
                            @if((float)$bal->reserved_balance > 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis">{{ $bal->reserved_balance }} d</span>
                            @else
                                <span class="text-muted">0 d</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success fs-6 px-2 py-1">
                                {{ $bal->available_balance }} d
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-folder-x fs-3 d-block mb-2"></i>
                            No leave balances found for year {{ $year }}. Run monthly accrual or initialize opening balances.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($balances->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $balances->links() }}
        </div>
    @endif
</div>

<!-- Manual Adjustment Modal -->
<div class="modal fade" id="adjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('leaves.balances.adjust') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-bold">Record Manual Balance Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->employee_code }} - {{ $emp->first_name }} {{ $emp->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type_id" class="form-select" required>
                                @foreach($leaveTypes as $lt)
                                    <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" value="{{ $year }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                            <select name="adjustment_type" class="form-select" required>
                                <option value="Credit">Credit (+ Days)</option>
                                <option value="Debit">Debit (- Days)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Days <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" min="0.5" name="days" value="1.0" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mandatory Reason / Justification <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Reason for adjustment (e.g. Compensatory waiver, joining adjustment)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm px-3">Record in Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

