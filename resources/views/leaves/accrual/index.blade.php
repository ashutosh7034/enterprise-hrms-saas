@extends('layouts.app')

@section('title', 'Leave Accrual & Year-End Processing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Accrual & Carry-Forward Engine</h1>
        <p class="text-muted small mb-0">Execute monthly entitlement credits and annual carry-forward/expiry processing.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Monthly Accrual Card -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-slate-900"><i class="bi bi-calendar-check me-1"></i> Monthly Accrual Execution</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('leaves.accrual.run') }}" method="POST">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" value="{{ date('Y') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 10)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Leave Type</label>
                        <select name="leave_type_id" class="form-select">
                            <option value="">All Accruable Leave Types</option>
                            @foreach($leaveTypes as $lt)
                                @if($lt->is_accrual_enabled)
                                    <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }}) - {{ $lt->monthly_accrual_rate }} d/mo</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <p class="text-muted small">
                        Accruals are idempotent. If an employee has already been credited for the specified month and year, the system will not duplicate the transaction.
                    </p>
                    <button type="submit" class="btn btn-accent w-100">
                        <i class="bi bi-play-circle me-1"></i> Run Monthly Accrual
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Annual Carry-Forward Card -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-slate-900"><i class="bi bi-arrow-repeat me-1"></i> Year-End Carry-Forward & Expiry</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('leaves.accrual.carry-forward') }}" method="POST">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Source Year <span class="text-danger">*</span></label>
                            <input type="number" name="from_year" value="{{ (int)date('Y') - 1 }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Year <span class="text-danger">*</span></label>
                            <input type="number" name="to_year" value="{{ (int)date('Y') }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Leave Type</label>
                        <select name="leave_type_id" class="form-select">
                            <option value="">All Carry-Forward Eligible Types</option>
                            @foreach($leaveTypes as $lt)
                                @if($lt->is_carry_forward_enabled)
                                    <option value="{{ $lt->id }}">{{ $lt->name }} (Max {{ $lt->carry_forward_limit }} d)</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_dry_run" value="1" id="is_dry_run" checked>
                        <label class="form-check-label small fw-semibold" for="is_dry_run">
                            Dry-Run Mode (Simulation & preview without updating balances)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-magic me-1"></i> Process Year-End Transition
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Accrual History Batches -->
<div class="card card-custom mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-slate-900">Recent Accrual Batches</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Year & Month</th>
                    <th>Leave Type</th>
                    <th>Eligible Staff</th>
                    <th>Credited Staff</th>
                    <th>Total Days Credited</th>
                    <th>Status</th>
                    <th>Executed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accrualBatches as $ab)
                    <tr>
                        <td class="font-monospace fw-semibold">{{ $ab->batch_number }}</td>
                        <td>{{ date('F', mktime(0, 0, 0, $ab->month, 10)) }} {{ $ab->year }}</td>
                        <td>{{ $ab->leaveType?->name ?? 'All Accruable' }}</td>
                        <td>{{ $ab->total_eligible_employees }}</td>
                        <td>{{ $ab->total_credited_employees }}</td>
                        <td><span class="fw-bold text-teal">+{{ $ab->total_days_credited }} d</span></td>
                        <td><span class="status-pill status-pill-success"><span class="dot"></span>{{ $ab->status }}</span></td>
                        <td><span class="text-muted small">{{ $ab->executor?->name }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-3 text-muted">No accrual batches recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Carry Forward History Batches -->
<div class="card card-custom">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-slate-900">Recent Carry-Forward Batches</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Transition</th>
                    <th>Mode</th>
                    <th>Staff Processed</th>
                    <th>Carried Forward</th>
                    <th>Expired Days</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($carryForwardBatches as $cb)
                    <tr>
                        <td class="font-monospace fw-semibold">{{ $cb->batch_number }}</td>
                        <td>{{ $cb->from_year }} &rarr; {{ $cb->to_year }}</td>
                        <td>
                            @if($cb->is_dry_run)
                                <span class="badge bg-warning-subtle text-warning-emphasis">Dry-Run (Simulated)</span>
                            @else
                                <span class="badge bg-success-subtle text-success">Live Execution</span>
                            @endif
                        </td>
                        <td>{{ $cb->total_employees_processed }}</td>
                        <td><span class="fw-bold text-success">+{{ $cb->total_days_carried_forward }} d</span></td>
                        <td><span class="text-danger">-{{ $cb->total_days_expired }} d</span></td>
                        <td><span class="status-pill status-pill-info"><span class="dot"></span>{{ $cb->status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-3 text-muted">No carry-forward batches recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

