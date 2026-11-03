@extends('layouts.app')

@section('title', 'Register Asset')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register Asset</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Register Asset Item</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Enroll equipment, laptop, vehicle, tool, or safety gear into company inventory.</p>
    </div>
    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Inventory
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Asset Specifications & Costing</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('assets.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Asset Name / Model <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Dell Latitude 5420 or Safety Harness" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Asset Code (Unique) <span class="text-danger">*</span></label>
                            <input type="text" name="asset_code" class="form-control code-font @error('asset_code') is-invalid @enderror" value="{{ old('asset_code') }}" placeholder="e.g. AST-LAP-0012" required>
                            @error('asset_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Asset Category <span class="text-danger">*</span></label>
                            <select name="asset_category_id" class="form-select @error('asset_category_id') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('asset_category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }} ({{ $cat->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Location / Site Base</label>
                            <select name="location_id" class="form-select @error('location_id') is-invalid @enderror">
                                <option value="">Default Head Office</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Asset Tag / Barcode</label>
                            <input type="text" name="asset_tag" class="form-control code-font" placeholder="Physical tag barcode" value="{{ old('asset_tag') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control code-font" placeholder="Manufacturer serial #" value="{{ old('serial_number') }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Purchase Cost (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purchase_cost" class="form-control code-font @error('purchase_cost') is-invalid @enderror" placeholder="0.00" value="{{ old('purchase_cost', '0.00') }}" required>
                            @error('purchase_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Initial Condition <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                <option value="Brand New" {{ old('condition') === 'Brand New' ? 'selected' : '' }}>Brand New</option>
                                <option value="Good" {{ old('condition', 'Good') === 'Good' ? 'selected' : '' }}>Good / Refurbished</option>
                                <option value="Fair" {{ old('condition') === 'Fair' ? 'selected' : '' }}>Fair / Used</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_returnable" id="is_returnable" value="1" {{ old('is_returnable', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_returnable" style="font-weight: 500;">
                            Asset is Returnable (Must be collected back during employee separation)
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 500;">Remarks / Specifications</label>
                        <textarea name="remarks" rows="3" class="form-control" placeholder="CPU/RAM specs, warranty information, supplier details...">{{ old('remarks') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">Register Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

