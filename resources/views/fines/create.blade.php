@extends('layouts.app')

@section('title', 'Create Fine Policy')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('fine-masters.index') }}">Fine Policies</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Create Fine Master Policy</h4>
    </div>
    <a href="{{ route('fine-masters.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Policies
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Policy Specifications</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('fine-masters.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Fine Code <span class="text-danger">*</span></label>
                            <input type="text" name="fine_code" class="form-control code-font @error('fine_code') is-invalid @enderror" value="{{ old('fine_code') }}" placeholder="e.g. FINE-SCRATCH-01" required>
                            @error('fine_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Policy Name <span class="text-danger">*</span></label>
                            <input type="text" name="fine_name" class="form-control @error('fine_name') is-invalid @enderror" value="{{ old('fine_name') }}" placeholder="e.g. Laptop Screen Damage Fee" required>
                            @error('fine_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Violation Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="Asset Damage" {{ old('category') === 'Asset Damage' ? 'selected' : '' }}>Asset Damage</option>
                                <option value="Asset Loss" {{ old('category') === 'Asset Loss' ? 'selected' : '' }}>Asset Loss</option>
                                <option value="Asset Non-Return" {{ old('category') === 'Asset Non-Return' ? 'selected' : '' }}>Asset Non-Return</option>
                                <option value="Other Policy Violation" {{ old('category') === 'Other Policy Violation' ? 'selected' : '' }}>Other Policy Violation</option>
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Applicable Asset Category</label>
                            <select name="asset_category_id" class="form-select @error('asset_category_id') is-invalid @enderror">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('asset_category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Default Tariff Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="default_amount" class="form-control code-font @error('default_amount') is-invalid @enderror" value="{{ old('default_amount', '500.00') }}" required>
                            @error('default_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror" value="{{ old('effective_date', now()->format('Y-m-d')) }}" required>
                            @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="Active" {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 500;">Policy Remarks / Criteria</label>
                        <textarea name="remarks" rows="3" class="form-control" placeholder="Operational rules, inquiry triggers...">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('fine-masters.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">Save Policy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

