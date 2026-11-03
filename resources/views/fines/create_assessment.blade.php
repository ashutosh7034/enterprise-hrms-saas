@extends('layouts.app')

@section('title', 'Log Damage Assessment')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('damage-assessments.index') }}">Damage & Fines</a></li>
                <li class="breadcrumb-item active" aria-current="page">Log Assessment</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Log Asset Damage Assessment</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Assess damage, compute depreciated asset ceiling, and establish recovery terms.</p>
    </div>
    <a href="{{ route('damage-assessments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Assessments
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Incident Investigation & Assessment</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('damage-assessments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Asset Allocation Record <span class="text-danger">*</span></label>
                        <select name="asset_allocation_id" class="form-select @error('asset_allocation_id') is-invalid @enderror" required>
                            <option value="">Select Asset Allocation</option>
                            @foreach($allocations as $alloc)
                                <option value="{{ $alloc->id }}" {{ (old('asset_allocation_id') == $alloc->id || $selectedAllocationId == $alloc->id) ? 'selected' : '' }}>
                                    {{ $alloc->asset->asset_code }} ({{ $alloc->asset->name }}) — Assigned to {{ $alloc->employee->full_name }} ({{ $alloc->employee->employee_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('asset_allocation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Standard Fine Policy (Optional Tariff)</label>
                            <select name="fine_master_id" class="form-select">
                                <option value="">Custom / Direct Assessment</option>
                                @foreach($fineMasters as $fine)
                                    <option value="{{ $fine->id }}" {{ old('fine_master_id') == $fine->id ? 'selected' : '' }}>
                                        {{ $fine->fine_name }} (₹{{ number_format($fine->default_amount, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Depreciation Valuation Strategy <span class="text-danger">*</span></label>
                            <select name="depreciation_strategy" class="form-select" required>
                                <option value="Straight-Line" {{ old('depreciation_strategy', 'Straight-Line') === 'Straight-Line' ? 'selected' : '' }}>Straight-Line Method (SLM)</option>
                                <option value="Written-Down Value" {{ old('depreciation_strategy') === 'Written-Down Value' ? 'selected' : '' }}>Written-Down Value (WDV - 40% p.a.)</option>
                                <option value="Fixed Assessment" {{ old('depreciation_strategy') === 'Fixed Assessment' ? 'selected' : '' }}>Fixed Tariff / Flat Fee</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Assessed Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="assessed_amount" class="form-control code-font @error('assessed_amount') is-invalid @enderror" value="{{ old('assessed_amount') }}" placeholder="0.00" required>
                            @error('assessed_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Recovery Method <span class="text-danger">*</span></label>
                            <select name="recovery_method" class="form-select" required>
                                <option value="Payroll Queue" {{ old('recovery_method', 'Payroll Queue') === 'Payroll Queue' ? 'selected' : '' }}>Queue for Phase 5 Payroll Deduction</option>
                                <option value="Direct Payment" {{ old('recovery_method') === 'Direct Payment' ? 'selected' : '' }}>Direct Payment (Cash / UPI / Bank)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Installment Count <span class="text-danger">*</span></label>
                            <input type="number" name="installment_count" class="form-control code-font" value="{{ old('installment_count', 1) }}" min="1" max="12" required>
                            <div class="form-text" style="font-size: 11px;">Max 12 installments.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Physical Damage Evidence / Inspection Notes</label>
                        <textarea name="condition_evidence" rows="2" class="form-control" placeholder="Describe physical damages observed, technician quote, serial numbers verified...">{{ old('condition_evidence') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 500;">Investigation Finding & Culpability Notes</label>
                        <textarea name="investigation_notes" rows="2" class="form-control" placeholder="Explain whether damage occurred due to gross negligence, accidental factory conditions, or unauthorized misuse...">{{ old('investigation_notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('damage-assessments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">Save & Queue for Authorization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-custom">
            <div class="card-header bg-white" style="font-weight: 600;">
                <i class="bi bi-info-circle me-1 text-primary"></i> Valuation Guidelines
            </div>
            <div class="card-body" style="font-size: 13px; color: #475569;">
                <p>
                    <strong>Depreciation Ceiling:</strong> The system automatically computes the asset's current book value based on original purchase cost and age.
                </p>
                <p>
                    <strong>Installments:</strong> When approved, the system generates sequential installment records ready for direct settlement or queued for the Phase 5 Payroll deduction engine.
                </p>
                <p class="mb-0">
                    <strong>Waiver Authority:</strong> Any waiver of assessed liability requires Head Office Admin authorization.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

