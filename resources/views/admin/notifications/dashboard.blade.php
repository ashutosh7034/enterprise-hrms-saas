@extends('layouts.app')

@section('title', 'Notification Control Tower')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Notification Control Tower</h4>
        <div class="text-muted" style="font-size: 13px;">Enterprise Multi-Channel Telemetry: In-App, Email, SMS & WhatsApp Delivery Operations.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text me-1"></i> Delivery Logs
        </a>
        <a href="{{ route('admin.notifications.templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-code me-1"></i> Message Templates
        </a>
        <a href="{{ route('admin.notifications.channels.index') }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-sliders me-1"></i> Channel Settings
        </a>
    </div>
</div>

<!-- Key Telemetry Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm p-3" style="border-radius: 8px; border-left: 4px solid #10B981 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 12px; text-transform: uppercase;">Total Sent / Delivered</div>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #065F46;">{{ number_format($totalSent) }}</h3>
                </div>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: #ECFDF5;">
                    <i class="bi bi-check2-circle fs-4 text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm p-3" style="border-radius: 8px; border-left: 4px solid #F43F5E !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 12px; text-transform: uppercase;">Failed Deliveries</div>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #9F1239;">{{ number_format($totalFailed) }}</h3>
                </div>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: #FEF2F2;">
                    <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="card border-0 shadow-sm p-3" style="border-radius: 8px; border-left: 4px solid #3B82F6 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 12px; text-transform: uppercase;">Queued / In Flight</div>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #1E40AF;">{{ number_format($totalQueued) }}</h3>
                </div>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: #EFF6FF;">
                    <i class="bi bi-arrow-repeat fs-4 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Channel Status Overview -->
<div class="row g-3 mb-4">
    @foreach(['in_app' => 'In-App Notifications', 'email' => 'Email Engine', 'sms' => 'SMS Gateway', 'whatsapp' => 'WhatsApp Business'] as $cKey => $cLabel)
        @php
            $cRows = $byChannel->where('channel', $cKey);
            $cDelivered = $cRows->whereIn('status', ['delivered', 'dispatched'])->sum('count');
            $cFailed = $cRows->where('status', 'failed')->sum('count');
            $cTotal = $cDelivered + $cFailed;
            $rate = $cTotal > 0 ? round(($cDelivered / $cTotal) * 100, 1) : 100;
        @endphp
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm p-3 h-100" style="border-radius: 8px;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;">{{ $cLabel }}</span>
                    <span class="badge" style="background-color: #F1F5F9; color: #475569; font-size: 11px;">{{ strtoupper($cKey) }}</span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fs-4 fw-bold text-dark">{{ number_format($cDelivered) }}</span>
                    <span class="text-muted" style="font-size: 12px;">delivered</span>
                    @if($cFailed > 0)
                        <span class="badge bg-danger ms-auto">{{ $cFailed }} failed</span>
                    @endif
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $rate }}%; background-color: #0D9488;" aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between mt-1 text-muted" style="font-size: 11px;">
                    <span>Success Rate</span>
                    <span class="fw-semibold text-dark">{{ $rate }}%</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Failed Deliveries Table (Actionable) -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <span class="fw-bold text-danger" style="font-size: 14px;"><i class="bi bi-exclamation-octagon me-1"></i> Recent Failed Deliveries</span>
            <div class="text-muted" style="font-size: 12px;">Messages requiring attention, address review, or retry recovery.</div>
        </div>
        <span class="badge bg-danger">{{ $recentFailed->count() }} Alert(s)</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th style="padding: 10px 16px;">Channel</th>
                    <th>Recipient</th>
                    <th>Employee / User</th>
                    <th>Attempts</th>
                    <th>Failure Reason</th>
                    <th>Logged At</th>
                    <th class="text-end" style="padding-right: 16px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentFailed as $fail)
                    <tr>
                        <td style="padding: 12px 16px;">
                            <span class="badge" style="background-color: #F1F5F9; color: #1E293B;">{{ strtoupper($fail->channel) }}</span>
                        </td>
                        <td><code>{{ $fail->recipient_address }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $fail->employee?->full_name ?? $fail->user?->name ?? 'System' }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $fail->company?->code }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $fail->attempt_number }}</span></td>
                        <td class="text-danger" style="max-width: 250px;">
                            <span class="text-truncate d-inline-block w-100" title="{{ $fail->failure_reason }}">{{ $fail->failure_reason ?? 'Unknown Error' }}</span>
                        </td>
                        <td class="text-muted" style="font-size: 11.5px;">{{ $fail->created_at?->diffForHumans() }}</td>
                        <td class="text-end" style="padding-right: 16px;">
                            <form method="POST" action="{{ route('admin.notifications.logs.retry', $fail->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Retry Delivery">
                                    <i class="bi bi-arrow-repeat me-1"></i> Retry
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-5 d-block mb-1"></i>
                            No failed notification delivery records. All channels operational.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Deliveries Stream -->
<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <span class="fw-bold" style="font-size: 14px; color: #0F172A;">Recent Outbound Activity Stream</span>
            <div class="text-muted" style="font-size: 12px;">Real-time feed across in-app, email, SMS, and WhatsApp dispatchers.</div>
        </div>
        <a href="{{ route('admin.notifications.logs') }}" class="btn btn-sm btn-link text-decoration-none" style="color: #0D9488;">View Full Logs <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th style="padding: 10px 16px;">Channel</th>
                    <th>Status</th>
                    <th>Recipient</th>
                    <th>Provider</th>
                    <th>Dispatched At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentDeliveries as $log)
                    <tr>
                        <td style="padding: 12px 16px;">
                            <span class="badge" style="background-color: #E2E8F0; color: #1E293B;">{{ strtoupper($log->channel) }}</span>
                        </td>
                        <td>
                            @if(in_array($log->status, ['delivered', 'dispatched']))
                                <span class="badge" style="background-color: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;"><i class="bi bi-check me-1"></i> {{ ucfirst($log->status) }}</span>
                            @elseif($log->status === 'failed')
                                <span class="badge" style="background-color: #FEF2F2; color: #9F1239; border: 1px solid #FECDD3;"><i class="bi bi-x me-1"></i> Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($log->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $log->employee?->full_name ?? $log->user?->name ?? 'User' }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $log->recipient_address }}</div>
                        </td>
                        <td><span class="badge bg-light text-secondary border">{{ $log->provider_name }}</span></td>
                        <td class="text-muted" style="font-size: 12px;">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-muted">No dispatch logs recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

