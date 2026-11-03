@extends('layouts.app')

@section('title', 'Salary Structures & Assignments — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Salary Structures & Assignments</h4>
            <p class="text-muted small mb-0">{{ $company->name }} ({{ $company->code }}) — Manage salary component rules and employee pay structures</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.structures.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> New Structure
            </a>
            <a href="{{ route('payroll.structures.assign') }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-person-plus me-1"></i> Assign to Employee
            </a>
        </div>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-structures-tab" data-bs-toggle="pill" data-bs-target="#pills-structures" type="button" role="tab">
                <i class="bi bi-diagram-3 me-1"></i> Salary Structures ({{ $structures->total() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-assignments-tab" data-bs-toggle="pill" data-bs-target="#pills-assignments" type="button" role="tab">
                <i class="bi bi-person-lines-fill me-1"></i> Employee Assignments ({{ $assignments->total() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <!-- Tab 1: Structures -->
        <div class="tab-pane fade show active" id="pills-structures" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3">Code</th>
                                <th>Name</th>
                                <th>Target OT Mode</th>
                                <th>Components</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($structures as $struct)
                                <tr>
                                    <td class="ps-3 fw-bold text-teal-700">{{ $struct->code }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $struct->name }}</div>
                                        <div class="text-muted small">{{ $struct->description ?? 'Standard company structure' }}</div>
                                    </td>
                                    <td>
                                        @if($struct->is_target_ot)
                                            <span class="badge bg-warning text-dark"><i class="bi bi-bullseye me-1"></i> Target OT (₹{{ number_format($struct->target_ot_amount, 2) }})</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Biometric OT</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">{{ $struct->components->count() }} Components</span>
                                    </td>
                                    <td>
                                        @if($struct->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-view-{{ $struct->id }}">
                                            <i class="bi bi-eye"></i> View Rules
                                        </button>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="modal-view-{{ $struct->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Structure Components — {{ $struct->name }} ({{ $struct->code }})</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <table class="table table-sm mb-0">
                                                    <thead class="bg-light">
                                                        <tr>
                                                            <th class="ps-3">Component</th>
                                                            <th>Type</th>
                                                            <th>Calculation</th>
                                                            <th>Default / Formula</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($struct->components as $item)
                                                            <tr>
                                                                <td class="ps-3 fw-semibold">{{ $item->component?->name }} ({{ $item->component?->code }})</td>
                                                                <td>
                                                                    <span class="badge {{ $item->component?->component_type === 'Earning' ? 'bg-success' : 'bg-danger' }}">
                                                                        {{ $item->component?->component_type }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $item->calculation_type }}</td>
                                                                <td>{{ $item->formula ?? '₹' . number_format($item->default_amount, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No salary structures configured yet. Click "New Structure" to create one.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($structures->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $structures->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Tab 2: Employee Assignments -->
        <div class="tab-pane fade" id="pills-assignments" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3">Employee</th>
                                <th>Department & Designation</th>
                                <th>Structure</th>
                                <th>Effective From</th>
                                <th>Gross Monthly</th>
                                <th>Annual CTC</th>
                                <th>Status</th>
                                <th class="pe-3 text-end">History</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assign)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">{{ $assign->employee?->full_name }}</div>
                                        <div class="text-muted small">{{ $assign->employee?->employee_code }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $assign->employee?->department?->name ?? 'N/A' }}</div>
                                        <div class="text-muted small">{{ $assign->employee?->designation?->name ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $assign->salaryStructure?->name }}</span>
                                    </td>
                                    <td>{{ $assign->effective_from_date->format('d M Y') }}</td>
                                    <td class="fw-bold text-slate-800">₹{{ number_format($assign->gross_monthly, 2) }}</td>
                                    <td class="fw-semibold">₹{{ number_format($assign->ctc_annual, 2) }}</td>
                                    <td>
                                        @if($assign->status === 'Approved')
                                            <span class="badge bg-success">Active / Approved</span>
                                        @elseif($assign->status === 'Superseded')
                                            <span class="badge bg-secondary">Superseded</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $assign->status }}</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end">
                                        <a href="{{ route('payroll.structures.revisions', $assign->employee_id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-clock-history"></i> Revisions
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No employee salary assignments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($assignments->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $assignments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

