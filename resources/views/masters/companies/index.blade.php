@extends('layouts.app')

@section('title', 'Company Master')

@section('content')
<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 12px;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
        <li class="breadcrumb-item text-muted">Masters</li>
        <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Company Master</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Company Master</h4>
        <p class="text-muted small mb-0">Multi-Company Legal Entities & Operational Scope for <strong>Vishal Enterprise</strong></p>
    </div>
    @if(auth()->user()->isHoAdmin())
        <button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
            <i class="bi bi-plus-lg"></i>
            <span>Register Company</span>
        </button>
    @endif
</div>

<!-- Summary KPI Tiles -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Legal Entities</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $companies->count() }}</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #EEF2FF; color: #4F46E5;">
                    <i class="bi bi-buildings fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Active Corporations</div>
                    <div class="fs-4 fw-bold text-success mt-1">
                        {{ $companies->where('status', 'Active')->count() }}
                    </div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #ECFDF3; color: #12B76A;">
                    <i class="bi bi-shield-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Multi-Tenant Governance</div>
                    <div class="fs-6 fw-bold text-dark mt-1 text-truncate" style="max-width: 200px;">HO Admin Clearance</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #F0FDF9; color: #0D9488;">
                    <i class="bi bi-shield-shaded fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card card-enterprise bg-white">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark" style="font-size: 14px;">Registered Legal Entities</span>
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">{{ $companies->count() }} Entities</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-enterprise mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 100px;">Code</th>
                        <th>Company Legal Name</th>
                        <th>CIN / GSTIN / PAN</th>
                        <th>Contact / Phone</th>
                        <th>Operational Units</th>
                        <th style="width: 120px;">Status</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <span class="badge bg-dark code-font px-2 py-1" style="font-size: 11px;">{{ $company->code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $company->name }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 260px;">{{ $company->address ?? 'No corporate address filed' }}</div>
                            </td>
                            <td class="small">
                                <div class="code-font text-dark"><span class="text-muted">CIN:</span> {{ $company->cin_number ?? '—' }}</div>
                                <div class="code-font text-muted"><span class="text-muted">GST:</span> {{ $company->gstin ?? '—' }}</div>
                            </td>
                            <td class="small">
                                <div class="text-dark">{{ $company->email ?? '—' }}</div>
                                <div class="text-muted">{{ $company->phone ?? '—' }}</div>
                            </td>
                            <td class="small">
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-light text-secondary border">{{ $company->locations_count }} Locations</span>
                                    <span class="badge bg-light text-secondary border">{{ $company->departments_count }} Depts</span>
                                    <span class="badge bg-light text-secondary border">{{ $company->contractors_count }} Contractors</span>
                                </div>
                            </td>
                            <td>
                                @if($company->status === 'Active')
                                    <span class="status-pill status-pill-success"><span class="dot"></span>Active</span>
                                @else
                                    <span class="status-pill status-pill-danger"><span class="dot"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(auth()->user()->isHoAdmin())
                                    <button class="btn btn-outline-secondary btn-sm py-1 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#editCompanyModal{{ $company->id }}" title="Edit Company">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </button>
                                @else
                                    <span class="badge bg-light text-muted border">Read-Only</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        @if(auth()->user()->isHoAdmin())
                        <div class="modal fade" id="editCompanyModal{{ $company->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="{{ route('companies.update', $company) }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header bg-light border-bottom p-3">
                                        <h6 class="modal-title fw-bold text-dark mb-0">Edit Company: {{ $company->name }}</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Company Name</label>
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $company->name }}" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Company Code</label>
                                                <input type="text" name="code" class="form-control form-control-sm" value="{{ $company->code }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="Active" {{ $company->status === 'Active' ? 'selected' : '' }}>Active</option>
                                                    <option value="Inactive" {{ $company->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">CIN Number</label>
                                                <input type="text" name="cin_number" class="form-control form-control-sm" value="{{ $company->cin_number }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">GSTIN</label>
                                                <input type="text" name="gstin" class="form-control form-control-sm" value="{{ $company->gstin }}">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Official Email</label>
                                            <input type="email" name="email" class="form-control form-control-sm" value="{{ $company->email }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Registered Address</label>
                                            <textarea name="address" class="form-control form-control-sm" rows="3">{{ $company->address }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-top p-3">
                                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-brand btn-sm px-3">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-buildings fs-2 text-secondary opacity-50 d-block mb-2"></i>
                                    <div class="fw-semibold">No companies registered.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
@if(auth()->user()->isHoAdmin())
<div class="modal fade" id="createCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('companies.store') }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            @csrf
            <div class="modal-header bg-light border-bottom p-3">
                <h6 class="modal-title fw-bold text-dark mb-0">Register New Legal Entity</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Company Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Vishal Enterprise" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Company Code</label>
                        <input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. VE" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">CIN Number</label>
                        <input type="text" name="cin_number" class="form-control form-control-sm" placeholder="Corporate Identity No.">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">GSTIN</label>
                        <input type="text" name="gstin" class="form-control form-control-sm" placeholder="GST Identification No.">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Official Email</label>
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="compliance@enterprise.com">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Registered Address</label>
                    <textarea name="address" class="form-control form-control-sm" rows="3" placeholder="Full registered legal corporate address"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand btn-sm px-3">Create Company</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
