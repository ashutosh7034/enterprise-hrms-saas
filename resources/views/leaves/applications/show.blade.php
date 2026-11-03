@extends('layouts.app')

@section('title', 'Leave Application ' . $application->application_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('leaves.applications.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Applications
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <div>
            <h1 class="h4 mb-1 text-slate-900 font-bold">
                Application: {{ $application->application_number }}
            </h1>
            <p class="text-muted small mb-0">Submitted on {{ $application->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($application->isPending() && (Auth::user()->isEmployee() || Auth::user()->isHr()))
                <form action="{{ route('leaves.applications.withdraw', $application->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to withdraw this application?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Withdraw Application
                    </button>
                </form>
            @endif

            @if($application->isApproved() && Auth::user()->isHr())
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Cancel Approved Leave
                </button>
            @endif

            @if($application->status === 'Pending Manager' && (Auth::user()->isManager() || Auth::user()->isHr()))
                <button type="button" class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#managerActionModal">
                    <i class="bi bi-check2-circle me-1"></i> Manager Action
                </button>
            @endif

            @if(($application->status === 'Pending HR' || $application->status === 'Pending Manager') && Auth::user()->isHr())
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#hrActionModal">
                    <i class="bi bi-shield-check me-1"></i> HR Final Sanction
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-custom mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-slate-900">Application Summary</h6>
                <div>
                    @if($application->status === 'Approved')
                        <span class="status-pill status-pill-success"><span class="dot"></span>Approved</span>
                    @elseif(str_starts_with($application->status, 'Pending'))
                        <span class="status-pill status-pill-warning"><span class="dot"></span>{{ $application->status }}</span>
                    @elseif($application->status === 'Rejected')
                        <span class="status-pill status-pill-danger"><span class="dot"></span>Rejected</span>
                    @else
                        <span class="status-pill status-pill-info"><span class="dot"></span>{{ $application->status }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="text-muted smaller">EMPLOYEE</div>
                        <div class="fw-bold text-slate-900">{{ $application->employee->first_name }} {{ $application->employee->last_name }}</div>
                        <div class="text-muted small">{{ $application->employee->employee_code }} &bull; {{ $application->employee->designation?->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted smaller">LEAVE TYPE</div>
                        <div class="fw-bold text-slate-900">{{ $application->leaveType->name }} ({{ $application->leaveType->code }})</div>
                        <div class="text-muted small">{{ $application->leaveType->is_paid ? 'Paid' : 'Unpaid' }} Leave</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted smaller">START DATE</div>
                        <div class="fw-semibold text-slate-800">{{ $application->start_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted smaller">END DATE</div>
                        <div class="fw-semibold text-slate-800">{{ $application->end_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted smaller">TOTAL PAYABLE DEDUCTION</div>
                        <div class="fw-bold text-teal fs-5">{{ $application->calculated_days }} days</div>
                    </div>
                    <div class="col-12 border-top pt-3">
                        <div class="text-muted smaller">REASON</div>
                        <p class="text-slate-800 mb-0 mt-1">{{ $application->reason }}</p>
                    </div>
                    @if($application->is_notice_period_override)
                        <div class="col-12">
                            <div class="alert alert-warning py-2 mb-0 small">
                                <i class="bi bi-shield-exclamation me-1"></i>
                                <strong>HR Notice Period Exemption:</strong> {{ $application->notice_period_justification }}
                            </div>
                        </div>
                    @endif
                </div>

                @if(!empty($application->sandwich_breakdown_json['breakdown']))
                    <h6 class="text-slate-900 small fw-bold border-bottom pb-2 mb-3">
                        <i class="bi bi-layers me-1"></i> Sandwich Policy Audit Breakdown
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 smaller">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Day Type</th>
                                    <th>Leave Deducted</th>
                                    <th>Policy Assessment Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($application->sandwich_breakdown_json['breakdown'] as $day)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $day['date'] }} ({{ $day['day_name'] }})</td>
                                        <td>
                                            <span class="badge {{ $day['day_type'] === 'Working Day' ? 'bg-light text-dark' : ($day['day_type'] === 'Holiday' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning-emphasis') }}">
                                                {{ $day['day_type'] }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($day['is_leave_deducted'])
                                                <span class="text-danger fw-bold">+{{ $day['deduction_units'] }} d</span>
                                            @else
                                                <span class="text-muted">0 d</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $day['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Multi-Stage Approval Steps Timeline -->
    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-slate-900">Approval Trail</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-unstyled mb-0">
                    @forelse($application->approvalSteps as $step)
                        <li class="mb-3 position-relative ps-4 border-start border-2 border-teal">
                            <span class="position-absolute top-0 start-0 translate-middle p-1 bg-teal border border-light rounded-circle"></span>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold small text-slate-900">{{ $step->stage }} &bull; {{ $step->action }}</span>
                                <span class="text-muted smaller">{{ $step->created_at->format('d M, h:i A') }}</span>
                            </div>
                            @if($step->approver)
                                <div class="text-muted smaller"><i class="bi bi-person"></i> {{ $step->approver->name }}</div>
                            @endif
                            @if($step->remarks)
                                <div class="bg-light p-2 rounded small mt-1 text-slate-700">{{ $step->remarks }}</div>
                            @endif
                        </li>
                    @empty
                        <li class="text-muted small">No approval steps logged.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Manager Action Modal -->
<div class="modal fade" id="managerActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('leaves.approvals.manager', $application->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-bold">Manager Review Decision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Decision <span class="text-danger">*</span></label>
                        <select name="decision" class="form-select" required>
                            <option value="Approve">Approve and Forward to HR</option>
                            <option value="Reject">Reject Application (Release Reserved Balance)</option>
                            <option value="Send Back">Send Back for Correction</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional comments for applicant..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm px-3">Confirm Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HR Action Modal -->
<div class="modal fade" id="hrActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('leaves.approvals.hr', $application->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-bold">HR Final Sanction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Decision <span class="text-danger">*</span></label>
                        <select name="decision" class="form-select" required>
                            <option value="Approve">Sanction & Deduct from Balance Ledger</option>
                            <option value="Reject">Reject & Release Balance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Approval or rejection notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">Execute Sanction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Approved Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('leaves.approvals.cancel', $application->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-bold text-danger">Cancel Approved Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning small">
                        Cancelling will restore <strong>{{ $application->approved_days ?? $application->calculated_days }} days</strong> back to the employee's available balance and record a cancellation reversal transaction in the ledger.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mandatory Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Provide reason for leave cancellation..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

