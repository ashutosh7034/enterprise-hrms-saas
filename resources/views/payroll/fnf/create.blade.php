@extends('layouts.app')

@section('title', 'Prepare Full & Final Settlement — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Prepare Full & Final Settlement</h4>
            <p class="text-muted small mb-0">Compute employee exit dues, notice period adjustment, leave encashment & recovery deductions</p>
        </div>
        <a href="{{ route('payroll.fnf.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to F&F
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <form action="{{ route('payroll.fnf.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Resigning / Exiting Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">-- Choose Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_code }}) — {{ $emp->department?->name ?? 'General' }} [{{ $emp->status }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Resignation Submission Date <span class="text-danger">*</span></label>
                                <input type="date" name="resignation_date" class="form-control" required value="{{ old('resignation_date', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Last Working Day (LWD) <span class="text-danger">*</span></label>
                                <input type="date" name="last_working_date" class="form-control" required value="{{ old('last_working_date', date('Y-m-d', strtotime('+30 days'))) }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Required Notice Period (Days)</label>
                                <input type="number" name="notice_period_required_days" class="form-control" placeholder="30" value="{{ old('notice_period_required_days', 30) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Served Notice Period (Days)</label>
                                <input type="number" name="notice_period_served_days" class="form-control" placeholder="30" value="{{ old('notice_period_served_days', 30) }}">
                                <div class="form-text">Any shortfall will automatically deduct from settlement.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Unpaid Working Days in Exit Month</label>
                                <input type="number" step="0.5" name="unpaid_salary_days" class="form-control" placeholder="Default to LWD day of month" value="{{ old('unpaid_salary_days') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">PL Balance for Encashment (Days)</label>
                                <input type="number" step="0.5" name="leave_encashment_days" class="form-control" placeholder="Auto-fetches confirmed PL balance if empty" value="{{ old('leave_encashment_days') }}">
                            </div>
                        </div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="force_gratuity" id="forceGratuity" value="1" {{ old('force_gratuity') ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="forceGratuity">Force Gratuity Payout (Overrides 5-Year Tenure Requirement)</label>
                            <div class="form-text">Gratuity is automatically calculated if tenure is 5+ years; enable this for special contractual waivers.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Additional Deductions / Recoveries (₹)</label>
                            <input type="number" step="0.01" name="other_deductions_amount" class="form-control" placeholder="0.00" value="{{ old('other_deductions_amount', 0.00) }}">
                            <div class="form-text">Outstanding loans and pending asset damage fines are automatically offset.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Settlement Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Exit clearance notes, handover completion remarks">{{ old('remarks') }}</textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-calculator me-1"></i> Compute & Generate Draft
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

