@extends('layouts.app')

@section('title', 'Notification Delivery Logs')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Notification Delivery Logs</h4>
        <div class="text-muted" style="font-size: 13px;">Comprehensive historical audit trail of all outbound messages across all channels.</div>
    </div>
    <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.notifications.logs') }}" class="row g-2 align-items-center">
            <div class="col-md-2 col-sm-6">
                <select name="channel" class="form-select form-select-sm">
                    <option value="">All Channels</option>
                    @foreach(['in_app' => 'In-App', 'email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $k => $v)
                        <option value="{{ $k }}" {{ request('channel') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach(['delivered' => 'Delivered', 'dispatched' => 'Dispatched', 'failed' => 'Failed', 'queued' => 'Queued', 'skipped' => 'Skipped'] as $k => $v)
                        <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" placeholder="From Date">
            </div>
            <div class="col-md-3 col-sm-6">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" placeholder="To Date">
            </div>
            <div class="col-md-2 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1" style="background-color: #1E293B; border-color: #1E293B;">Filter</button>
                @if(request()->hasAny(['channel', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-secondary btn-sm" title="Clear">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th style="padding: 12px 16px;">Log ID</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Recipient</th>
                    <th>Employee / User</th>
                    <th>Provider</th>
                    <th>Attempt</th>
                    <th>Timestamp</th>
                    <th class="text-end" style="padding-right: 16px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="padding: 12px 16px;"><code>#{{ $log->id }}</code></td>
                        <td>
                            <span class="badge" style="background-color: #F1F5F9; color: #1E293B; border: 1px solid #CBD5E1;">{{ strtoupper($log->channel) }}</span>
                        </td>
                        <td>
                            @if(in_array($log->status, ['delivered', 'dispatched']))
                                <span class="badge" style="background-color: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;"><i class="bi bi-check me-1"></i> {{ ucfirst($log->status) }}</span>
                            @elseif($log->status === 'failed')
                                <span class="badge" style="background-color: #FEF2F2; color: #9F1239; border: 1px solid #FECDD3;" title="{{ $log->failure_reason }}"><i class="bi bi-x me-1"></i> Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($log->status) }}</span>
                            @endif
                        </td>
                        <td><code>{{ $log->recipient_address }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $log->employee?->full_name ?? $log->user?->name ?? 'System' }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $log->company?->code ?? 'All' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">{{ $log->provider_name }}</span>
                            @if($log->provider_message_id)
                                <div class="text-muted" style="font-size: 10px;">{{ substr($log->provider_message_id, 0, 16) }}...</div>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $log->attempt_number }}</span></td>
                        <td class="text-muted" style="font-size: 12px;">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                        <td class="text-end" style="padding-right: 16px;">
                            @if($log->status === 'failed')
                                <form method="POST" action="{{ route('admin.notifications.logs.retry', $log->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Retry Failed Delivery">
                                        <i class="bi bi-arrow-repeat"></i> Retry
                                    </button>
                                </form>
                            @else
                                <span class="text-muted" style="font-size: 11px;"><i class="bi bi-check-all text-success"></i> OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4 text-center text-muted">No delivery logs matching query.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-white p-3 border-0">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection

