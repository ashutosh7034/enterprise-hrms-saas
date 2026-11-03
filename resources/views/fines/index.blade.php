@extends('layouts.app')

@section('title', 'Fine Master Policies')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1" style="font-weight: 700; color: #0F172A;">Fine Master Policies</h4>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Define standardized disciplinary and asset damage fine tariffs and schedules.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('damage-assessments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-receipt me-1"></i> Damage & Recovery Ledger
        </a>
        <a href="{{ route('fine-masters.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Create Fine Policy
        </a>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span style="font-weight: 600;">Standard Fine Tariffs ({{ $fines->total() }})</span>
        <span class="badge bg-light text-dark border code-font">Company: {{ $activeCompany->code }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Fine Code</th>
                    <th>Policy Name</th>
                    <th>Category</th>
                    <th>Asset Scope</th>
                    <th>Default Amount</th>
                    <th>Effective Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fines as $fine)
                    <tr>
                        <td class="code-font fw-bold">{{ $fine->fine_code }}</td>
                        <td style="font-weight: 600;">{{ $fine->fine_name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $fine->category }}</span></td>
                        <td>{{ $fine->assetCategory?->name ?? 'All Asset Categories' }}</td>
                        <td class="code-font fw-bold">₹{{ number_format($fine->default_amount, 2) }}</td>
                        <td class="code-font">{{ $fine->effective_date->format('d M Y') }}</td>
                        <td>
                            @if($fine->status === 'Active')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Active</span>
                            @else
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('fine-masters.edit', $fine->id) }}" class="btn btn-outline-secondary btn-sm" title="Edit Policy">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-calculator display-6 d-block mb-2 text-secondary"></i>
                            <div style="font-size: 15px; font-weight: 500;">No fine policies configured for {{ $activeCompany->code }}</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($fines->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $fines->links() }}
        </div>
    @endif
</div>
@endsection

