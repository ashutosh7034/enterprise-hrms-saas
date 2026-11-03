@extends('layouts.app')

@section('title', 'New Salary Structure — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Create Salary Structure</h4>
            <p class="text-muted small mb-0">{{ $company->name }} — Define new company salary structure and component rules</p>
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

    <form action="{{ route('payroll.structures.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold mb-0 text-slate-800">Structure Master Details</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Structure Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. PLANT-STANDARD-2026" required value="{{ old('code') }}">
                            <div class="form-text">Unique alphanumeric identifier for this company.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Structure Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Standard Plant Staff Structure" required value="{{ old('name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Applicable employee grade or department notes">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_target_ot" id="isTargetOt" value="1" {{ old('is_target_ot') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="isTargetOt">Enable Target Overtime (Flat Monthly OT)</label>
                            <div class="form-text">When checked, employees under this structure receive a fixed flat OT amount instead of biometric hourly OT.</div>
                        </div>
                        <div class="mb-3" id="targetOtAmountDiv" style="{{ old('is_target_ot') ? '' : 'display: none;' }}">
                            <label class="form-label small fw-semibold">Target OT Fixed Monthly Amount (₹)</label>
                            <input type="number" step="0.01" name="target_ot_amount" class="form-control" placeholder="e.g. 2500.00" value="{{ old('target_ot_amount', 0.00) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-slate-800">Include Salary Components</h6>
                        <span class="badge bg-light text-muted border">Select Applicable</span>
                    </div>
                    <div class="card-body pt-0">
                        <p class="text-muted small mb-3">Check components to include in this structure and define their calculation strategy.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40px;">Select</th>
                                        <th>Component</th>
                                        <th>Type</th>
                                        <th>Calculation Rule</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($components as $i => $comp)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input" name="components[{{ $i }}][salary_component_id]" value="{{ $comp->id }}" {{ in_array($comp->code, ['BASIC', 'HRA', 'PF_EMP', 'PT']) ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $comp->name }}</div>
                                                <div class="text-muted small">{{ $comp->code }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $comp->component_type === 'Earning' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $comp->component_type }}
                                                </span>
                                            </td>
                                            <td>
                                                <select name="components[{{ $i }}][calculation_type]" class="form-select form-select-sm">
                                                    <option value="Fixed Amount">Fixed Amount / Direct</option>
                                                    <option value="Percentage of Component">Percentage Based</option>
                                                    <option value="Formula">Formula</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> Save Salary Structure
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('isTargetOt').addEventListener('change', function() {
        document.getElementById('targetOtAmountDiv').style.display = this.checked ? 'block' : 'none';
    });
</script>
@endsection

