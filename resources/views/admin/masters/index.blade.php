@extends('layouts.app')

@section('title', 'Centralized Dropdown Master Management — Kanvtech HRMS')

@section('styles')
<style>
    .master-category-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 14px;
        border-radius: 8px;
        color: #334155;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.15s ease-in-out;
        margin-bottom: 2px;
    }
    .master-category-link:hover {
        background-color: #F1F5F9;
        color: #0F172A;
    }
    .master-category-link.active {
        background-color: #0D9488;
        color: #FFFFFF;
        font-weight: 600;
    }
    .master-category-link.active .badge {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #FFFFFF !important;
    }
    .master-table th {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748B;
        background-color: #F8FAFC;
    }

    /* Contained Master Category Custom Select & Dropdown */
    .custom-master-select {
        width: 100% !important;
        position: relative;
    }

    .custom-master-select .master-category-trigger {
        width: 100% !important;
        border-radius: 8px;
        padding: 7px 12px;
        background-color: #FFFFFF;
        border: 1px solid #CBD5E1;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    .custom-master-select .master-category-trigger:hover {
        border-color: #94A3B8;
        background-color: #F8FAFC;
    }

    .custom-master-select .master-category-trigger:focus,
    .custom-master-select .master-category-trigger[aria-expanded="true"] {
        border-color: #0D9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        outline: none;
    }

    .custom-master-select .master-category-menu {
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        margin-top: 4px;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
        z-index: 1065;
    }

    .custom-master-select .master-category-options-list {
        scrollbar-width: thin;
        scrollbar-color: #CBD5E1 transparent;
        max-height: 270px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .custom-master-select .master-category-options-list::-webkit-scrollbar {
        width: 5px;
    }
    .custom-master-select .master-category-options-list::-webkit-scrollbar-thumb {
        background-color: #CBD5E1;
        border-radius: 9999px;
    }

    .custom-master-select .master-category-option {
        border-bottom: 1px solid #F8FAFC;
        transition: background-color 0.12s ease;
        cursor: pointer;
        white-space: normal;
        text-align: left;
    }

    .custom-master-select .master-category-option:last-child {
        border-bottom: none;
    }

    .custom-master-select .master-category-option:hover {
        background-color: #F1F5F9;
    }

    .custom-master-select .master-category-option.active {
        background-color: #F0FDFA !important;
        color: #0F172A !important;
    }

    .custom-master-select .master-category-option.active .option-title {
        color: #0D9488 !important;
        font-weight: 600 !important;
    }

    .custom-master-select .master-category-option.active .option-icon {
        color: #0D9488 !important;
    }

    #createMasterModal .modal-dialog,
    #editMasterModal .modal-dialog {
        max-width: 540px;
    }

    #createMasterModal .modal-content,
    #createMasterModal .modal-body {
        overflow: visible !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumb -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-teal text-white rounded-pill px-2.5 py-1" style="font-size: 10.5px;">Master Data Engine</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 11px;">
                    <i class="bi bi-shield-check text-success me-1"></i>Tenant Isolated
                </span>
            </div>
            <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Dropdown Master Management</h4>
            <p class="text-muted small mb-0">Centralized governance for dynamic select options across all 16 Employee Onboarding sections.</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill shadow-xs" style="font-size: 12px;">
                <span class="text-secondary">Active Entity:</span> <strong>{{ $activeCompany->name }} ({{ $activeCompany->code }})</strong>
            </span>
            <a href="{{ route('employees.create') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person-plus me-1"></i> Open Onboarding Wizard
            </a>
            <button type="button" class="btn btn-sm btn-dark px-3" data-bs-toggle="modal" data-bs-target="#createMasterModal">
                <i class="bi bi-plus-lg me-1"></i> Add Master Value
            </button>
        </div>
    </div>

    <!-- Quick Navigation to Structural Masters -->
    <div class="card border mb-4 bg-light-subtle rounded-3 shadow-xs">
        <div class="card-body p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="small fw-semibold text-secondary">
                <i class="bi bi-diagram-3 me-1 text-teal"></i>Core Structural Masters:
            </span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('locations.index') }}" class="btn btn-xs btn-white border shadow-xs py-1 px-2.5" style="font-size: 11.5px;">
                    <i class="bi bi-geo-alt text-primary me-1"></i>Locations ({{ \App\Models\Location::where('company_id', $activeCompany->id)->count() }})
                </a>
                <a href="{{ route('departments.index') }}" class="btn btn-xs btn-white border shadow-xs py-1 px-2.5" style="font-size: 11.5px;">
                    <i class="bi bi-building text-teal me-1"></i>Departments ({{ \App\Models\Department::where('company_id', $activeCompany->id)->count() }})
                </a>
                <a href="{{ route('designations.index') }}" class="btn btn-xs btn-white border shadow-xs py-1 px-2.5" style="font-size: 11.5px;">
                    <i class="bi bi-award text-purple me-1"></i>Designations ({{ \App\Models\Designation::count() }})
                </a>
                <a href="{{ route('categories.index') }}" class="btn btn-xs btn-white border shadow-xs py-1 px-2.5" style="font-size: 11.5px;">
                    <i class="bi bi-tags text-orange me-1"></i>Cadre Categories
                </a>
                <a href="{{ route('contractors.index') }}" class="btn btn-xs btn-white border shadow-xs py-1 px-2.5" style="font-size: 11.5px;">
                    <i class="bi bi-briefcase text-success me-1"></i>Contractor Vendors
                </a>
                <a href="{{ route('admin.employee-codes.index') }}" class="btn btn-xs btn-white border shadow-xs py-1 px-2.5" style="font-size: 11.5px;">
                    <i class="bi bi-upc-scan text-dark me-1"></i>Code Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="row g-4">
        <!-- Left Column: Master Categories Nav List -->
        <div class="col-lg-4 col-xl-3">
            <div class="card border rounded-3 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark" style="font-size: 13.5px;">
                        <i class="bi bi-list-nested text-teal me-1"></i> Master Categories
                    </span>
                    <span class="badge bg-light text-secondary border">{{ count($allCategories) }}</span>
                </div>
                <div class="card-body p-2" style="max-height: calc(100vh - 300px); overflow-y: auto;">
                    @foreach($allCategories as $catKey => $catMeta)
                        <a href="{{ route('admin.masters.index', ['category' => $catKey]) }}" class="master-category-link {{ $selectedCategory === $catKey ? 'active' : '' }}">
                            <div class="d-flex align-items-center gap-2 text-truncate pe-1">
                                <i class="bi {{ $catMeta['icon'] }}" style="font-size: 14px;"></i>
                                <span class="text-truncate">{{ $catMeta['name'] }}</span>
                            </div>
                            <span class="badge bg-light text-muted border" style="font-size: 10px;">
                                {{ $categoryCounts[$catKey] ?? 0 }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column: Values Table & Management -->
        <div class="col-lg-8 col-xl-9">
            <div class="card border rounded-3 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $allCategories[$selectedCategory]['icon'] }} fs-5 text-teal"></i>
                                <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.01em;">{{ $allCategories[$selectedCategory]['name'] }}</h5>
                            </div>
                            <small class="text-muted">{{ $allCategories[$selectedCategory]['description'] }} • Used in <strong class="text-dark">{{ $allCategories[$selectedCategory]['section'] }}</strong></small>
                        </div>
                        <button type="button" class="btn btn-sm btn-teal text-white px-3" data-bs-toggle="modal" data-bs-target="#createMasterModal" style="background-color: #0D9488;">
                            <i class="bi bi-plus-circle me-1"></i> Add {{ Str::singular($allCategories[$selectedCategory]['name']) }}
                        </button>
                    </div>

                    <!-- Search & Filters -->
                    <form method="GET" action="{{ route('admin.masters.index') }}" class="row g-2 align-items-center mt-1">
                        <input type="hidden" name="category" value="{{ $selectedCategory }}">
                        <div class="col-md-6 col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="q" class="form-control border-start-0" placeholder="Search by name, code or description..." value="{{ $search }}">
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
                                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-6 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                            @if($search || $status !== 'all')
                                <a href="{{ route('admin.masters.index', ['category' => $selectedCategory]) }}" class="btn btn-sm btn-light border" title="Reset filters">
                                    <i class="bi bi-x"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table Content -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 master-table" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">#Order</th>
                                <th>Name / Label</th>
                                <th>Code / Identifier</th>
                                <th>Scope</th>
                                <th>Parent Dependency</th>
                                <th class="text-center" style="width: 110px;">Status</th>
                                <th class="text-end" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($masterItems as $item)
                                <tr>
                                    <td class="text-center code-font text-muted">{{ $item->display_order }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                        @if($item->description)
                                            <small class="text-muted d-block text-truncate" style="max-width: 250px;">{{ $item->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border code-font">{{ $item->code }}</span>
                                    </td>
                                    <td>
                                        @php $badge = $item->scope_badge; @endphp
                                        <span class="badge {{ $badge['class'] }} d-inline-flex align-items-center gap-1.5 px-2.5 py-1" style="font-size: 11px;">
                                            <i class="bi {{ $badge['icon'] }}"></i>
                                            <span>{{ $badge['label'] }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        @if($item->parent)
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-arrow-return-right me-1 text-teal"></i>{{ $item->parent->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($item->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#10B981;margin-right:4px;"></span>Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#EF4444;margin-right:4px;"></span>Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-outline-secondary btn-edit-master" 
                                                    data-bs-toggle="modal" data-bs-target="#editMasterModal"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->name }}"
                                                    data-code="{{ $item->code }}"
                                                    data-description="{{ $item->description }}"
                                                    data-order="{{ $item->display_order }}"
                                                    data-active="{{ $item->is_active ? '1' : '0' }}"
                                                    data-parent="{{ $item->parent_id }}"
                                                    data-scope="{{ $item->scope_type }}"
                                                    data-company-id="{{ $item->company_id }}"
                                                    data-location-id="{{ $item->location_id }}"
                                                    title="Edit Value">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- Toggle Status Button -->
                                            <form method="POST" action="{{ route('admin.masters.toggle-status', $item) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $item->is_active ? 'deactivate' : 'activate' }} this master value?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-{{ $item->is_active ? 'warning' : 'success' }}" title="{{ $item->is_active ? 'Deactivate value' : 'Activate value' }}">
                                                    <i class="bi {{ $item->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox display-6 d-block mb-2 text-secondary opacity-50"></i>
                                        <div class="fw-semibold">No master values found</div>
                                        <div class="small">Click <strong>Add Master Value</strong> to create options for this category.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($masterItems->hasPages())
                    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between">
                        <small class="text-muted">Showing {{ $masterItems->firstItem() }} to {{ $masterItems->lastItem() }} of {{ $masterItems->total() }} values</small>
                        {{ $masterItems->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- CREATE MASTER MODAL -->
<div class="modal fade" id="createMasterModal" tabindex="-1" aria-labelledby="createMasterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.masters.store') }}" class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            @csrf
            <div class="modal-header border-bottom py-3 px-4">
                <h6 class="modal-title fw-bold text-dark" id="createMasterModalLabel">
                    <i class="bi bi-plus-circle text-teal me-2"></i>Add Master Value
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Master Category <span class="text-danger">*</span></label>
                    <div class="dropdown custom-master-select" id="categoryDropdownContainer">
                        <input type="hidden" name="category" id="createMasterCategoryInput" value="{{ $selectedCategory }}" required>
                        
                        <button type="button" 
                                class="master-category-trigger text-start d-flex align-items-center justify-content-between" 
                                id="createMasterCategoryBtn" 
                                data-bs-toggle="dropdown" 
                                data-bs-auto-close="true" 
                                aria-expanded="false"
                                style="min-height: 38px;">
                            <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                <i class="bi {{ $allCategories[$selectedCategory]['icon'] ?? 'bi-tag' }} text-teal category-btn-icon" style="font-size: 15px;"></i>
                                <span class="selected-category-label text-truncate fw-medium" style="font-size: 13px; color: #1E293B;">
                                    {{ $allCategories[$selectedCategory]['name'] ?? 'Select Category' }}
                                </span>
                                <span class="badge bg-light text-secondary border selected-category-section small text-truncate" style="font-size: 10px; max-width: 150px;">
                                    {{ $allCategories[$selectedCategory]['section'] ?? '' }}
                                </span>
                            </div>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 12px; flex-shrink: 0;"></i>
                        </button>

                        <div class="dropdown-menu shadow-lg p-0 master-category-menu" aria-labelledby="createMasterCategoryBtn">
                            <!-- Search Filter Bar -->
                            <div class="p-2 border-bottom bg-light position-sticky top-0" style="z-index: 5;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 py-1"><i class="bi bi-search text-muted" style="font-size: 11px;"></i></span>
                                    <input type="text" class="form-control form-control-sm border-start-0 py-1" id="categorySearchInput" placeholder="Search master category..." autocomplete="off">
                                </div>
                            </div>

                            <!-- Scrollable Options List -->
                            <div class="master-category-options-list" id="categoryOptionsList">
                                @foreach($allCategories as $k => $c)
                                    <button type="button" 
                                            class="dropdown-item master-category-option d-flex align-items-center justify-content-between py-2 px-3 {{ $selectedCategory === $k ? 'active' : '' }}" 
                                            data-value="{{ $k }}" 
                                            data-name="{{ $c['name'] }}" 
                                            data-section="{{ $c['section'] }}" 
                                            data-icon="{{ $c['icon'] }}">
                                        <div class="d-flex align-items-center gap-2.5 overflow-hidden me-2">
                                            <i class="bi {{ $c['icon'] }} text-secondary option-icon" style="font-size: 14px;"></i>
                                            <div class="overflow-hidden">
                                                <div class="option-title text-truncate" style="font-size: 12.5px;">{{ $c['name'] }}</div>
                                                <div class="text-muted text-truncate" style="font-size: 10.5px;">Section: {{ $c['section'] }}</div>
                                            </div>
                                        </div>
                                        <i class="bi bi-check2 text-teal check-indicator {{ $selectedCategory === $k ? '' : 'd-none' }} fw-bold" style="font-size: 16px; flex-shrink: 0;"></i>
                                    </button>
                                @endforeach
                            </div>

                            <div id="noCategoriesFound" class="p-3 text-center text-muted small d-none">
                                <i class="bi bi-search me-1"></i>No matching categories
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Display Name / Label <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Non-Binary, Freelancer, Doctor of Philosophy" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Unique Code / Key</label>
                        <input type="text" name="code" class="form-control form-control-sm code-font" placeholder="e.g. NON_BINARY">
                        <small class="text-muted" style="font-size: 11px;">Auto-generated if left blank</small>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Display Order</label>
                        <input type="number" name="display_order" class="form-control form-control-sm" value="0" min="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Description / Administrative Note</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Optional notes regarding usage..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Parent Dependency</label>
                    <select name="parent_id" class="form-select form-select-sm">
                        <option value="">None (Independent)</option>
                        @foreach($parentOptions as $po)
                            <option value="{{ $po->id }}">{{ $po->name }} ({{ $po->category }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Scope Configuration Card -->
                <div class="p-3 rounded-3 border bg-light-subtle mb-2">
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-dark mb-1" style="font-size: 13px;">
                            <i class="bi bi-shield-lock text-teal me-1"></i>Scope <span class="text-danger">*</span>
                        </label>
                        <select name="scope" id="createMasterScope" class="form-select form-select-sm" required>
                            <option value="global">Global Enterprise Standard (All Companies)</option>
                            <option value="active_company" selected>Active Company Only ({{ $activeCompany->code }} - {{ $activeCompany->name }})</option>
                            @if($companies->count() > 1)
                                <option value="selected_company">Selected Company</option>
                            @endif
                            <option value="selected_branch">Selected Branch (Branch Scope)</option>
                        </select>
                        <div id="createScopeHelp" class="form-text small text-muted mt-1">
                            Available exclusively to {{ $activeCompany->name }}.
                        </div>
                    </div>

                    @if($companies->count() > 1)
                        <!-- Dynamic Target Company Selector (for multi-company tenants) -->
                        <div class="mb-2 d-none" id="createCompanyGroup">
                            <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                <i class="bi bi-building text-primary me-1"></i>Target Company <span class="text-danger">*</span>
                            </label>
                            <select name="company_id" id="createMasterCompany" class="form-select form-select-sm">
                                <option value="">-- Choose Company --</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}" data-code="{{ $comp->code }}">{{ $comp->name }} ({{ $comp->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <!-- Single Company Context: auto-bind company without showing redundant dropdown -->
                        <input type="hidden" name="company_id" id="createMasterCompany" value="{{ $companies->first()?->id }}" data-code="{{ $companies->first()?->code }}">
                        <div class="mb-2 d-none" id="createCompanyGroup">
                            <div class="small text-muted py-1">
                                <i class="bi bi-building text-primary me-1"></i>Company: <strong>{{ $companies->first()?->name }} ({{ $companies->first()?->code }})</strong>
                            </div>
                        </div>
                    @endif

                    <!-- Dynamic Target Branch Selector -->
                    <div class="d-none" id="createBranchGroup">
                        <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                            <i class="bi bi-geo-alt text-teal me-1"></i>Target Branch / Location <span class="text-danger">*</span>
                        </label>
                        <select name="location_id" id="createMasterBranch" class="form-select form-select-sm">
                            <option value="">-- Choose Branch --</option>
                        </select>
                        <div class="form-text small text-muted">Branches strictly filtered to authorized facilities.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-teal text-white px-4 fw-semibold" style="background-color: #0D9488;">
                    <i class="bi bi-save me-1"></i> Save Value
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MASTER MODAL -->
<div class="modal fade" id="editMasterModal" tabindex="-1" aria-labelledby="editMasterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" id="editMasterForm" class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom py-3 px-4">
                <h6 class="modal-title fw-bold text-dark" id="editMasterModalLabel">
                    <i class="bi bi-pencil text-teal me-2"></i>Edit Master Value
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Display Name / Label <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editMasterName" class="form-control form-control-sm" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Code / Key <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="editMasterCode" class="form-control form-control-sm code-font" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Display Order</label>
                        <input type="number" name="display_order" id="editMasterOrder" class="form-control form-control-sm" min="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 13px;">Description</label>
                    <textarea name="description" id="editMasterDesc" class="form-control form-control-sm" rows="2"></textarea>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Status <span class="text-danger">*</span></label>
                        <select name="is_active" id="editMasterActive" class="form-select form-select-sm">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Parent Dependency</label>
                        <select name="parent_id" id="editMasterParent" class="form-select form-select-sm">
                            <option value="">None (Independent)</option>
                            @foreach($parentOptions as $po)
                                <option value="{{ $po->id }}">{{ $po->name }} ({{ $po->category }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Scope Configuration Card -->
                <div class="p-3 rounded-3 border bg-light-subtle mb-2">
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-dark mb-1" style="font-size: 13px;">
                            <i class="bi bi-shield-lock text-teal me-1"></i>Scope <span class="text-danger">*</span>
                        </label>
                        <select name="scope" id="editMasterScope" class="form-select form-select-sm" required>
                            <option value="global">Global Enterprise Standard (All Companies)</option>
                            <option value="active_company">Active Company Only ({{ $activeCompany->code }} - {{ $activeCompany->name }})</option>
                            @if($companies->count() > 1)
                                <option value="selected_company">Selected Company</option>
                            @endif
                            <option value="selected_branch">Selected Branch (Branch Scope)</option>
                        </select>
                        <div id="editScopeHelp" class="form-text small text-muted mt-1"></div>
                    </div>

                    @if($companies->count() > 1)
                        <!-- Dynamic Target Company Selector (for multi-company tenants) -->
                        <div class="mb-2 d-none" id="editCompanyGroup">
                            <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                <i class="bi bi-building text-primary me-1"></i>Target Company <span class="text-danger">*</span>
                            </label>
                            <select name="company_id" id="editMasterCompany" class="form-select form-select-sm">
                                <option value="">-- Choose Company --</option>
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}" data-code="{{ $comp->code }}">{{ $comp->name }} ({{ $comp->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <!-- Single Company Context: auto-bind company without showing redundant dropdown -->
                        <input type="hidden" name="company_id" id="editMasterCompany" value="{{ $companies->first()?->id }}" data-code="{{ $companies->first()?->code }}">
                        <div class="mb-2 d-none" id="editCompanyGroup">
                            <div class="small text-muted py-1">
                                <i class="bi bi-building text-primary me-1"></i>Company: <strong>{{ $companies->first()?->name }} ({{ $companies->first()?->code }})</strong>
                            </div>
                        </div>
                    @endif

                    <!-- Dynamic Target Branch Selector -->
                    <div class="d-none" id="editBranchGroup">
                        <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                            <i class="bi bi-geo-alt text-teal me-1"></i>Target Branch / Location <span class="text-danger">*</span>
                        </label>
                        <select name="location_id" id="editMasterBranch" class="form-select form-select-sm">
                            <option value="">-- Choose Branch --</option>
                        </select>
                        <div class="form-text small text-muted">Branches strictly filtered to authorized facilities.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4 bg-light">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-teal text-white px-4 fw-semibold" style="background-color: #0D9488;">
                    <i class="bi bi-save me-1"></i> Update Value
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allBranches = @json($locations);
    const activeCompName = @json($activeCompany->name);
    const activeCompCode = @json($activeCompany->code);

    // Helper: Filter Branch select based on Company ID
    function filterBranches(companyId, branchSelect, preselectedId = null) {
        branchSelect.innerHTML = '<option value="">-- Choose Branch --</option>';
        if (!companyId) {
            return;
        }

        const filtered = allBranches.filter(b => String(b.company_id) === String(companyId));
        if (filtered.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.disabled = true;
            opt.textContent = 'No branches configured for this company';
            branchSelect.appendChild(opt);
            return;
        }

        filtered.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = `${b.name} (${b.code})`;
            if (preselectedId && String(b.id) === String(preselectedId)) {
                opt.selected = true;
            }
            branchSelect.appendChild(opt);
        });
    }

    const hasMultipleCompanies = {{ $companies->count() > 1 ? 'true' : 'false' }};
    const defaultCompanyId = {{ $companies->count() === 1 ? ($companies->first()?->id ?? 'null') : 'null' }};

    // Helper: Sync Scope selection with Company and Branch UI
    function syncScopeUI(scopeVal, companyGroup, branchGroup, companySelect, branchSelect, helpEl) {
        const isSelectElement = companySelect && companySelect.tagName === 'SELECT';

        if (scopeVal === 'global') {
            companyGroup.classList.add('d-none');
            branchGroup.classList.add('d-none');
            if (isSelectElement) companySelect.removeAttribute('required');
            branchSelect.removeAttribute('required');
            helpEl.innerHTML = '<span class="text-success fw-medium"><i class="bi bi-globe2 me-1"></i>Available enterprise-wide across all current and future entities.</span>';
        } else if (scopeVal === 'active_company') {
            companyGroup.classList.add('d-none');
            branchGroup.classList.add('d-none');
            if (isSelectElement) companySelect.removeAttribute('required');
            branchSelect.removeAttribute('required');
            helpEl.innerHTML = `<span class="text-primary fw-medium"><i class="bi bi-building-check me-1"></i>Available exclusively to active entity (${activeCompCode} — ${activeCompName}).</span>`;
        } else if (scopeVal === 'selected_company') {
            if (hasMultipleCompanies) {
                companyGroup.classList.remove('d-none');
                if (isSelectElement) companySelect.setAttribute('required', 'required');
            } else {
                companyGroup.classList.add('d-none');
            }
            branchGroup.classList.add('d-none');
            branchSelect.removeAttribute('required');
            helpEl.innerHTML = '<span class="text-teal fw-medium"><i class="bi bi-buildings me-1"></i>Scoped exclusively to the selected enterprise company.</span>';
        } else if (scopeVal === 'selected_branch') {
            if (hasMultipleCompanies) {
                companyGroup.classList.remove('d-none');
                if (isSelectElement) companySelect.setAttribute('required', 'required');
            } else {
                companyGroup.classList.add('d-none');
                if (defaultCompanyId) {
                    filterBranches(defaultCompanyId, branchSelect);
                }
            }
            branchGroup.classList.remove('d-none');
            branchSelect.setAttribute('required', 'required');
            helpEl.innerHTML = '<span class="text-info-emphasis fw-medium"><i class="bi bi-geo-alt-fill me-1"></i>Scoped to a specific operating branch / physical facility within the company.</span>';
        }
    }

    // CREATE MODAL WIRING
    const createScope = document.getElementById('createMasterScope');
    const createCompGroup = document.getElementById('createCompanyGroup');
    const createBranchGroup = document.getElementById('createBranchGroup');
    const createComp = document.getElementById('createMasterCompany');
    const createBranch = document.getElementById('createMasterBranch');
    const createHelp = document.getElementById('createScopeHelp');

    if (createScope) {
        createScope.addEventListener('change', function () {
            syncScopeUI(this.value, createCompGroup, createBranchGroup, createComp, createBranch, createHelp);
            if (this.value === 'selected_branch' && createComp.value) {
                filterBranches(createComp.value, createBranch);
            }
        });

        createComp.addEventListener('change', function () {
            filterBranches(this.value, createBranch);
        });

        // Initialize Create scope state
        syncScopeUI(createScope.value, createCompGroup, createBranchGroup, createComp, createBranch, createHelp);
    }

    // EDIT MODAL WIRING
    const editForm = document.getElementById('editMasterForm');
    const editName = document.getElementById('editMasterName');
    const editCode = document.getElementById('editMasterCode');
    const editOrder = document.getElementById('editMasterOrder');
    const editDesc = document.getElementById('editMasterDesc');
    const editActive = document.getElementById('editMasterActive');
    const editParent = document.getElementById('editMasterParent');

    const editScope = document.getElementById('editMasterScope');
    const editCompGroup = document.getElementById('editCompanyGroup');
    const editBranchGroup = document.getElementById('editBranchGroup');
    const editComp = document.getElementById('editMasterCompany');
    const editBranch = document.getElementById('editMasterBranch');
    const editHelp = document.getElementById('editScopeHelp');

    if (editScope) {
        editScope.addEventListener('change', function () {
            syncScopeUI(this.value, editCompGroup, editBranchGroup, editComp, editBranch, editHelp);
            if (this.value === 'selected_branch' && editComp.value) {
                filterBranches(editComp.value, editBranch);
            }
        });

        editComp.addEventListener('change', function () {
            filterBranches(this.value, editBranch);
        });
    }

    const editButtons = document.querySelectorAll('.btn-edit-master');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const code = this.getAttribute('data-code');
            const desc = this.getAttribute('data-description') || '';
            const order = this.getAttribute('data-order');
            const active = this.getAttribute('data-active');
            const parent = this.getAttribute('data-parent') || '';

            const scopeType = this.getAttribute('data-scope') || 'global';
            const companyId = this.getAttribute('data-company-id') || '';
            const locationId = this.getAttribute('data-location-id') || '';

            editForm.action = "{{ url('admin/masters') }}/" + id;
            editName.value = name;
            editCode.value = code;
            editDesc.value = desc;
            editOrder.value = order;
            editActive.value = active;
            editParent.value = parent;

            // Set scope
            let resolvedScope = scopeType;
            if (scopeType === 'company' || scopeType === 'selected_company') {
                resolvedScope = (companyId === '{{ $activeCompany->id }}') ? 'active_company' : 'selected_company';
            } else if (scopeType === 'branch' || scopeType === 'selected_branch') {
                resolvedScope = 'selected_branch';
            }

            if (editScope) {
                editScope.value = resolvedScope;
                if (companyId) {
                    editComp.value = companyId;
                    filterBranches(companyId, editBranch, locationId);
                } else {
                    editComp.value = '';
                    filterBranches(null, editBranch);
                }
                syncScopeUI(resolvedScope, editCompGroup, editBranchGroup, editComp, editBranch, editHelp);
            }
        });
    });

    // Custom Master Category Dropdown logic
    const categoryBtn = document.getElementById('createMasterCategoryBtn');
    const categoryInput = document.getElementById('createMasterCategoryInput');
    const categorySearch = document.getElementById('categorySearchInput');
    const categoryOptions = document.querySelectorAll('.master-category-option');
    const noCatFound = document.getElementById('noCategoriesFound');
    const labelSpan = categoryBtn ? categoryBtn.querySelector('.selected-category-label') : null;
    const sectionSpan = categoryBtn ? categoryBtn.querySelector('.selected-category-section') : null;
    const iconEl = categoryBtn ? categoryBtn.querySelector('.category-btn-icon') : null;

    if (categoryBtn && categoryInput) {
        categoryOptions.forEach(opt => {
            opt.addEventListener('click', function (e) {
                e.preventDefault();
                const val = this.getAttribute('data-value');
                const name = this.getAttribute('data-name');
                const section = this.getAttribute('data-section');
                const icon = this.getAttribute('data-icon');

                categoryInput.value = val;
                if (labelSpan) labelSpan.textContent = name;
                if (sectionSpan) sectionSpan.textContent = section;
                if (iconEl) {
                    iconEl.className = 'bi ' + icon + ' text-teal category-btn-icon';
                }

                categoryOptions.forEach(o => {
                    o.classList.remove('active');
                    const chk = o.querySelector('.check-indicator');
                    if (chk) chk.classList.add('d-none');
                });
                this.classList.add('active');
                const myChk = this.querySelector('.check-indicator');
                if (myChk) myChk.classList.remove('d-none');

                // Close dropdown
                const dropdownInstance = bootstrap.Dropdown.getInstance(categoryBtn) || new bootstrap.Dropdown(categoryBtn);
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            });
        });

        // Live Search in Category Dropdown
        if (categorySearch) {
            categorySearch.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();
                let matched = 0;
                categoryOptions.forEach(opt => {
                    const text = ((opt.getAttribute('data-name') || '') + ' ' + (opt.getAttribute('data-section') || '')).toLowerCase();
                    if (text.includes(term)) {
                        opt.classList.remove('d-none');
                        matched++;
                    } else {
                        opt.classList.add('d-none');
                    }
                });
                if (noCatFound) {
                    if (matched === 0) {
                        noCatFound.classList.remove('d-none');
                    } else {
                        noCatFound.classList.add('d-none');
                    }
                }
            });

            // Focus search input when dropdown opens
            categoryBtn.addEventListener('shown.bs.dropdown', function () {
                categorySearch.focus();
            });
        }
    }
});
</script>
@endpush

