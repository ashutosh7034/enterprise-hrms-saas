@extends('layouts.app')

@section('title', 'Sandwich Leave Policies')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1 text-slate-900 font-bold">Sandwich Leave Policies</h1>
        <p class="text-muted small mb-0">Configure whether intervening weekly-offs and holidays are counted toward leave deduction.</p>
    </div>
    <a href="{{ route('leaves.sandwich-policies.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-circle me-1"></i> New Sandwich Policy
    </a>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Policy Name</th>
                    <th>Leave Type</th>
                    <th>Scope Overrides</th>
                    <th>Intervening Weekly-Offs</th>
                    <th>Intervening Holidays</th>
                    <th>Effective Dates</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $policy)
                    <tr>
                        <td>
                            <span class="badge bg-dark text-white rounded-pill px-2">P{{ $policy->priority }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-slate-900">{{ $policy->policy_name }}</div>
                        </td>
                        <td>
                            @if($policy->leaveType)
                                <span class="badge bg-light text-dark border">{{ $policy->leaveType->name }}</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">All Leave Types</span>
                            @endif
                        </td>
                        <td>
                            @if($policy->location)
                                <span class="badge bg-primary-subtle text-primary">{{ $policy->location->name }}</span>
                            @elseif($policy->department)
                                <span class="badge bg-info-subtle text-info">{{ $policy->department->name }}</span>
                            @else
                                <span class="text-muted small">Company Default</span>
                            @endif
                        </td>
                        <td>
                            @if($policy->include_intervening_weekly_offs)
                                <span class="status-pill status-pill-warning"><span class="dot"></span>Included</span>
                            @else
                                <span class="text-muted small">Excluded</span>
                            @endif
                        </td>
                        <td>
                            @if($policy->include_intervening_holidays)
                                <span class="status-pill status-pill-warning"><span class="dot"></span>Included</span>
                            @else
                                <span class="text-muted small">Excluded</span>
                            @endif
                        </td>
                        <td>
                            <span class="small">{{ $policy->effective_from->format('d M Y') }}</span>
                            @if($policy->effective_to)
                                <span class="text-muted small">to {{ $policy->effective_to->format('d M Y') }}</span>
                            @else
                                <span class="badge bg-success-subtle text-success small">Ongoing</span>
                            @endif
                        </td>
                        <td>
                            @if($policy->is_enabled)
                                <span class="status-pill status-pill-success"><span class="dot"></span>Enabled</span>
                            @else
                                <span class="status-pill status-pill-danger"><span class="dot"></span>Disabled</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('leaves.sandwich-policies.edit', $policy->id) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('leaves.sandwich-policies.destroy', $policy->id) }}" method="POST" onsubmit="return confirm('Delete this sandwich policy?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-sliders fs-3 d-block mb-2"></i>
                            No sandwich policies configured for this company. Click "New Sandwich Policy" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

