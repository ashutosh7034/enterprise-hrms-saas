@extends('layouts.app')

@section('title', 'Locations & Sites')

@section('content')
<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 12px;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
        <li class="breadcrumb-item text-muted">Masters</li>
        <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Locations & Plant Sites</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Locations & Plant Sites</h4>
        <p class="text-muted small mb-0">Managing Branches, Plants and Ground Staff Sites for <strong>{{ $activeCompany?->name }}</strong></p>
    </div>
    <button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#createLocationModal">
        <i class="bi bi-plus-lg"></i>
        <span>Add Location</span>
    </button>
</div>

<!-- Summary KPI Tiles -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Total Sites</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $locations->total() }}</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #EEF2FF; color: #4F46E5;">
                    <i class="bi bi-geo-alt fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Active Units</div>
                    <div class="fs-4 fw-bold text-success mt-1">
                        {{ $locations->where('status', 'Active')->count() }}
                    </div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #ECFDF3; color: #12B76A;">
                    <i class="bi bi-building-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Active Entity</div>
                    <div class="fs-6 fw-bold text-dark mt-1 text-truncate" style="max-width: 200px;">{{ $activeCompany?->name ?? 'Enterprise HRMS' }}</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #F0FDF9; color: #0D9488;">
                    <i class="bi bi-buildings fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card card-enterprise bg-white">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark" style="font-size: 14px;">Active Company Locations</span>
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">{{ $locations->total() }} Recorded</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-enterprise mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 120px;">Code</th>
                        <th>Location Name</th>
                        <th style="width: 170px;">Site Classification</th>
                        <th style="width: 150px;">State (PT Slabs)</th>
                        <th>Address</th>
                        <th style="width: 120px;">Status</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $loc)
                        <tr>
                            <td>
                                <span class="badge bg-dark code-font px-2 py-1" style="font-size: 11px;">{{ $loc->code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $loc->name }}</div>
                            </td>
                            <td>
                                @if($loc->is_site)
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" style="font-size: 11.5px;">
                                        <i class="bi bi-cone-striped me-1"></i>Ground Staff Site
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11.5px;">
                                        <i class="bi bi-building me-1"></i>Office / Plant
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-medium text-dark">{{ $loc->state }}</span>
                            </td>
                            <td class="text-muted small">{{ $loc->address ?? '—' }}</td>
                            <td>
                                @if($loc->status === 'Active')
                                    <span class="status-pill status-pill-success"><span class="dot"></span>Active</span>
                                @else
                                    <span class="status-pill status-pill-danger"><span class="dot"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm py-1 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $loc->id }}" title="Edit Location">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('locations.destroy', $loc) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this location?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 rounded-2" title="Deactivate">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editLocationModal{{ $loc->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="{{ route('locations.update', $loc) }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header bg-light border-bottom p-3">
                                        <h6 class="modal-title fw-bold text-dark mb-0">Edit Location: {{ $loc->name }}</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Location / Plant Name</label>
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $loc->name }}" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Location Code</label>
                                                <input type="text" name="code" class="form-control form-control-sm" value="{{ $loc->code }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">State (for PT)</label>
                                                <input type="text" name="state" class="form-control form-control-sm" value="{{ $loc->state }}" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Address Details</label>
                                            <textarea name="address" class="form-control form-control-sm" rows="3">{{ $loc->address }}</textarea>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="Active" {{ $loc->status === 'Active' ? 'selected' : '' }}>Active</option>
                                                    <option value="Inactive" {{ $loc->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="col-6 d-flex align-items-center pt-3">
                                                <div class="form-check">
                                                    <input type="checkbox" name="is_site" value="1" class="form-check-input" id="is_site_{{ $loc->id }}" {{ $loc->is_site ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-semibold" for="is_site_{{ $loc->id }}">Ground Staff Work Site</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-top p-3">
                                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-brand btn-sm px-3">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-geo-alt fs-2 text-secondary opacity-50 d-block mb-2"></i>
                                    <div class="fw-semibold">No locations registered for this company.</div>
                                    <p class="small text-muted mb-3">Add plant facilities, office sites, or ground work zones.</p>
                                    <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                                        <i class="bi bi-plus-lg me-1"></i> Add Location
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($locations->hasPages())
        <div class="p-3 border-top">
            {{ $locations->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('locations.store') }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            @csrf
            <div class="modal-header bg-light border-bottom p-3">
                <h6 class="modal-title fw-bold text-dark mb-0">Register New Location / Site</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Location / Plant Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Pune Central Plant" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Location Code</label>
                        <input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. LOC-PUN" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">State (for PT)</label>
                        <input type="text" name="state" class="form-control form-control-sm" value="Maharashtra" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Address Details</label>
                    <textarea name="address" class="form-control form-control-sm" rows="3" placeholder="Full plant / site address"></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-6 d-flex align-items-center pt-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_site" value="1" class="form-check-input" id="is_site_new">
                            <label class="form-check-label small fw-semibold" for="is_site_new">Ground Staff Work Site</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand btn-sm px-3">Create Location</button>
            </div>
        </form>
    </div>
</div>
@endsection
