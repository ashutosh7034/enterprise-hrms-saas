@extends('layouts.app')

@section('title', 'Plant / Site Shutdowns — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Plant & Site Shutdown Management</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Declare planned maintenance, emergency stoppages & power outages with automated attendance safeguards.</p>
    </div>
    <div>
        <a href="{{ route('plant-shutdowns.create') }}" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">
            <i class="bi bi-plus-circle me-1"></i> Request Plant Shutdown
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('plant-shutdowns.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['Pending', 'Approved', 'Cancelled', 'Completed'] as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <a href="{{ route('plant-shutdowns.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Shutdown Code & Title</th>
                    <th>Type & Category</th>
                    <th>Date Range</th>
                    <th>Target Site / Scope</th>
                    <th>Compensation Policy</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shutdowns as $shutdown)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $shutdown->title }}</div>
                            <span class="badge bg-light text-secondary font-monospace" style="font-size: 11px;">{{ $shutdown->shutdown_code }}</span>
                            <div class="text-muted small">Req: {{ $shutdown->requester?->name }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $shutdown->shutdown_type === 'Emergency' ? 'bg-danger text-white' : ($shutdown->shutdown_type === 'Power Failure' ? 'bg-warning text-dark' : 'bg-primary text-white') }}" style="font-size: 11px;">
                                {{ $shutdown->shutdown_type }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><i class="bi bi-calendar-range me-1 text-muted"></i>{{ $shutdown->start_date->format('d M Y') }} to {{ $shutdown->end_date->format('d M Y') }}</div>
                            <small class="text-muted">{{ $shutdown->start_date->diffInDays($shutdown->end_date) + 1 }} day(s)</small>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><i class="bi bi-geo-alt me-1 text-muted"></i>{{ $shutdown->location?->name }}</div>
                            @if($shutdown->department)
                                <small class="text-muted">Dept: {{ $shutdown->department->name }}</small><br>
                            @endif
                            @if($shutdown->category)
                                <small class="text-muted">Category: {{ $shutdown->category->name }}</small>
                            @endif
                            @if(!$shutdown->department && !$shutdown->category)
                                <small class="text-muted">All Departments & Categories</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $shutdown->compensation_rule }}</span>
                        </td>
                        <td>
                            @if($shutdown->status === 'Approved')
                                <span class="status-pill status-pill-success"><span class="dot"></span> Approved</span>
                            @elseif($shutdown->status === 'Pending')
                                <span class="status-pill status-pill-warning"><span class="dot"></span> Pending Approval</span>
                            @elseif($shutdown->status === 'Cancelled')
                                <span class="status-pill status-pill-danger"><span class="dot"></span> Cancelled</span>
                            @else
                                <span class="status-pill status-pill-info"><span class="dot"></span> {{ $shutdown->status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('plant-shutdowns.show', $shutdown->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> View & Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-slash-circle d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No plant shutdowns recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($shutdowns->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $shutdowns->links() }}
        </div>
    @endif
</div>
@endsection

