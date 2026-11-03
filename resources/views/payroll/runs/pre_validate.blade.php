@extends('layouts.app')

@section('title', 'Payroll Pre-Validation Gate — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Pre-Validation Gate Audit</h4>
            <p class="text-muted small mb-0">Period: {{ date('F Y', mktime(0, 0, 0, $results['month'], 1, $results['year'])) }} ({{ $results['calendar_days'] }} Days) — Integrity verification prior to batch payroll calculation</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.runs.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Control Tower
            </a>
            @if($results['can_proceed'])
                <form action="{{ route('payroll.runs.execute') }}" method="POST">
                    @csrf
                    <input type="hidden" name="year" value="{{ $results['year'] }}">
                    <input type="hidden" name="month" value="{{ $results['month'] }}">
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-play-circle me-1"></i> Proceed to Calculation
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Warnings / Lock Alerts -->
    @if(!empty($results['warnings']))
        @foreach($results['warnings'] as $warning)
            <div class="alert alert-warning shadow-sm d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <div>{{ $warning }}</div>
            </div>
        @endforeach
    @endif

    <!-- Scorecards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Total Headcount</div>
                <div class="fw-bold fs-3 text-slate-800">{{ $results['total_headcount'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-success">
                <div class="text-muted small">Eligible for Payroll</div>
                <div class="fw-bold fs-3 text-success">{{ $results['eligible_count'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-danger">
                <div class="text-muted small">Ineligible / Excluded</div>
                <div class="fw-bold fs-3 text-danger">{{ $results['ineligible_count'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Leave Period Lock</div>
                <div class="fw-bold fs-5 mt-1">
                    @if($results['is_leave_period_locked'])
                        <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i> Locked</span>
                    @else
                        <span class="badge bg-warning text-dark"><i class="bi bi-unlock-fill me-1"></i> Open (Warning)</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Details Tabs -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold text-success" data-bs-toggle="tab" data-bs-target="#tab-eligible">
                        <i class="bi bi-check-circle me-1"></i> Eligible Staff ({{ $results['eligible_count'] }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold text-danger" data-bs-toggle="tab" data-bs-target="#tab-ineligible">
                        <i class="bi bi-x-circle me-1"></i> Ineligible Staff ({{ $results['ineligible_count'] }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content">
                <!-- Eligible Tab -->
                <div class="tab-pane fade show active" id="tab-eligible">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3">Employee</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Standard Gross</th>
                                    <th>Loan EMI Queued</th>
                                    <th class="pe-3">Fine Queued</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results['eligible_employees'] as $emp)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold">{{ $emp['employee_name'] }}</div>
                                            <div class="text-muted small">{{ $emp['employee_code'] }}</div>
                                        </td>
                                        <td>{{ $emp['department'] }}</td>
                                        <td>{{ $emp['designation'] }}</td>
                                        <td class="fw-bold text-slate-800">₹{{ number_format($emp['gross_monthly'], 2) }}</td>
                                        <td>
                                            @if($emp['has_loan_emi'])
                                                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> EMI Due</span>
                                            @else
                                                <span class="text-muted small">None</span>
                                            @endif
                                        </td>
                                        <td class="pe-3">
                                            @if($emp['has_fine_queued'])
                                                <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i> Fine Queued</span>
                                            @else
                                                <span class="text-muted small">None</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No employees passed pre-validation gates.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ineligible Tab -->
                <div class="tab-pane fade" id="tab-ineligible">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3">Employee</th>
                                    <th>Department</th>
                                    <th class="pe-3">Specific Validation Blockers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results['ineligible_employees'] as $inemp)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold">{{ $inemp['employee_name'] }}</div>
                                            <div class="text-muted small">{{ $inemp['employee_code'] }}</div>
                                        </td>
                                        <td>{{ $inemp['department'] }}</td>
                                        <td class="pe-3">
                                            <ul class="mb-0 text-danger small ps-3">
                                                @foreach($inemp['reasons'] as $r)
                                                    <li>{{ $r }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">All active employees passed validation checks.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

