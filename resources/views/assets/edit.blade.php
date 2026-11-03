@extends('layouts.app')

@section('title', "Edit Asset: {$asset->asset_code}")

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.show', $asset->id) }}">{{ $asset->asset_code }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Edit Asset Item</h4>
    </div>
    <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Asset
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white">
                <span style="font-weight: 600;">Update Asset: <span class="code-font">{{ $asset->asset_code }}</span></span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('assets.update', $asset->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Asset Name / Model <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $asset->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Asset Category <span class="text-danger">*</span></label>
                            <select name="asset_category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('asset_category_id', $asset->asset_category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Tag / Barcode</label>
                            <input type="text" name="asset_tag" class="form-control code-font" value="{{ old('asset_tag', $asset->asset_tag) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control code-font" value="{{ old('serial_number', $asset->serial_number) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Purchase Cost (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="purchase_cost" class="form-control code-font" value="{{ old('purchase_cost', $asset->purchase_cost) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-weight: 500;">Inventory Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="Available" {{ old('status', $asset->status) === 'Available' ? 'selected' : '' }}>Available</option>
                                <option value="Allocated" {{ old('status', $asset->status) === 'Allocated' ? 'selected' : '' }}>Allocated</option>
                                <option value="Returned" {{ old('status', $asset->status) === 'Returned' ? 'selected' : '' }}>Returned</option>
                                <option value="Damaged" {{ old('status', $asset->status) === 'Damaged' ? 'selected' : '' }}>Damaged</option>
                                <option value="Lost" {{ old('status', $asset->status) === 'Lost' ? 'selected' : '' }}>Lost</option>
                                <option value="Under Repair" {{ old('status', $asset->status) === 'Under Repair' ? 'selected' : '' }}>Under Repair</option>
                                <option value="Retired" {{ old('status', $asset->status) === 'Retired' ? 'selected' : '' }}>Retired</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Location / Site Base</label>
                            <select name="location_id" class="form-select">
                                <option value="">Default Head Office</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id', $asset->location_id) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight: 500;">Physical Condition</label>
                            <input type="text" name="condition" class="form-control" value="{{ old('condition', $asset->condition) }}">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_returnable" id="is_returnable" value="1" {{ old('is_returnable', $asset->is_returnable) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_returnable" style="font-weight: 500;">
                            Asset is Returnable
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight: 500;">Remarks / Specs</label>
                        <textarea name="remarks" rows="3" class="form-control">{{ old('remarks', $asset->remarks) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-brand px-4">Update Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

