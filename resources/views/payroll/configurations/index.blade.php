@extends('layouts.app')

@section('title', 'Payroll & Statutory Configuration — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Payroll & Statutory Configuration</h4>
            <p class="text-muted small mb-0">Define corporate pay parameters, calculation rules, and statutory compliance slabs</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Section 1: General Payroll Configuration -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-slate-800">General Payroll Rules & Calendars</h6>
                </div>
                <div class="card-body pt-0">
                    <form action="{{ route('payroll.configurations.payroll.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Attendance Calculation Basis</label>
                            <select name="attendance_calculation_basis" class="form-select">
                                <option value="Actual Calendar Days" {{ $payrollConfig->attendance_calculation_basis === 'Actual Calendar Days' ? 'selected' : '' }}>Actual Calendar Days (28/29/30/31)</option>
                                <option value="Standard 30 Days" {{ $payrollConfig->attendance_calculation_basis === 'Standard 30 Days' ? 'selected' : '' }}>Standard 30 Days (Fixed Base)</option>
                                <option value="Standard 26 Working Days" {{ $payrollConfig->attendance_calculation_basis === 'Standard 26 Working Days' ? 'selected' : '' }}>Standard 26 Working Days (Industrial)</option>
                            </select>
                            <div class="form-text">Divisor used to calculate per-day rate and pro-rata attendance pay.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payroll Cutoff Day</label>
                                <input type="number" name="payroll_cutoff_day" class="form-control" min="1" max="31" value="{{ $payrollConfig->payroll_cutoff_day }}">
                                <div class="form-text">Punches cutoff day of month.</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Disbursement Pay Day</label>
                                <input type="number" name="pay_day_of_month" class="form-control" min="1" max="31" value="{{ $payrollConfig->pay_day_of_month }}">
                                <div class="form-text">Target salary payout day.</div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">OT Hourly Divisor (Hours)</label>
                                <input type="number" name="ot_hourly_divisor" class="form-control" value="{{ $payrollConfig->ot_hourly_divisor }}">
                                <div class="form-text">e.g. 208 hrs (26 days * 8h).</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">OT Rate Multiplier</label>
                                <input type="number" step="0.1" name="ot_multiplier" class="form-control" value="{{ $payrollConfig->ot_multiplier }}">
                                <div class="form-text">e.g. 2.0x for double rate.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Net Pay Rounding Rule</label>
                            <select name="net_pay_rounding_rule" class="form-select">
                                <option value="Nearest Rupee" {{ $payrollConfig->net_pay_rounding_rule === 'Nearest Rupee' ? 'selected' : '' }}>Round to Nearest Rupee</option>
                                <option value="Floor" {{ $payrollConfig->net_pay_rounding_rule === 'Floor' ? 'selected' : '' }}>Floor (Round Down)</option>
                                <option value="Ceiling" {{ $payrollConfig->net_pay_rounding_rule === 'Ceiling' ? 'selected' : '' }}>Ceiling (Round Up)</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Payroll Rules
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section 2: Statutory Compliance Configuration -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-slate-800">Statutory Compliance (PF, ESI, LWF)</h6>
                </div>
                <div class="card-body pt-0">
                    <form action="{{ route('payroll.configurations.statutory.update') }}" method="POST">
                        @csrf
                        <h6 class="text-teal-700 fw-bold small text-uppercase mb-2">Provident Fund (EPF)</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Employee PF (%)</label>
                                <input type="number" step="0.01" name="pf_employee_rate" class="form-control" value="{{ $statutoryConfig->pf_employee_rate }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Employer PF (%)</label>
                                <input type="number" step="0.01" name="pf_employer_rate" class="form-control" value="{{ $statutoryConfig->pf_employer_rate }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">PF Wage Ceiling (₹)</label>
                                <input type="number" step="0.01" name="pf_wage_ceiling" class="form-control" value="{{ $statutoryConfig->pf_wage_ceiling }}">
                            </div>
                            <div class="col-6 d-flex align-items-center pt-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="pf_restrict_to_ceiling" id="pfCeiling" value="1" {{ $statutoryConfig->pf_restrict_to_ceiling ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="pfCeiling">Cap at ₹15,000</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3 text-muted">

                        <h6 class="text-teal-700 fw-bold small text-uppercase mb-2">Employees' State Insurance (ESI)</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Employee ESI (%)</label>
                                <input type="number" step="0.01" name="esi_employee_rate" class="form-control" value="{{ $statutoryConfig->esi_employee_rate }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Employer ESI (%)</label>
                                <input type="number" step="0.01" name="esi_employer_rate" class="form-control" value="{{ $statutoryConfig->esi_employer_rate }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Wage Ceiling (₹)</label>
                                <input type="number" step="0.01" name="esi_wage_ceiling" class="form-control" value="{{ $statutoryConfig->esi_wage_ceiling }}">
                            </div>
                        </div>

                        <hr class="my-3 text-muted">

                        <h6 class="text-teal-700 fw-bold small text-uppercase mb-2">Labour Welfare Fund (LWF)</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Employee (₹)</label>
                                <input type="number" step="0.01" name="lwf_employee_amount" class="form-control" value="{{ $statutoryConfig->lwf_employee_amount }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Employer (₹)</label>
                                <input type="number" step="0.01" name="lwf_employer_amount" class="form-control" value="{{ $statutoryConfig->lwf_employer_amount }}">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Deduction Cycle</label>
                                <select name="lwf_deduction_frequency" class="form-select">
                                    <option value="Bi-Annual June/Dec" {{ $statutoryConfig->lwf_deduction_frequency === 'Bi-Annual June/Dec' ? 'selected' : '' }}>June & Dec</option>
                                    <option value="Monthly" {{ $statutoryConfig->lwf_deduction_frequency === 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Statutory Rules
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section 3: Professional Tax Slabs Table -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-slate-800">State Professional Tax (PT) Slabs</h6>
                    <span class="badge bg-light text-muted border">Configured Slabs</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3">State</th>
                                <th>Applicable Gender</th>
                                <th>Salary Bracket (Monthly)</th>
                                <th>Standard Monthly Tax</th>
                                <th class="pe-3">Special Month (e.g. Feb)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ptSlabs as $slab)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $slab->state }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $slab->gender }}</span>
                                    </td>
                                    <td>
                                        ₹{{ number_format($slab->min_monthly_salary, 2) }}
                                        — {{ $slab->max_monthly_salary ? '₹' . number_format($slab->max_monthly_salary, 2) : 'Above' }}
                                    </td>
                                    <td class="fw-semibold text-teal-700">₹{{ number_format($slab->tax_amount, 2) }}</td>
                                    <td class="pe-3">
                                        @if($slab->special_month && $slab->special_tax_amount)
                                            <span class="badge bg-info-subtle text-info">Month {{ $slab->special_month }}: ₹{{ number_format($slab->special_tax_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted small">Standard</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No state PT slabs configured yet. Default slabs will auto-generate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

