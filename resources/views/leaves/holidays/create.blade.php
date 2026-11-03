@extends('layouts.app')

@section('title', 'Add Holiday')

@section('content')
<div class="mb-4">
    <a href="{{ route('leaves.holidays.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Holidays
    </a>
    <h1 class="h4 mb-1 text-slate-900 font-bold mt-2">Add New Holiday</h1>
    <p class="text-muted small mb-0">Define national, festival, or site-specific calendar holidays.</p>
</div>

<div class="card card-custom">
    <div class="card-body p-4">
        <form action="{{ route('leaves.holidays.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Holiday Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Republic Day" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="form-control @error('date') is-invalid @enderror" required>
                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Holiday Type <span class="text-danger">*</span></label>
                    <select name="holiday_type" class="form-select">
                        <option value="Festival Holiday">Festival Holiday</option>
                        <option value="National Holiday">National Holiday</option>
                        <option value="Site-Specific Holiday">Site-Specific Holiday</option>
                        <option value="Company Holiday">Company Holiday</option>
                        <option value="Regional Holiday">Regional Holiday</option>
                        <option value="Optional Holiday">Optional Holiday</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Specific Location / Site</label>
                    <select name="location_id" class="form-select">
                        <option value="">All Locations (Company-Wide)</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }} {{ $loc->is_site ? '(Site)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Specific Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Specific Employee Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_ground_staff_applicable" value="1" id="is_ground_staff_applicable" {{ old('is_ground_staff_applicable', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_ground_staff_applicable">Applies to Ground Staff Sites</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="is_paid" {{ old('is_paid', '1') ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="is_paid">Paid Holiday</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional notes, gazette notification details...">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('leaves.holidays.index') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-accent px-4">Save Holiday</button>
            </div>
        </form>
    </div>
</div>
@endsection

