@extends('layouts.app')

@section('title', "Edit Fine Policy: {$fineMaster->fine_code}")

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('fine-masters.index') }}">Fine Policies</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit {{ $fineMaster->fine_code }}</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Edit Fine Master Policy</h4>
    </div>
    <a href="{{ route('fine-masters.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Policies
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Update: <span class="code-font">{{ $fineMaster->fine_code }}</span></span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('fine-masters.update', $fineMaster->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Fine Code</label>
                            <input type="text" class="form-control code-font" value="{{ $fineMaster->fine_code }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Policy Name <span class="text-danger">*</span></label>
                            <input type="text" name="fine_name" class="form-control" value="{{ old('fine_name', $fineMaster->fine_name) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Violation Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="Asset Damage" {{ old('category', $fineMaster->category) === 'Asset Damage' ? 'selected' : '' }}>Asset Damage</option>
                                <option value="Asset Loss" {{ old('category', $fineMaster->category) === 'Asset Loss' ? 'selected' : '' }}>Asset Loss</option>
                                <option value="Asset Non-Return" {{ old('category', $fineMaster->category) === 'Asset Non-Return' ? 'selected' : '' }}>Asset Non-Return</option>
                                <option value="Other Policy Violation" {{ old('category', $fineMaster->category) === 'Other Policy Violation' ? 'selected' : '' }}>Other Policy Violation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Applicable Asset Category</label>
                            <select name="asset_category_id" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('asset_category_id', $fineMaster->asset_category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Default Tariff Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="default_amount" class="form-control code-font" value="{{ old('default_amount', $fineMaster->default_amount) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date', $fineMaster->effective_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Active" {{ old('status', $fineMaster->status) === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ old('status', $fineMaster->status) === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 500;">Policy Remarks / Criteria</label>
                        <textarea name="remarks" rows="3" class="form-control">{{ old('remarks', $fineMaster->remarks) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('fine-masters.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">Update Policy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

