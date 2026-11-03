@extends('layouts.app')

@section('title', 'Asset Inventory')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Asset Master & Inventory</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Track company-owned tools, electronics, uniforms, and security assets with allocation & return lifecycles.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('damage-assessments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-receipt me-1"></i> Damage & Fine Recovery
        </a>
        <a href="{{ route('assets.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Register New Asset
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Total Assets</div>
                    <div style="font-size: 24px; font-weight: 700; color: #0F172A;">{{ $totalCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #F1F5F9; color: #475569; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-box-seam fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Currently Allocated</div>
                    <div style="font-size: 24px; font-weight: 700; color: #0D9488;">{{ $allocatedCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #CCFBF1; color: #0F766E; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-person-check fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">In Stock / Available</div>
                    <div style="font-size: 24px; font-weight: 700; color: #16A34A;">{{ $availableCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #DCFCE7; color: #15803D; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-check2-circle fs-5"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted" style="font-size: 12px; font-weight: 500;">Damaged / Lost</div>
                    <div style="font-size: 24px; font-weight: 700; color: #DC2626;">{{ $damagedCount }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 8px; background: #FEE2E2; color: #B91C1C; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-exclamation-octagon fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('assets.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by asset code, tag, serial, name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="asset_category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('asset_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Available" {{ request('status') === 'Available' ? 'selected' : '' }}>Available</option>
                    <option value="Allocated" {{ request('status') === 'Allocated' ? 'selected' : '' }}>Allocated</option>
                    <option value="Returned" {{ request('status') === 'Returned' ? 'selected' : '' }}>Returned</option>
                    <option value="Damaged" {{ request('status') === 'Damaged' ? 'selected' : '' }}>Damaged</option>
                    <option value="Lost" {{ request('status') === 'Lost' ? 'selected' : '' }}>Lost</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="location_id" class="form-select form-select-sm">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-brand btn-sm flex-fill">Filter</button>
                @if(request()->anyFilled(['search', 'asset_category_id', 'status', 'location_id']))
                    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Asset Table -->
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span style="font-weight: 600;">Assets Inventory ({{ $assets->total() }})</span>
        <span class="badge bg-light text-dark border code-font">Entity: {{ $activeCompany->code }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Asset Details</th>
                    <th>Category</th>
                    <th>Tag & Serial #</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Location</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $asset)
                    <tr>
                        <td>
                            <a href="{{ route('assets.show', $asset->id) }}" class="fw-bold text-decoration-none" style="color: #0F172A;">
                                {{ $asset->name }}
                            </a>
                            <div class="code-font text-muted" style="font-size: 11.5px;">{{ $asset->asset_code }}</div>
                        </td>
                        <td>{{ $asset->category->name }}</td>
                        <td>
                            <div class="code-font" style="font-size: 12px;">Tag: {{ $asset->asset_tag ?? '—' }}</div>
                            <div class="code-font text-muted" style="font-size: 11px;">SN: {{ $asset->serial_number ?? '—' }}</div>
                        </td>
                        <td class="code-font">₹{{ number_format($asset->purchase_cost, 2) }}</td>
                        <td>
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
                        </td>
                        <td>
                            @if($asset->activeAllocation)
                                <a href="{{ route('employees.show', $asset->activeAllocation->employee_id) }}" class="text-decoration-none fw-bold" style="font-size: 12.5px;">
                                    {{ $asset->activeAllocation->employee->full_name }}
                                </a>
                                <div class="code-font text-muted" style="font-size: 11px;">
                                    {{ $asset->activeAllocation->employee->employee_code }} • Since {{ $asset->activeAllocation->allocated_date->format('d M') }}
                                </div>
                            @else
                                <span class="text-muted" style="font-size: 12px;">Unallocated</span>
                            @endif
                        </td>
                        <td>{{ $asset->location?->name ?? 'Head Office' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-secondary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($asset->status === 'Available')
                                    <button type="button" class="btn btn-outline-primary" title="Allocate to Employee" onclick="openAllocateModal({{ $asset->id }}, '{{ $asset->asset_code }}')">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                @endif
                                <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-secondary" title="Edit Asset">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-box-seam display-6 d-block mb-2 text-secondary"></i>
                            <div style="font-size: 15px; font-weight: 500;">No assets registered matching criteria</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assets->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $assets->links() }}
        </div>
    @endif
</div>

<!-- Modal: Allocate Asset -->
<div class="modal fade" id="allocateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="allocateForm">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Allocate Asset <span id="allocAssetCode" class="code-font"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Recipient Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select form-select-sm" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->employee_code }} — {{ $emp->full_name }} ({{ $emp->department?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Allocation Date <span class="text-danger">*</span></label>
                        <input type="date" name="allocated_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Condition at Issue <span class="text-danger">*</span></label>
                        <select name="condition_at_issue" class="form-select form-select-sm" required>
                            <option value="Brand New">Brand New</option>
                            <option value="Good" selected>Good / Fully Operational</option>
                            <option value="Fair">Fair / Minor Scratches</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 500;">Allocation Notes / Instructions</label>
                        <textarea name="remarks" rows="2" class="form-control form-control-sm" placeholder="Notes on accessories handed over..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand btn-sm">Confirm Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAllocateModal(assetId, assetCode) {
        const form = document.getElementById('allocateForm');
        form.action = '/assets/' + assetId + '/allocate';
        document.getElementById('allocAssetCode').innerText = '(' + assetCode + ')';
        const modal = new bootstrap.Modal(document.getElementById('allocateModal'));
        modal.show();
    }
</script>
@endsection

