@extends('layouts.app')

@section('title', 'My Documents')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">My Documents & Letters</h4>
            <p class="text-muted small mb-0">Official appointment letters, confirmation orders, certificates, and issued forms</p>
        </div>
        <a href="{{ route('documents.verify.form') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-shield-check me-1"></i> Verify a Document
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card card-custom p-3 border-0 shadow-sm mb-4">
        <form method="GET" action="{{ route('documents.my-documents') }}" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by document title or number..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select form-select-sm">
                    <option value="">-- All Categories --</option>
                    @foreach(['Employee Lifecycle', 'Employee Request', 'HR Compliance', 'Employee Forms', 'Leave & Attendance Forms', 'Payroll & Finance Forms', 'Administrative Forms'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-teal btn-sm w-100">Filter</button>
                <a href="{{ route('documents.my-documents') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Documents Grid -->
    @if($documents->isEmpty())
        <div class="card card-custom border-0 shadow-sm p-5 text-center">
            <i class="bi bi-folder-check fs-1 text-teal d-block mb-3"></i>
            <h5 class="fw-bold text-dark">No issued documents found</h5>
            <p class="text-muted small mb-0">When HR generates and authorizes an official letter or certificate for you, it will appear here for download.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($documents as $doc)
                <div class="col-lg-4 col-md-6">
                    <div class="card card-custom p-3 border-0 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark border font-monospace small">{{ $doc->document_number }}</span>
                                <span class="badge bg-success">Official Record</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">{{ $doc->title }}</h6>
                            <div class="text-muted small mb-3">{{ $doc->category }}</div>

                            <div class="p-2 rounded bg-light border small mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Issue Date:</span>
                                    <span class="fw-semibold text-dark">{{ $doc->issued_at ? $doc->issued_at->format('d M Y') : 'Recent' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Verification Code:</span>
                                    <span class="font-monospace text-teal fw-bold">{{ $doc->verification_code }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2 border-top">
                            <a href="{{ route('documents.ess.show', $doc) }}" class="btn btn-outline-secondary btn-sm flex-fill">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                            <a href="{{ route('documents.ess.download', $doc) }}" class="btn btn-teal btn-sm flex-fill">
                                <i class="bi bi-download me-1"></i> Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($documents->hasPages())
            <div class="mt-4">
                {{ $documents->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>
@endsection

