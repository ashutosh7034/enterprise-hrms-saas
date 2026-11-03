@extends('layouts.app')

@section('title', 'Notification Message Templates')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Notification Message Templates</h4>
        <div class="text-muted" style="font-size: 13px;">Manage multi-channel message wording, variable substitutions, and mandatory compliance rules.</div>
    </div>
    <a href="{{ route('admin.notifications.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.notifications.templates.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-6">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach(['Onboarding', 'Attendance', 'Shift', 'Leave', 'Payroll', 'Loans', 'Settlement', 'Compliance', 'Security'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <select name="channel" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Channels</option>
                    @foreach(['in_app' => 'In-App', 'email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $k => $v)
                        <option value="{{ $k }}" {{ request('channel') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-12 text-md-end">
                @if(request()->hasAny(['category', 'channel']))
                    <a href="{{ route('admin.notifications.templates.index') }}" class="btn btn-link btn-sm text-decoration-none text-muted p-0">Clear Filters</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Templates Table -->
<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead style="background-color: #F8FAFC;">
                <tr>
                    <th style="padding: 12px 16px;">Template Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Channel</th>
                    <th>Mandatory Policy</th>
                    <th>Status</th>
                    <th class="text-end" style="padding-right: 16px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                    <tr>
                        <td style="padding: 12px 16px;"><code>{{ $tpl->code }}</code></td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $tpl->name }}</div>
                            <div class="text-muted" style="font-size: 11px;">Company: {{ $tpl->company?->code ?? 'Global System Default' }}</div>
                        </td>
                        <td><span class="badge" style="background-color: #E2E8F0; color: #334155;">{{ $tpl->category }}</span></td>
                        <td>
                            <span class="badge" style="background-color: #F1F5F9; color: #1E293B; border: 1px solid #CBD5E1;">{{ strtoupper($tpl->channel) }}</span>
                        </td>
                        <td>
                            @if($tpl->is_mandatory)
                                <span class="badge" style="background-color: #FEF3C7; color: #92400E;"><i class="bi bi-lock-fill me-1"></i> Mandatory</span>
                            @else
                                <span class="text-muted" style="font-size: 12px;">Opt-out permitted</span>
                            @endif
                        </td>
                        <td>
                            @if($tpl->is_active)
                                <span class="badge" style="background-color: #ECFDF5; color: #065F46;">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end" style="padding-right: 16px;">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.notifications.templates.preview', $tpl->id) }}" class="btn btn-outline-secondary" target="_blank" title="Live Preview">
                                    <i class="bi bi-eye"></i> Preview
                                </a>
                                <a href="{{ route('admin.notifications.templates.edit', $tpl->id) }}" class="btn btn-outline-primary" title="Edit Template">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-muted">No message templates found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($templates->hasPages())
        <div class="card-footer bg-white p-3 border-0">
            {{ $templates->links() }}
        </div>
    @endif
</div>
@endsection

