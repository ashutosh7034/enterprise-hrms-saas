@extends('layouts.app')

@section('title', "Asset: {$asset->asset_code}")

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 12.5px;">
                <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Asset Inventory</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $asset->asset_code }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0" style="font-weight: 700; color: #0F172A;">{{ $asset->name }}</h4>
            <span class="badge bg-light text-dark border code-font">{{ $asset->asset_code }}</span>
            @if($asset->status === 'Available')
                <span class="status-pill status-pill-success"><span class="dot"></span> Available</span>
            @elseif($asset->status === 'Allocated')
                <span class="status-pill status-pill-info"><span class="dot"></span> Allocated</span>
            @elseif($asset->status === 'Damaged')
                <span class="status-pill status-pill-danger"><span class="dot"></span> Damaged</span>
            @elseif($asset->status === 'Lost')
                <span class="status-pill status-pill-danger"><span class="dot"></span> Lost</span>
            @else
                <span class="status-pill status-pill-warning"><span class="dot"></span> {{ $asset->status }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Inventory
        </a>
        <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-brand btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Asset
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Specifications -->
    <div class="col-md-7">
        <div class="card card-custom h-100">
            <div class="card-header bg-white" style="font-weight: 600;">Asset Specifications</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 35%;">Category</td>
                        <td style="font-weight: 600;">{{ $asset->category->name }} (Code: {{ $asset->category->code }})</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tag / Barcode</td>
                        <td class="code-font">{{ $asset->asset_tag ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Serial Number</td>
                        <td class="code-font">{{ $asset->serial_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Location / Site Base</td>
                        <td>{{ $asset->location?->name ?? 'Head Office' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Returnable Asset?</td>
                        <td>
                            @if($asset->is_returnable)
                                <span class="badge bg-success-subtle text-success border">Yes (Mandatory return at exit)</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Consumable / Non-returnable</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Physical Condition</td>
                        <td>{{ $asset->condition }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Remarks / Specs</td>
                        <td>{{ $asset->remarks ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Depreciation & Costing Widget -->
    <div class="col-md-5">
        <div class="card card-custom h-100">
            <div class="card-header bg-white" style="font-weight: 600;">
                <i class="bi bi-calculator me-1"></i> Valuation & Depreciation Engine
            </div>
            <div class="card-body">
                <div class="p-3 rounded mb-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" style="font-size: 12px;">Original Purchase Cost</span>
                        <span class="code-font fw-bold" style="font-size: 14px;">₹{{ number_format($depreciation['purchase_cost'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" style="font-size: 12px;">Purchase Date</span>
                        <span class="code-font">{{ $asset->purchase_date ? $asset->purchase_date->format('d M Y') : 'Not Recorded' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" style="font-size: 12px;">Asset Operational Age</span>
                        <span class="code-font">{{ $depreciation['age_years'] }} years</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" style="font-size: 12px;">Accumulated SLM Depreciation</span>
                        <span class="code-font text-danger">-₹{{ number_format($depreciation['accumulated_depreciation'], 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong style="font-size: 13px;">Current Book Value (SLM)</strong>
                        <strong class="code-font text-success" style="font-size: 15px;">₹{{ number_format($depreciation['depreciated_value'], 2) }}</strong>
                    </div>
                </div>
                <div class="form-text" style="font-size: 11.5px; color: #64748B;">
                    Valuation guidelines are applied when assessing employee financial liability upon loss or negligent damage.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Allocation History -->
<div class="card card-custom mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span style="font-weight: 600;">Issuance & Allocation Audit History</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Assigned Employee</th>
                    <th>Issue Date</th>
                    <th>Condition at Issue</th>
                    <th>Return Date</th>
                    <th>Condition at Return</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asset->allocations as $alloc)
                    <tr>
                        <td>
                            <a href="{{ route('employees.show', $alloc->employee_id) }}" class="fw-bold text-decoration-none">
                                {{ $alloc->employee->full_name }}
                            </a>
                            <div class="code-font text-muted" style="font-size: 11px;">{{ $alloc->employee->employee_code }}</div>
                        </td>
                        <td class="code-font">{{ $alloc->allocated_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $alloc->condition_at_issue }}</span></td>
                        <td class="code-font">{{ $alloc->return_date ? $alloc->return_date->format('d M Y') : 'Active' }}</td>
                        <td>
                            @if($alloc->condition_at_return)
                                <span class="badge bg-light text-dark border">{{ $alloc->condition_at_return }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($alloc->status === 'Active')
                                <span class="status-pill status-pill-success"><span class="dot"></span> In Possession</span>
                            @else
                                <span class="status-pill status-pill-info"><span class="dot"></span> {{ $alloc->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Asset has never been allocated.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

