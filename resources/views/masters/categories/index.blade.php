@extends('layouts.app')

@section('title', 'Employee Categories')

@section('content')
<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size: 12px;">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a></li>
        <li class="breadcrumb-item text-muted">Masters</li>
        <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Employee Categories</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A; letter-spacing: -0.02em;">Employee Categories</h4>
        <p class="text-muted small mb-0">Employment Classifications & Notice Period Policies for <strong>{{ $activeCompany?->name }}</strong></p>
    </div>
    <button type="button" class="btn btn-brand btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <i class="bi bi-plus-lg"></i>
        <span>Add Category</span>
    </button>
</div>

<!-- Summary KPI Tiles -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Total Categories</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $categories->total() }}</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #EEF2FF; color: #4F46E5;">
                    <i class="bi bi-tags fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-enterprise p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Active Policies</div>
                    <div class="fs-4 fw-bold text-success mt-1">
                        {{ $categories->where('status', 'Active')->count() }}
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
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Company Tenancy</div>
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
            <span class="fw-bold text-dark" style="font-size: 14px;">Configured Categories ({{ $activeCompany?->name }})</span>
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">{{ $categories->total() }} Classes</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-enterprise mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 120px;">Code</th>
                        <th>Category Name</th>
                        <th style="width: 180px;">Notice Period</th>
                        <th>Description</th>
                        <th style="width: 120px;">Status</th>
                        <th class="text-end" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td>
                                <span class="badge bg-dark code-font px-2 py-1" style="font-size: 11px;">{{ $cat->code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $cat->name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11.5px;">
                                    <i class="bi bi-clock me-1"></i>{{ $cat->notice_period_days }} Days Notice
                                </span>
                            </td>
                            <td class="text-muted small">{{ $cat->description ?? '—' }}</td>
                            <td>
                                @if($cat->status === 'Active')
                                    <span class="status-pill status-pill-success"><span class="dot"></span>Active</span>
                                @else
                                    <span class="status-pill status-pill-danger"><span class="dot"></span>Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm py-1 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $cat->id }}" title="Edit Category">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('categories.destroy', $cat) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this category?');">
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
                        <div class="modal fade" id="editCategoryModal{{ $cat->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="{{ route('categories.update', $cat) }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header bg-light border-bottom p-3">
                                        <h6 class="modal-title fw-bold text-dark mb-0">Edit Category: {{ $cat->name }}</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start p-4">
                                        <div class="row g-2 mb-3">
                                            <div class="col-8">
                                                <label class="form-label small fw-semibold text-secondary">Category Name</label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $cat->name }}" required>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold text-secondary">Code</label>
                                                <input type="text" name="code" class="form-control form-control-sm" value="{{ $cat->code }}" required>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Notice Period (Days)</label>
                                                <input type="number" name="notice_period_days" class="form-control form-control-sm" value="{{ $cat->notice_period_days }}" min="0" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold text-secondary">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="Active" {{ $cat->status === 'Active' ? 'selected' : '' }}>Active</option>
                                                    <option value="Inactive" {{ $cat->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-secondary">Description</label>
                                            <textarea name="description" class="form-control form-control-sm" rows="3">{{ $cat->description }}</textarea>
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
                                    <i class="bi bi-tags fs-2 text-secondary opacity-50 d-block mb-2"></i>
                                    <div class="fw-semibold">No categories registered for this company.</div>
                                    <p class="small text-muted mb-3">Configure workforce classes, employment terms, and notice periods.</p>
                                    <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                        <i class="bi bi-plus-lg me-1"></i> Add Category
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($categories->hasPages())
        <div class="p-3 border-top">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('categories.store') }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            @csrf
            <div class="modal-header bg-light border-bottom p-3">
                <h6 class="modal-title fw-bold text-dark mb-0">Add Employee Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start p-4">
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label small fw-semibold text-secondary">Category Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Ground Staff" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold text-secondary">Code</label>
                        <input type="text" name="code" class="form-control form-control-sm" placeholder="e.g. CAT-GND" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary">Notice Period (Days)</label>
                        <input type="number" name="notice_period_days" class="form-control form-control-sm" value="30" min="0" required>
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
                    <textarea name="description" class="form-control form-control-sm" rows="3" placeholder="e.g. Site workers deployed on client locations"></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand btn-sm px-3">Create Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
