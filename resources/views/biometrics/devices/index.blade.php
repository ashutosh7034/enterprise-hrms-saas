@extends('layouts.app')

@section('title', 'Biometric Devices — Enterprise HRMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">Biometric Device Registry</h1>
        <p class="text-muted mb-0" style="font-size: 13.5px;">Manage hardware biometric terminals, monitor heartbeat status & ingestion synchronization.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('biometric.logs') }}" class="btn btn-outline-secondary">
            <i class="bi bi-list-columns-reverse me-1"></i> Raw Punch Logs
        </a>
        <a href="{{ route('biometric-devices.create') }}" class="btn btn-primary" style="background-color: var(--brand-accent); border-color: var(--brand-accent);">
            <i class="bi bi-plus-circle me-1"></i> Register New Device
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-primary">
            <div class="text-muted small text-uppercase fw-bold">Total Registered</div>
            <div class="h3 mb-0 fw-bold text-dark mt-1">{{ $devices->total() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-success">
            <div class="text-muted small text-uppercase fw-bold">Active Online</div>
            <div class="h3 mb-0 fw-bold text-success mt-1">{{ $devices->where('status', 'Active')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-warning">
            <div class="text-muted small text-uppercase fw-bold">Health Warnings</div>
            <div class="h3 mb-0 fw-bold text-warning mt-1">{{ $devices->where('health_status', 'Warning')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-info">
            <div class="text-muted small text-uppercase fw-bold">Ingestion Webhook</div>
            <div class="small fw-semibold text-truncate mt-1 text-primary">/api/biometric/webhook</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Device Details</th>
                    <th>Site / Location</th>
                    <th>Network & Protocol</th>
                    <th>Health & Sync Status</th>
                    <th>Last Activity</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $device->device_name }}</div>
                            <span class="badge bg-light text-secondary font-monospace" style="font-size: 11px;">{{ $device->device_identifier }}</span>
                            <div class="text-muted small">SN: {{ $device->serial_number }}</div>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><i class="bi bi-geo-alt me-1 text-muted"></i>{{ $device->location?->name ?? 'Unassigned' }}</div>
                            <small class="text-muted">{{ $device->device_type }}</small>
                        </td>
                        <td>
                            <div class="font-monospace text-dark">{{ $device->ip_address ?: 'Dynamic / Push' }}:{{ $device->port }}</div>
                            <span class="badge bg-light text-dark border" style="font-size: 10.5px;">{{ $device->protocol }}</span>
                        </td>
                        <td>
                            @if($device->status === 'Active')
                                <span class="status-pill status-pill-success mb-1 d-inline-block"><span class="dot"></span> Active</span>
                            @elseif($device->status === 'Shutdown')
                                <span class="status-pill status-pill-warning mb-1 d-inline-block"><span class="dot"></span> Shutdown</span>
                            @else
                                <span class="status-pill status-pill-danger mb-1 d-inline-block"><span class="dot"></span> Inactive</span>
                            @endif
                            <br>
                            <small class="badge {{ $device->health_status === 'Healthy' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}" style="font-size: 10px;">
                                {{ $device->health_status }}
                            </small>
                        </td>
                        <td>
                            <div>Sync: <span class="text-muted small">{{ $device->last_sync_at ? $device->last_sync_at->diffForHumans() : 'Never' }}</span></div>
                            <small class="text-muted">Heartbeat: {{ $device->last_heartbeat_at ? $device->last_heartbeat_at->format('d M H:i') : 'N/A' }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('biometric-devices.edit', $device->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-cpu d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                            No biometric devices registered.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($devices->hasPages())
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $devices->links() }}
        </div>
    @endif
</div>
@endsection

