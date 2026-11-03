@extends('layouts.app')

@section('title', 'Leave Period Locking')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Leave Period Lock & Closure</h1>
        <p class="text-muted small mb-0">Close monthly leave periods for payroll reconciliation. Requires HO Admin authorization to unlock.</p>
    </div>
    @if(Auth::user()->isHr())
        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#lockModal">
            <i class="bi bi-lock-fill me-1"></i> Lock Leave Period
        </button>
    @endif
</div>

@if($errors->has('unlock_error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first('unlock_error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Year & Month</th>
                    <th>Locked Status</th>
                    <th>Locked By</th>
                    <th>Locked Timestamp</th>
                    <th>Closure Remarks</th>
                    <th>Unlock History</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locks as $lk)
                    <tr>
                        <td>
                            <div class="fw-bold text-slate-900 fs-6">{{ date('F', mktime(0, 0, 0, $lk->month, 10)) }} {{ $lk->year }}</div>
                            <span class="badge bg-light text-dark font-monospace">{{ $lk->year }}-{{ sprintf('%02d', $lk->month) }}</span>
                        </td>
                        <td>
                            @if($lk->is_locked)
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Locked</span>
                            @else
                                <span class="status-pill status-pill-success"><span class="dot"></span>Unlocked</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $lk->locker->name }}</span>
                        </td>
                        <td>{{ $lk->locked_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <span class="text-muted small">{{ $lk->remarks ?? 'Monthly payroll closure' }}</span>
                        </td>
                        <td>
                            @if($lk->unlockHistories->isNotEmpty())
                                <span class="badge bg-warning-subtle text-warning-emphasis">
                                    {{ $lk->unlockHistories->count() }} HO override(s)
                                </span>
                                <div class="text-muted smaller">Latest: {{ $lk->unlockHistories->first()->unlocked_at->format('d M Y') }}</div>
                            @else
                                <span class="text-muted small">None</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($lk->is_locked)
                                @if(Auth::user()->isHoAdmin())
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#unlockModal{{ $lk->id }}">
                                        <i class="bi bi-unlock-fill me-1"></i> HO Unlock
                                    </button>
                                @else
                                    <span class="text-muted small" title="HO Admin role required to unlock closed period"><i class="bi bi-shield-lock"></i> HO Lock</span>
                                @endif
                            @else
                                <form action="{{ route('leaves.period-locks.relock', $lk->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-lock"></i> Re-Lock Period
                                    </button>
                                </form>
                            @endif

                            @if(Auth::user()->isHoAdmin())
                                <!-- HO Unlock Modal -->
                                <div class="modal fade text-start" id="unlockModal{{ $lk->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('leaves.period-locks.unlock', $lk->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title font-bold text-warning-emphasis"><i class="bi bi-shield-exclamation me-1"></i> Head Office Period Unlock</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="alert alert-warning small">
                                                        Unlocking this closed period allows retroactive leave approvals, cancellations, and balance changes for <strong>{{ date('F', mktime(0, 0, 0, $lk->month, 10)) }} {{ $lk->year }}</strong>. This action is permanently audited.
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Mandatory Head Office Justification <span class="text-danger">*</span></label>
                                                        <textarea name="justification" class="form-control" rows="3" placeholder="Provide executive business justification for unlocking closed period..." required minlength="10"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-warning btn-sm px-3 font-semibold">Authorize Unlock</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-check fs-3 d-block mb-2"></i>
                            No leave periods currently locked.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Lock New Period Modal -->
<div class="modal fade" id="lockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('leaves.period-locks.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-bold">Lock Leave Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
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
                        <label class="form-label small fw-semibold">Closure Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Month-end payroll closure finalized..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm px-3">Lock Period</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

