@extends('layouts.app')

@section('title', 'Document Template Master')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Document Template Master</h4>
            <p class="text-muted small mb-0">Legal HR letters, onboarding forms, and statutory declarations with versioning</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Document Hub
            </a>
            <a href="{{ route('admin.documents.templates.create') }}" class="btn btn-teal btn-sm shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Create New Template
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="card card-custom p-3 border-0 shadow-sm mb-4">
        <form method="GET" action="{{ route('admin.documents.templates.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by template name, code, or type..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select form-select-sm">
                    <option value="">-- All Categories --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-teal btn-sm w-100">Filter</button>
                <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Templates Grid/Table -->
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body p-0">
            @if($templates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-code fs-1 text-muted d-block mb-2"></i>
                    <p class="mb-0 fw-semibold text-dark">No templates found matching query.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small">Code</th>
                                <th class="small">Template Name</th>
                                <th class="small">Category</th>
                                <th class="small text-center">Version</th>
                                <th class="small text-center">Approval</th>
                                <th class="small text-center">Salary Gate</th>
                                <th class="small">Status</th>
                                <th class="small text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $tmpl)
                                <tr>
                                    <td class="font-monospace small fw-semibold text-teal">{{ $tmpl->code }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark small">{{ $tmpl->name }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $tmpl->document_type }} &bull; {{ $tmpl->generated_documents_count }} documents issued</div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $tmpl->category }}</span></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary font-monospace">v{{ $tmpl->version }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($tmpl->requires_approval)
                                            <span class="badge bg-warning text-dark"><i class="bi bi-check2"></i> Required</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Direct</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($tmpl->is_sensitive_salary)
                                            <span class="badge bg-danger"><i class="bi bi-currency-rupee"></i> Sensitive</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $tmpl->status === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $tmpl->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.documents.templates.preview', $tmpl) }}" class="btn btn-light btn-sm text-secondary" title="Live Preview">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.documents.templates.edit', $tmpl) }}" class="btn btn-light btn-sm text-teal" title="Edit Template">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.documents.templates.duplicate', $tmpl) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-light btn-sm text-dark" title="Duplicate as Draft">
                                                    <i class="bi bi-copy"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($templates->hasPages())
                    <div class="p-3 border-top">
                        {{ $templates->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

