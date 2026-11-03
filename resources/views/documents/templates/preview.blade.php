@extends('layouts.app')

@section('title', 'Template Preview - ' . $template->name)

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-dark font-monospace">{{ $template->code }}</span>
                <span class="badge bg-secondary">v{{ $template->version }}</span>
                <span class="badge bg-light text-dark border">{{ $template->category }}</span>
            </div>
            <h4 class="fw-bold mb-0" style="color: #0F172A;">Preview: {{ $template->name }}</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.documents.templates.edit', $template) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Edit Template
            </a>
            <a href="{{ route('admin.documents.templates.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Catalog
            </a>
        </div>
    </div>

    <!-- Employee Switcher for Preview Context -->
    <div class="card card-custom p-3 border-0 shadow-sm mb-4">
        <form method="GET" action="{{ route('admin.documents.templates.preview', $template) }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="col-form-label small fw-semibold text-dark"><i class="bi bi-person-bounding-box text-teal me-1"></i>Previewing with Employee Context:</label>
            </div>
            <div class="col-md-5">
                <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @php
                        $allEmployees = \App\Models\Employee::where('company_id', $template->company_id ?? 1)->take(20)->get();
                    @endphp
                    @foreach($allEmployees as $emp)
                        <option value="{{ $emp->id }}" {{ ($employee?->id == $emp->id) ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }}) &bull; {{ $emp->designation?->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <span class="small text-muted">All placeholders dynamically bound for testing layout integrity.</span>
            </div>
        </form>
    </div>

    <!-- Rendered A4 Document Container -->
    <div class="d-flex justify-content-center">
        <div class="card card-custom border-0 shadow p-5" style="width: 100%; max-width: 840px; background: #ffffff; min-height: 1000px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            <div class="document-preview-viewport" style="color: #1e293b; font-size: 14px; line-height: 1.6;">
                {!! $previewHtml !!}
            </div>
        </div>
    </div>
</div>
@endsection

