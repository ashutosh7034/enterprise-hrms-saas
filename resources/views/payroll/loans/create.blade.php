@extends('layouts.app')

@section('title', 'Apply for Loan or Salary Advance — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Apply for Loan / Salary Advance</h4>
            <p class="text-muted small mb-0">Submit advance request with repayment tenure and installment terms</p>
        </div>
        <a href="{{ route('payroll.loans.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Loans
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
                    <form action="{{ route('payroll.loans.store') }}" method="POST">
                        @csrf
                        @if($roleName !== 'employee_ess')
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Target Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->full_name }} ({{ $emp->employee_code }}) — {{ $emp->department?->name ?? 'General' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Advance / Loan Type <span class="text-danger">*</span></label>
                                <select name="loan_type" class="form-select" required>
                                    <option value="Salary Advance" {{ old('loan_type') === 'Salary Advance' ? 'selected' : '' }}>Salary Advance</option>
                                    <option value="Personal Loan" {{ old('loan_type') === 'Personal Loan' ? 'selected' : '' }}>Personal Loan</option>
                                    <option value="Emergency Medical Advance" {{ old('loan_type') === 'Emergency Medical Advance' ? 'selected' : '' }}>Emergency Medical Advance</option>
                                    <option value="Festival Advance" {{ old('loan_type') === 'Festival Advance' ? 'selected' : '' }}>Festival Advance</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Principal Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="principal_amount" id="principalAmount" class="form-control fw-bold" placeholder="e.g. 25000.00" required value="{{ old('principal_amount') }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Repayment Tenure (Months) <span class="text-danger">*</span></label>
                                <input type="number" name="tenure_months" id="tenureMonths" class="form-control" min="1" max="36" required value="{{ old('tenure_months', 3) }}">
                                <div class="form-text">Number of monthly installments.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Annual Interest Rate (%)</label>
                                <input type="number" step="0.01" name="interest_rate_percent" id="interestRate" class="form-control" value="{{ old('interest_rate_percent', 0.00) }}">
                                <div class="form-text">Default 0.00% for salary advances.</div>
                            </div>
                        </div>

                        <!-- Estimated EMI Banner -->
                        <div class="p-3 bg-light rounded-3 mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">Projected Monthly EMI:</span>
                                <div class="text-muted small">Deducted automatically via payroll</div>
                            </div>
                            <div>
                                <span class="fs-4 fw-bold text-teal-800" id="projectedEmi">₹0.00</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Reason / Purpose of Advance</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Provide clear purpose or emergency details">{{ old('reason') }}</textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-send me-1"></i> Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateEmi() {
        const principal = parseFloat(document.getElementById('principalAmount').value) || 0;
        const tenure = parseInt(document.getElementById('tenureMonths').value) || 1;
        const rate = parseFloat(document.getElementById('interestRate').value) || 0;

        const totalInterest = rate > 0 ? (principal * (rate / 100) * (tenure / 12)) : 0;
        const totalPayable = principal + totalInterest;
        const emi = tenure > 0 ? (totalPayable / tenure) : principal;

        document.getElementById('projectedEmi').innerText = '₹' + emi.toFixed(2);
    }

    document.getElementById('principalAmount').addEventListener('input', updateEmi);
    document.getElementById('tenureMonths').addEventListener('input', updateEmi);
    document.getElementById('interestRate').addEventListener('input', updateEmi);
</script>
@endsection

