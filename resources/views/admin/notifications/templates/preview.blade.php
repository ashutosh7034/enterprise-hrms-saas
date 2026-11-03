@extends('layouts.app')

@section('title', 'Template Preview: ' . $template->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Template Preview: {{ $template->name }}</h4>
        <div class="text-muted" style="font-size: 13px;">Channel: <strong>{{ strtoupper($template->channel) }}</strong> | Code: <code>{{ $template->code }}</code></div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.notifications.templates.edit', $template->id) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Template
        </a>
        <a href="{{ route('admin.notifications.templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Templates
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        @if($template->channel === 'sms')
            <!-- Mobile SMS Preview Device Shell -->
            <div class="card border shadow-sm mx-auto" style="max-width: 400px; border-radius: 28px; background: #000; padding: 12px;">
                <div class="card border-0 bg-white" style="border-radius: 20px; min-height: 460px; overflow: hidden;">
                    <div class="bg-light p-3 text-center border-bottom">
                        <div class="fw-bold text-dark" style="font-size: 13px;">HRMS-ALERTS</div>
                        <div class="text-muted" style="font-size: 11px;">Text Message • SMS</div>
                    </div>
                    <div class="p-3" style="min-height: 340px; background-color: #F8FAFC;">
                        <div class="d-inline-block bg-white p-3 rounded-3 shadow-sm border" style="max-width: 85%; font-size: 13px; color: #1E293B;">
                            {{ $rendered['body'] }}
                            <div class="text-end text-muted mt-1" style="font-size: 10px;">{{ date('H:i') }} • Delivered</div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($template->channel === 'whatsapp')
            <!-- WhatsApp Preview Device Shell -->
            <div class="card border shadow-sm mx-auto" style="max-width: 400px; border-radius: 28px; background: #000; padding: 12px;">
                <div class="card border-0 bg-white" style="border-radius: 20px; min-height: 460px; overflow: hidden;">
                    <div class="p-3 d-flex align-items-center gap-2" style="background-color: #075E54; color: #FFFFFF;">
                        <i class="bi bi-whatsapp fs-5"></i>
                        <div>
                            <div class="fw-bold" style="font-size: 13px;">{{ $template->company?->name ?? 'Vishal Enterprise' }} Verified</div>
                            <div style="font-size: 10.5px; opacity: 0.85;">Official Business Account</div>
                        </div>
                    </div>
                    <div class="p-3" style="min-height: 340px; background-color: #E5DDD5;">
                        <div class="d-inline-block bg-white p-3 rounded-3 shadow-sm border" style="max-width: 88%; font-size: 13px; color: #1E293B; border-left: 4px solid #25D366 !important;">
                            <div class="fw-bold mb-1" style="color: #075E54; font-size: 12px;">HRMS NOTIFICATION</div>
                            {{ $rendered['body'] }}
                            @if(!empty($rendered['action_url']))
                                <div class="mt-2 pt-2 border-top">
                                    <a href="#" class="btn btn-sm btn-outline-success w-100" style="font-size: 11px;">Open in HRMS Portal</a>
                                </div>
                            @endif
                            <div class="text-end text-muted mt-1" style="font-size: 10px;">{{ date('H:i') }} <i class="bi bi-check2-all text-primary"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- In-App Card Preview -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px;">
                <div class="card-header bg-white p-3 border-bottom">
                    <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;">In-App Feed Notification Preview</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 border">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: #E2E8F0; flex-shrink: 0;">
                            <i class="bi bi-bell-fill fs-5" style="color: #0D9488;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark" style="font-size: 14px;">{{ $rendered['subject'] ?? $template->name }}</span>
                                <span class="badge" style="background-color: #0D9488; color: #FFFFFF; font-size: 10px;">Just now</span>
                            </div>
                            <div class="text-secondary mb-2" style="font-size: 13px;">{{ $rendered['body'] }}</div>
                            @if(!empty($rendered['action_url']))
                                <a href="#" class="btn btn-sm btn-outline-teal" style="color: #0D9488; border-color: #0D9488; font-size: 12px; padding: 2px 10px;">
                                    View Details <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

