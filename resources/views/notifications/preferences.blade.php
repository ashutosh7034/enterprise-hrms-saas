@extends('layouts.app')

@section('title', 'Notification Preferences')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Notification Preferences</h4>
        <div class="text-muted" style="font-size: 13px;">Manage which channels you receive notifications on for each HRMS module.</div>
    </div>
    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Notifications
    </a>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <span class="fw-bold" style="font-size: 14px; color: #0F172A;">Delivery Channels Matrix</span>
            <div class="text-muted" style="font-size: 12px;">Toggle notification channels. Mandatory statutory, compliance, and security alerts cannot be disabled.</div>
        </div>
        <span class="badge" style="background-color: #FEF3C7; color: #92400E; font-size: 11px;">
            <i class="bi bi-shield-lock me-1"></i> Company Policy Enforced
        </span>
    </div>

    <form method="POST" action="{{ route('notifications.preferences.update') }}">
        @csrf
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                <thead style="background-color: #F8FAFC;">
                    <tr>
                        <th style="width: 30%; padding: 12px 16px;">Category / Module</th>
                        <th class="text-center" style="width: 17%;">In-App</th>
                        <th class="text-center" style="width: 17%;">Email</th>
                        <th class="text-center" style="width: 17%;">SMS</th>
                        <th class="text-center" style="width: 17%;">WhatsApp</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $categories = [
                            'Onboarding' => 'Employee onboarding, account activation, document notices',
                            'Attendance' => 'Shift punches, regularization approvals, deviations',
                            'Shift' => 'Roster scheduling, shift rotations, plant shutdowns',
                            'Leave' => 'Leave submissions, manager approvals, period closures',
                            'Payroll' => 'Monthly payslips, salary disbursements, tax projections',
                            'Loans' => 'Loan approvals, monthly EMI deduction notices',
                            'Settlement' => 'Full and Final (FnF) exit statements, final clearance',
                            'Compliance' => 'Mandatory PF/ESI, statutory filings, audit reminders',
                            'Security' => 'Session security alerts, password changes, critical access',
                        ];
                    @endphp

                    @foreach($categories as $cat => $desc)
                        @php
                            $isMandatory = in_array($cat, ['Compliance', 'Security']);
                            $catPrefs = $grouped->get($cat, collect())->keyBy('channel');
                        @endphp
                        <tr>
                            <td style="padding: 14px 16px;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-dark">{{ $cat }}</span>
                                    @if($isMandatory)
                                        <span class="badge" style="background-color: #FEE2E2; color: #991B1B; font-size: 10px;">Mandatory</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size: 11.5px;">{{ $desc }}</div>
                            </td>

                            @foreach(['in_app', 'email', 'sms', 'whatsapp'] as $chan)
                                @php
                                    $pref = $catPrefs->get($chan);
                                    $enabled = $isMandatory || ($pref ? $pref->is_enabled : true);
                                @endphp
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               name="preferences[{{ $cat }}][{{ $chan }}]"
                                               value="1"
                                               {{ $enabled ? 'checked' : '' }}
                                               {{ $isMandatory ? 'disabled' : '' }}
                                               style="cursor: {{ $isMandatory ? 'not-allowed' : 'pointer' }};">
                                        @if($isMandatory)
                                            <input type="hidden" name="preferences[{{ $cat }}][{{ $chan }}]" value="1">
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-light p-3 border-top d-flex align-items-center justify-content-between">
            <span class="text-muted" style="font-size: 12px;">Changes take effect immediately upon saving.</span>
            <button type="submit" class="btn btn-primary btn-sm px-4" style="background-color: #0D9488; border-color: #0D9488;">
                <i class="bi bi-save me-1"></i> Save Preferences
            </button>
        </div>
    </form>
</div>
@endsection

