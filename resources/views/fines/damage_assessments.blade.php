@extends('layouts.app')

@section('title', 'Damage Assessments & Recovery Ledger')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Asset Damage & Fine Recovery Ledger</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Manage asset damage incident investigations, depreciation-based liabilities, and recovery schedules.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('fine-masters.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-calculator me-1"></i> Fine Master Policies
        </a>
        <a href="{{ route('damage-assessments.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Log Damage Assessment
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Pending HO Authorization</div>
                    <div style="font-size: 24px; font-weight: 700; color: #B45309;">{{ $pendingApprovals }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-hourglass-split fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Queued for Phase 5 Payroll</div>
                    <div style="font-size: 24px; font-weight: 700; color: #0D9488;">{{ $queuedForPayroll }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #CCFBF1; color: #0F766E; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-wallet2 fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Total Fines Recovered</div>
                    <div style="font-size: 24px; font-weight: 700; color: #16A34A;">₹{{ number_format($totalRecovered, 2) }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #DCFCE7; color: #15803D; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-cash-stack fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="damageTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessmentsTab" type="button" role="tab">
            <i class="bi bi-file-earmark-break me-1"></i> Damage Incident Assessments ({{ $assessments->total() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledgerTab" type="button" role="tab">
            <i class="bi bi-journal-text me-1"></i> Fine Recovery Ledger & Payroll Queue ({{ $recoveries->total() }})
        </button>
    </li>
</ul>

<div class="tab-content" id="damageTabsContent">
    <!-- Tab 1: Damage Assessments -->
    <div class="tab-pane fade show active" id="assessmentsTab" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span style="font-weight: 600;">Incident Assessments</span>
                <span class="badge bg-light text-dark border code-font">Company: {{ $activeCompany->code }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Asset Details</th>
                            <th>Employee Liable</th>
                            <th>Assessed Damage</th>
                            <th>Depreciated Book Val</th>
                            <th>Net Recovery</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assessments as $ass)
                            <tr>
                                <td class="code-font fw-bold">#{{ $ass->id }}</td>
                                <td>
                                    <div class="code-font fw-bold">{{ $ass->allocation->asset->asset_code }}</div>
                                    <div class="text-muted" style="font-size: 11.5px;">{{ $ass->allocation->asset->name }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('employees.show', $ass->allocation->employee_id) }}" class="fw-bold text-decoration-none">
                                        {{ $ass->allocation->employee->full_name }}
                                    </a>
                                    <div class="code-font text-muted" style="font-size: 11px;">{{ $ass->allocation->employee->employee_code }}</div>
                                </td>
                                <td class="code-font">₹{{ number_format($ass->assessed_amount, 2) }}</td>
                                <td>
                                    <div class="code-font text-muted">₹{{ number_format($ass->depreciated_value, 2) }}</div>
                                    <div class="badge bg-light text-secondary border" style="font-size: 10px;">{{ $ass->depreciation_strategy }}</div>
                                </td>
                                <td>
                                    <div class="code-font fw-bold text-danger">₹{{ number_format($ass->net_recovery_amount, 2) }}</div>
                                    @if($ass->waived_amount > 0)
                                        <div class="text-muted" style="font-size: 11px;">(₹{{ number_format($ass->waived_amount, 2) }} waived)</div>
                                    @endif
                                </td>
                                <td>
                                    @if($ass->status === 'Approved')
                                        <span class="status-pill status-pill-success"><span class="dot"></span> Approved</span>
                                    @elseif($ass->status === 'Pending Approval')
                                        <span class="status-pill status-pill-warning"><span class="dot"></span> Pending Approval</span>
                                    @elseif($ass->status === 'Waived')
                                        <span class="status-pill status-pill-info"><span class="dot"></span> Waived</span>
                                    @else
                                        <span class="status-pill status-pill-danger"><span class="dot"></span> {{ $ass->status }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($ass->status === 'Pending Approval')
                                            <form method="POST" action="{{ route('damage-assessments.approve', $ass->id) }}" class="d-inline" onsubmit="return confirm('Authorize this damage assessment and generate recovery installments?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Approve Assessment">
                                                    <i class="bi bi-check-lg me-1"></i> Approve
                                                </button>
                                            </form>
                                        @endif

                                        @if(auth()->user()->isHoAdmin() && $ass->status !== 'Waived')
                                            <button type="button" class="btn btn-outline-warning" title="Apply Waiver" onclick="openWaiverModal({{ $ass->id }}, {{ $ass->assessed_amount }})">
                                                <i class="bi bi-tag me-1"></i> Waiver
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check display-6 d-block mb-2 text-success"></i>
                                    <div style="font-size: 15px; font-weight: 500;">No damage assessments recorded</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assessments->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $assessments->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Tab 2: Recovery Ledger -->
    <div class="tab-pane fade" id="ledgerTab" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span style="font-weight: 600;">Fine Recovery Ledger & Deductions</span>
                <span class="badge bg-light text-dark border code-font">Phase 5 Payroll Integration Interface</span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Installment</th>
                            <th>Employee</th>
                            <th>Assessment Ref</th>
                            <th>Recovery Amount</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Receipt / Remarks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recoveries as $rec)
                            <tr>
                                <td class="code-font fw-bold">Inst #{{ $rec->installment_number }}</td>
                                <td>
                                    <a href="{{ route('employees.show', $rec->employee_id) }}" class="fw-bold text-decoration-none">
                                        {{ $rec->employee->full_name }}
                                    </a>
                                    <div class="code-font text-muted" style="font-size: 11px;">{{ $rec->employee->employee_code }}</div>
                                </td>
                                <td>
                                    <div class="code-font text-muted">Assessment #{{ $rec->damage_assessment_id }}</div>
                                    <div style="font-size: 11.5px;">{{ $rec->assessment->allocation->asset->asset_code }}</div>
                                </td>
                                <td class="code-font fw-bold">₹{{ number_format($rec->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $rec->payment_channel }}</span>
                                </td>
                                <td>
                                    @if($rec->status === 'Paid')
                                        <span class="status-pill status-pill-success"><span class="dot"></span> Paid ({{ $rec->paid_date ? $rec->paid_date->format('d M') : '' }})</span>
                                    @elseif($rec->status === 'Queued for Phase 5 Payroll')
                                        <span class="status-pill status-pill-info"><span class="dot"></span> Queued for Payroll</span>
                                    @elseif($rec->status === 'Waived')
                                        <span class="status-pill status-pill-secondary"><span class="dot"></span> Waived</span>
                                    @else
                                        <span class="status-pill status-pill-warning"><span class="dot"></span> Pending Payment</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rec->receipt_reference)
                                        <div class="code-font" style="font-size: 11.5px;">Ref: {{ $rec->receipt_reference }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 11.5px;">{{ $rec->remarks ?? '—' }}</div>
                                </td>
                                <td class="text-end">
                                    @if($rec->status !== 'Paid' && $rec->status !== 'Waived')
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="openPaymentModal({{ $rec->id }}, {{ $rec->amount }}, {{ $rec->installment_number }})">
                                            <i class="bi bi-cash me-1"></i> Record Payment
                                        </button>
                                    @else
                                        <span class="text-muted" style="font-size: 12px;">Settled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-wallet display-6 d-block mb-2 text-secondary"></i>
                                    <div style="font-size: 15px; font-weight: 500;">No fine recovery records currently pending</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recoveries->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $recoveries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Apply Waiver -->
<div class="modal fade" id="waiverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="waiverForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-warning"><i class="bi bi-tag me-1"></i> Authorize Fine Waiver</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size: 13px; color: #475569;">
                        Assessed Amount: <strong>₹<span id="waiverAssessedSpan"></span></strong>. Only HO Admin can authorize fine waivers.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Waiver Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="waived_amount" id="waiverAmountInput" class="form-control code-font" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Waiver Rationale / Justification <span class="text-danger">*</span></label>
                        <textarea name="waiver_reason" rows="3" class="form-control" placeholder="Provide reason for waiver (e.g. accidental factory damage without negligence, management pardon)..." required minlength="10"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm">Confirm Waiver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="paymentForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold text-success"><i class="bi bi-cash me-1"></i> Record Fine Recovery Payment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Payment Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="paymentAmountInput" class="form-control code-font" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Payment Channel <span class="text-danger">*</span></label>
                        <select name="payment_channel" class="form-select form-select-sm" required>
                            <option value="Direct Payment">Direct Cash / Online Transfer Payment</option>
                            <option value="Payroll Deduction Queue">Payroll Deduction Queue</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Receipt / Transaction Reference</label>
                        <input type="text" name="receipt_reference" class="form-control form-control-sm code-font" placeholder="e.g. REC-2026-9923 or UPI Ref">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Settlement notes...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirm Payment Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openWaiverModal(assessmentId, assessedAmount) {
        const form = document.getElementById('waiverForm');
        form.action = '/damage-assessments/' + assessmentId + '/waiver';
        document.getElementById('waiverAssessedSpan').innerText = assessedAmount.toFixed(2);
        document.getElementById('waiverAmountInput').value = assessedAmount.toFixed(2);
        const modal = new bootstrap.Modal(document.getElementById('waiverModal'));
        modal.show();
    }

    function openPaymentModal(recordId, amount, instNumber) {
        const form = document.getElementById('paymentForm');
        form.action = '/fine-recovery/' + recordId + '/payment';
        document.getElementById('paymentAmountInput').value = amount.toFixed(2);
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }
</script>
@endsection

