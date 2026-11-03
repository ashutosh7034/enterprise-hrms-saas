@extends('layouts.app')

@section('title', 'Edit Message Template: ' . $template->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Edit Template: {{ $template->name }}</h4>
        <div class="text-muted" style="font-size: 13px;">Code: <code>{{ $template->code }}</code> | Channel: <strong>{{ strtoupper($template->channel) }}</strong> | Category: <strong>{{ $template->category }}</strong></div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.notifications.templates.preview', $template->id) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-eye me-1"></i> Preview
        </a>
        <a href="{{ route('admin.notifications.templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Templates
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 8px;">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.notifications.templates.update', $template->id) }}">
                    @csrf
                    @method('PUT')

                    @if(in_array($template->channel, ['email', 'in_app']))
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 13px;">Subject Line / Notification Title</label>
                            <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="form-control" placeholder="e.g. Action Required: {employee_name} Onboarding">
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Message Body Template <span class="text-danger">*</span></label>
                        <textarea name="body_template" rows="6" class="form-control font-monospace" style="font-size: 13px;" required>{{ old('body_template', $template->body_template) }}</textarea>
                        <div class="form-text" style="font-size: 11.5px;">Supports plain text and mustache curly placeholders like <code>{employee_name}</code>, <code>{company_name}</code>, etc.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size: 13px;">Action Link URL</label>
                        <input type="text" name="action_url" value="{{ old('action_url', $template->action_url) }}" class="form-control" placeholder="e.g. /leaves/applications or {action_url}">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="is_mandatory" name="is_mandatory" value="1" {{ old('is_mandatory', $template->is_mandatory) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_mandatory" style="font-size: 13px;">
                                    Mandatory Policy Alert
                                </label>
                                <div class="text-muted" style="font-size: 11px;">If enabled, employees cannot disable this alert in their notification preferences.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active" style="font-size: 13px;">
                                    Template Active
                                </label>
                                <div class="text-muted" style="font-size: 11px;">Disabled templates are skipped during dispatching without triggering errors.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="{{ route('admin.notifications.templates.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4" style="background-color: #0D9488; border-color: #0D9488;">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Placeholders Reference Column -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius: 8px;">
            <div class="card-header bg-white p-3 border-bottom">
                <span class="fw-bold" style="font-size: 13.5px; color: #0F172A;"><i class="bi bi-braces me-1"></i> Available Placeholders</span>
            </div>
            <div class="card-body p-3" style="font-size: 12.5px;">
                <p class="text-muted mb-3" style="font-size: 12px;">Dynamic variables populated automatically during notification compilation:</p>

                <div class="mb-2"><code>{employee_name}</code> <span class="text-muted">— Full employee name</span></div>
                <div class="mb-2"><code>{employee_code}</code> <span class="text-muted">— Employee identification code</span></div>
                <div class="mb-2"><code>{company_name}</code> <span class="text-muted">— Active company title</span></div>
                <div class="mb-2"><code>{company_code}</code> <span class="text-muted">— Company acronym (VE / VRE)</span></div>
                <div class="mb-2"><code>{action_url}</code> <span class="text-muted">— Direct portal link</span></div>
                <div class="mb-2"><code>{details}</code> <span class="text-muted">— Specific contextual reason</span></div>
                <div class="mb-2"><code>{remarks}</code> <span class="text-muted">— Approver feedback or rejection note</span></div>
                <div class="mb-2"><code>{attendance_date}</code> <span class="text-muted">— Date of shift/attendance punch</span></div>
                <div class="mb-2"><code>{leave_type}</code> <span class="text-muted">— Leave quota name</span></div>
                <div class="mb-2"><code>{start_date}</code> <span class="text-muted">— Period begin date</span></div>
                <div class="mb-2"><code>{end_date}</code> <span class="text-muted">— Period conclusion date</span></div>
                <div class="mb-2"><code>{period_month}</code> <span class="text-muted">— Month name (e.g. November 2026)</span></div>
                <div class="mb-2"><code>{net_pay}</code> <span class="text-muted">— Net salary in INR</span></div>
                <div class="mb-2"><code>{principal_amount}</code> <span class="text-muted">— Loan principal amount</span></div>
                <div class="mb-2"><code>{net_payable}</code> <span class="text-muted">— Full & Final net amount</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

