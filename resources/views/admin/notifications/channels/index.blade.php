@extends('layouts.app')

@section('title', 'Notification Channel Settings')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Notification Channel Settings</h4>
        <div class="text-muted" style="font-size: 13px;">Configure delivery gateways, mock switches, and credentials for In-App, Email, SMS, and WhatsApp.</div>
    </div>
    <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <span class="fw-bold" style="font-size: 14px; color: #0F172A;">Active Gateways by Company</span>
            <div class="text-muted" style="font-size: 12px;">Mock mode prevents real carrier transmission during testing and local development.</div>
        </div>
        <span class="badge" style="background-color: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;">
            <i class="bi bi-shield-check me-1"></i> Safety Guard Active
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th style="padding: 12px 16px;">Company</th>
                    <th>Channel</th>
                    <th>Provider Name</th>
                    <th>Mock Mode (Safe)</th>
                    <th>Status</th>
                    <th class="text-end" style="padding-right: 16px;">Configure</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $chan)
                    <tr>
                        <td style="padding: 12px 16px;">
                            <span class="badge bg-secondary" style="font-size: 11px;">{{ $chan->company?->code ?? 'Global' }}</span>
                            <span class="fw-semibold ms-1">{{ $chan->company?->name ?? 'System Default' }}</span>
                        </td>
                        <td>
                            <span class="badge" style="background-color: #F1F5F9; color: #1E293B; border: 1px solid #CBD5E1; font-size: 11px;">
                                {{ strtoupper($chan->channel) }}
                            </span>
                        </td>
                        <td><code>{{ $chan->provider_name }}</code></td>
                        <td>
                            @if($chan->is_mock)
                                <span class="badge" style="background-color: #FEF3C7; color: #92400E;"><i class="bi bi-shield-fill-check me-1"></i> Mock Active</span>
                            @else
                                <span class="badge" style="background-color: #FEF2F2; color: #9F1239;"><i class="bi bi-broadcast me-1"></i> Production Live</span>
                            @endif
                        </td>
                        <td>
                            @if($chan->is_enabled)
                                <span class="badge" style="background-color: #ECFDF5; color: #065F46;">Enabled</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                        <td class="text-end" style="padding-right: 16px;">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editChannelModal{{ $chan->id }}">
                                <i class="bi bi-sliders me-1"></i> Settings
                            </button>

                            <!-- Edit Channel Modal -->
                            <div class="modal fade text-start" id="editChannelModal{{ $chan->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $chan->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header">
                                            <h5 class="modal-title fs-6 fw-bold" id="modalLabel{{ $chan->id }}">
                                                Configure {{ strtoupper($chan->channel) }} — {{ $chan->company?->code ?? 'Global' }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.notifications.channels.update', $chan->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body" style="font-size: 13px;">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Provider Engine</label>
                                                    <select name="provider_name" class="form-select form-select-sm">
                                                        <option value="mock" {{ $chan->provider_name === 'mock' ? 'selected' : '' }}>Mock Gateway (Safe Simulation)</option>
                                                        <option value="database" {{ $chan->provider_name === 'database' ? 'selected' : '' }}>Database / In-App</option>
                                                        <option value="log" {{ $chan->provider_name === 'log' ? 'selected' : '' }}>Laravel Mail Log Driver</option>
                                                        <option value="smtp" {{ $chan->provider_name === 'smtp' ? 'selected' : '' }}>Production SMTP</option>
                                                        <option value="msg91" {{ $chan->provider_name === 'msg91' ? 'selected' : '' }}>MSG91 SMS Gateway</option>
                                                        <option value="twilio" {{ $chan->provider_name === 'twilio' ? 'selected' : '' }}>Twilio Cloud SMS</option>
                                                        <option value="meta_whatsapp" {{ $chan->provider_name === 'meta_whatsapp' ? 'selected' : '' }}>Meta WhatsApp Cloud API</option>
                                                        <option value="gupshup" {{ $chan->provider_name === 'gupshup' ? 'selected' : '' }}>Gupshup WhatsApp API</option>
                                                    </select>
                                                </div>

                                                <div class="form-check form-switch mb-3 p-2 bg-light rounded border">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="mock{{ $chan->id }}" name="is_mock" value="1" {{ $chan->is_mock ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="mock{{ $chan->id }}">Mock Delivery Simulation</label>
                                                    <div class="text-muted" style="font-size: 11px;">Recommended for local environments and testing.</div>
                                                </div>

                                                <div class="form-check form-switch mb-3 p-2 bg-light rounded border">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="enabled{{ $chan->id }}" name="is_enabled" value="1" {{ $chan->is_enabled ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enabled{{ $chan->id }}">Channel Enabled</label>
                                                    <div class="text-muted" style="font-size: 11px;">If disabled, all outgoing messages for this channel are suppressed.</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Credentials & API Token (Encrypted)</label>
                                                    <textarea name="credentials" rows="3" class="form-control font-monospace" style="font-size: 12px;" placeholder="Optional JSON API key / secret">{{ $chan->credentials }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light p-2">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary btn-sm" style="background-color: #0D9488; border-color: #0D9488;">Save Settings</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-muted">No channel configurations found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

