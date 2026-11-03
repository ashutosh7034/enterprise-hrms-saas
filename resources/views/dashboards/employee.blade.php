@extends('layouts.app')

@section('title', 'Employee Self-Service (ESS)')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Employee Self-Service (ESS)</h4>
        <p class="text-muted small mb-0">Welcome back, <strong>{{ $user->name }}</strong> ({{ $emp?->employee_code ?? 'Staff' }}) • {{ $activeCompany?->name }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('leaves.applications.create') }}" class="btn btn-sm btn-brand">
            <i class="bi bi-calendar-plus me-1"></i> Apply Leave
        </a>
        <a href="{{ route('documents.my-documents') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i> My Documents
        </a>
    </div>
</div>

<!-- Personal Quick Metrics Strip -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Available Leaves</div>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($metrics['available_leaves'], 1) }}</h3>
                    <div class="text-muted small">{{ number_format($metrics['leaves_taken'], 1) }} consumed this year</div>
                </div>
                <div class="p-2 rounded bg-success-subtle text-success">
                    <i class="bi bi-calendar-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Attendance Rate (MTD)</div>
                    <h3 class="fw-bold mb-0 text-primary">{{ $metrics['attendance_percent'] }}%</h3>
                    <div class="text-muted small">Month-to-date present</div>
                </div>
                <div class="p-2 rounded bg-primary-subtle text-primary">
                    <i class="bi bi-pie-chart fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Pending Leave Requests</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ $metrics['pending_leaves'] }}</h3>
                    <div class="text-muted small">Awaiting approval</div>
                </div>
                <div class="p-2 rounded bg-warning-subtle text-warning">
                    <i class="bi bi-hourglass-split fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-custom p-3 h-100 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium mb-1">Issued Documents</div>
                    <h3 class="fw-bold mb-0 text-info">{{ $metrics['my_documents_count'] }}</h3>
                    <div class="text-muted small">Official letters & forms</div>
                </div>
                <div class="p-2 rounded bg-info-subtle text-info">
                    <i class="bi bi-file-earmark-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Today's Attendance Punch Card -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-fingerprint me-2 text-teal"></i>Today's Attendance Status</span>
                <span class="badge {{ $todayRecord ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                    {{ $todayRecord ? ($todayRecord->status ?? 'Marked') : 'Not Recorded' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center my-2">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted small mb-1">Clock In Time</div>
                            <h4 class="fw-bold mb-0 text-dark">
                                {{ $todayRecord && $todayRecord->in_time ? \Carbon\Carbon::parse($todayRecord->in_time)->format('h:i A') : '--:--' }}
                            </h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded border">
                            <div class="text-muted small mb-1">Clock Out Time</div>
                            <h4 class="fw-bold mb-0 text-dark">
                                {{ $todayRecord && $todayRecord->out_time ? \Carbon\Carbon::parse($todayRecord->out_time)->format('h:i A') : '--:--' }}
                            </h4>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <span class="text-muted small">Need an attendance correction?</span>
                    <a href="{{ route('regularization.index') }}" class="btn btn-sm btn-outline-secondary">Submit Regularization</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Payslip / Compensation Summary -->
    <div class="col-lg-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark"><i class="bi bi-cash-coin me-2 text-primary"></i>Latest Payslip</span>
                @if($latestPayslip)
                    <span class="badge bg-success-subtle text-success">{{ $latestPayslip->payment_status }}</span>
                @endif
            </div>
            <div class="card-body">
                @if($latestPayslip)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="text-muted small">Payroll Cycle</div>
                            <h6 class="fw-bold mb-0 text-dark">{{ \Carbon\Carbon::create($latestPayslip->year, $latestPayslip->month, 1)->format('F Y') }}</h6>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Net Payout</div>
                            <h5 class="fw-bold mb-0 text-success">INR {{ number_format($latestPayslip->net_salary, 2) }}</h5>
                        </div>
                    </div>
                    <div class="row g-2 text-muted small py-2 border-top border-bottom">
                        <div class="col-6">Paid Days: <strong>{{ $latestPayslip->paid_days }}</strong></div>
                        <div class="col-6 text-end">Gross Earnings: <strong>INR {{ number_format($latestPayslip->gross_earnings, 2) }}</strong></div>
                        <div class="col-6">PF Ded: <strong>INR {{ number_format($latestPayslip->pf_employee, 2) }}</strong></div>
                        <div class="col-6 text-end">Total Ded: <strong>INR {{ number_format($latestPayslip->total_deductions, 2) }}</strong></div>
                    </div>
                    <div class="mt-3 text-end">
                        <a href="{{ route('payroll.payslips.show', $latestPayslip) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-pdf me-1"></i> View Full Payslip
                        </a>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-receipt fs-2 text-secondary d-block mb-2"></i>
                        <div class="small fw-medium">No processed payslips available yet for your employee account.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Company Allocated Assets -->
@if($allocatedAssets->isNotEmpty())
    <div class="card card-custom mb-4">
        <div class="card-header bg-white py-3">
            <span class="fw-semibold text-dark"><i class="bi bi-laptop me-2 text-teal"></i>Company Assets Assigned to You</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Asset Tag</th>
                            <th>Item Name</th>
                            <th>Serial Number</th>
                            <th>Allocation Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allocatedAssets as $alloc)
                            <tr>
                                <td><code>{{ $alloc->asset?->tag }}</code></td>
                                <td class="fw-medium text-dark">{{ $alloc->asset?->name }}</td>
                                <td>{{ $alloc->asset?->serial_number ?? 'N/A' }}</td>
                                <td>{{ $alloc->allocated_at?->format('d M Y') }}</td>
                                <td><span class="badge bg-success-subtle text-success">Active In-Possession</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
