@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 12px;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
        <li class="breadcrumb-item text-muted">Masters</li>
        <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Department Master</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Department Master</h4>
        <p class="text-muted small mb-0">Operational & Administrative Units for <strong>{{ $activeCompany?->name }}</strong></p>
    </div>
    <button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
        <i class="bi bi-plus-lg"></i>
        <span>Add Department</span>
    </button>
</div>

<!-- Summary KPI Tiles -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Total Units</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $departments->total() }}</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #EEF2FF; color: #4F46E5;">
                    <i class="bi bi-diagram-3 fs-5"></i>
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
                        {{ $departments->where('status', 'Active')->count() }}
                    </div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #ECFDF3; color: #12B76A;">
                    <i class="bi bi-check2-circle fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Operational Tenancy</div>
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
            <span class="fw-bold text-dark" style="font-size: 14px;">Departments ({{ $activeCompany?->name }})</span>
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">{{ $departments->total() }} Units</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-enterprise mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 120px;">Code</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th style="width: 180px;">Linked Designations</th>
                        <th style="width: 120px;">Status</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
                        <tr>
                            <td>
                                <span class="badge bg-dark code-font px-2 py-1" style="font-size: 11px;">{{ $dept->code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $dept->name }}</div>
                            </td>
                            <td class="text-muted small">{{ $dept->description ?? '—' }}</td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11.5px;">
                                    <i class="bi bi-diagram-2 me-1"></i>{{ $dept->designations_count }} Designations
                                </span>
                            </td>
                            <td>
                                @if($dept->status === 'Active')
                                    <span class="status-pill status-pill-success"><span class="dot"></span>Active</span>
                                @else
                                    <span class="status-pill status-pill-danger"><span class="dot"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm py-1 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#editDepartmentModal{{ $dept->id }}" title="Edit Department">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('departments.destroy', $dept) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this department?');">
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
                        <div class="modal fade" id="editDepartmentModal{{ $dept->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="{{ route('departments.update', $dept) }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header bg-light border-bottom p-3">
                                        <h6 class="modal-title fw-bold text-dark mb-0">Edit Department: {{ $dept->name }}</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Department Name</label>
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $dept->name }}" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Department Code</label>
                                                <input type="text" name="code" class="form-control form-control-sm" value="{{ $dept->code }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="Active" {{ $dept->status === 'Active' ? 'selected' : '' }}>Active</option>
                                                    <option value="Inactive" {{ $dept->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Description</label>
                                            <textarea name="description" class="form-control form-control-sm" rows="3">{{ $dept->description }}</textarea>
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-diagram-3 fs-2 text-secondary opacity-50 d-block mb-2"></i>
                                    <div class="fw-semibold">No departments registered for this company.</div>
                                    <p class="small text-muted mb-3">Get started by creating your first administrative or operational unit.</p>
                                    <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
                                        <i class="bi bi-plus-lg me-1"></i> Add Department
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())
        <div class="p-3 border-top">
            {{ $departments->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('departments.store') }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            @csrf
            <div class="modal-header bg-light border-bottom p-3">
                <h6 class="modal-title fw-bold text-dark mb-0">Add New Department</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start p-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Department Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Mechanical Engineering" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Department Code</label>
                        <input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. MECH" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="Operational responsibilities..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand btn-sm px-3">Create Department</button>
            </div>
        </form>
    </div>
</div>
@endsection
