@extends('layouts.app')

@section('title', 'Document History Archive')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Document History & Repository</h4>
            <p class="text-muted small mb-0">Searchable regulatory archive of all corporate letters, forms, and issued certificates</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Hub
            </a>
            <a href="{{ route('admin.documents.generate') }}" class="btn btn-teal btn-sm shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Generate New
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-custom p-3 border-0 shadow-sm mb-4">
        <form method="GET" action="{{ route('admin.documents.history') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by Doc No, Title, Employee Name/Code..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">-- All Categories --</option>
                    @foreach(['Employee Lifecycle', 'Employee Request', 'HR Compliance', 'Employee Forms', 'Leave & Attendance Forms', 'Payroll & Finance Forms', 'Administrative Forms'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    @foreach(['Issued', 'Pending Approval', 'Rejected', 'Cancelled', 'Generated'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-teal btn-sm w-100">Filter</button>
                <a href="{{ route('admin.documents.history') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Documents Table -->
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body p-0">
            @if($documents->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                    <p class="mb-0 fw-semibold text-dark">No documents found matching criteria.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small">Document No</th>
                                <th class="small">Document Title</th>
                                <th class="small">Recipient</th>
                                <th class="small">Category</th>
                                <th class="small">Generated Date</th>
                                <th class="small">Status</th>
                                <th class="small text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $doc)
                                <tr>
                                    <td class="font-monospace small fw-semibold text-dark">{{ $doc->document_number }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark small">{{ $doc->title }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $doc->document_type }}</div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">{{ $doc->employee?->full_name }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $doc->employee?->employee_code }} &bull; {{ $doc->employee?->department?->name }}</div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $doc->category }}</span></td>
                                    <td class="small text-muted">{{ $doc->created_at?->format('d M Y') }}</td>
                                    <td>
                                        @if($doc->status === 'Issued')
                                            <span class="badge bg-success">Issued</span>
                                        @elseif($doc->status === 'Pending Approval')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($doc->status === 'Cancelled')
                                            <span class="badge bg-secondary">Cancelled</span>
                                        @elseif($doc->status === 'Rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ $doc->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.documents.show', $doc) }}" class="btn btn-light btn-sm text-secondary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(in_array($doc->status, ['Issued', 'Generated']))
                                                <a href="{{ route('admin.documents.download', $doc) }}" class="btn btn-light btn-sm text-teal" title="Download PDF">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($documents->hasPages())
                    <div class="p-3 border-top">
                        {{ $documents->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

