@extends('layouts.app')

@section('title', 'Assign Salary Structure — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Assign Salary Structure to Employee</h4>
            <p class="text-muted small mb-0">Map employee compensation breakdown with effective dating and auto-revision history</p>
        </div>
        <a href="{{ route('payroll.structures.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Structures
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

    <form action="{{ route('payroll.structures.assign.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold mb-0 text-slate-800">1. Employee & Structure Selection</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Target Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">-- Choose Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_code }}) — {{ $emp->department?->name ?? 'General' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Salary Structure Model <span class="text-danger">*</span></label>
                            <select name="salary_structure_id" class="form-select" required>
                                <option value="">-- Choose Structure --</option>
                                @foreach($structures as $s)
                                    <option value="{{ $s->id }}" {{ old('salary_structure_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }} ({{ $s->code }}) {{ $s->is_target_ot ? '[Target OT]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Effective From Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_from_date" class="form-control" required value="{{ old('effective_from_date', date('Y-m-01')) }}">
                            <div class="form-text">Previous approved assignment will be superseded as of this date.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Revision / Assignment Remarks</label>
                            <textarea name="revision_notes" class="form-control" rows="2" placeholder="e.g. Annual Appraisal Revision 2026 / New Joinee Offer">{{ old('revision_notes') }}</textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_pf_eligible" id="isPf" value="1" {{ old('is_pf_eligible', true) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="isPf">PF Eligible (12%)</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_esi_eligible" id="isEsi" value="1" {{ old('is_esi_eligible', true) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="isEsi">ESI Eligible (0.75%)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold mb-0 text-slate-800">2. Salary Compensation Breakdown (Monthly)</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Total Gross Monthly Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="gross_monthly" id="grossMonthly" class="form-control fw-bold text-teal-700 fs-5" placeholder="e.g. 50000.00" required value="{{ old('gross_monthly') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Annual CTC (₹)</label>
                            <input type="number" step="0.01" name="ctc_annual" id="ctcAnnual" class="form-control" placeholder="Gross * 12" value="{{ old('ctc_annual') }}">
                        </div>

                        <hr class="my-3 text-muted">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Basic Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="basic_salary" id="basicSalary" class="form-control" placeholder="Standard 50% of Gross" required value="{{ old('basic_salary') }}">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">HRA (₹)</label>
                                <input type="number" step="0.01" name="hra" id="hraSalary" class="form-control" placeholder="25% of Gross" value="{{ old('hra', 0.00) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Dearness Allowance (DA) (₹)</label>
                                <input type="number" step="0.01" name="da" class="form-control" placeholder="0.00" value="{{ old('da', 0.00) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Special / Site Allowance (₹)</label>
                            <input type="number" step="0.01" name="special_allowance" id="specialAllowance" class="form-control" placeholder="Balancing component" value="{{ old('special_allowance', 0.00) }}">
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> Confirm & Assign Salary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('grossMonthly').addEventListener('input', function() {
        const gross = parseFloat(this.value) || 0;
        document.getElementById('ctcAnnual').value = (gross * 12).toFixed(2);
        
        // Suggest standard industrial breakdown: 50% Basic, 25% HRA, 25% Special
        if (gross > 0 && !document.getElementById('basicSalary').value) {
            document.getElementById('basicSalary').value = (gross * 0.50).toFixed(2);
            document.getElementById('hraSalary').value = (gross * 0.25).toFixed(2);
            document.getElementById('specialAllowance').value = (gross * 0.25).toFixed(2);
        }
    });
</script>
@endsection

