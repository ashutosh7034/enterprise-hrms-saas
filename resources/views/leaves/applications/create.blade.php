@extends('layouts.app')

@section('title', 'Apply for Leave')

@section('content')
<div class="mb-4">
    <a href="{{ route('leaves.applications.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Applications
    </a>
    <h1 class="h4 mb-1 text-slate-900 font-bold mt-2">Submit Leave Application</h1>
    <p class="text-muted small mb-0">Select dates to preview real-time sandwich policy deductions and balance reservation.</p>
</div>

@if($errors->has('leave_error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first('leave_error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-custom">
            <div class="card-body p-4">
                <form action="{{ route('leaves.applications.store') }}" method="POST" id="leaveApplicationForm">
                    @csrf

                    @if(Auth::user()->isHr())
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required onchange="fetchSandwichPreview()">
                                <option value="">Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employee_code }} - {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->department?->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <input type="hidden" name="employee_id" id="employee_id" value="{{ $selectedEmployee->id }}">
                        <div class="p-3 bg-light rounded mb-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-slate-900">{{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}</div>
                                    <div class="text-muted small">{{ $selectedEmployee->employee_code }} &bull; {{ $selectedEmployee->department?->name }}</div>
                                </div>
                                @if($selectedEmployee->isInNoticePeriod())
                                    <span class="badge bg-danger-subtle text-danger">Notice Period Active</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" id="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required onchange="fetchSandwichPreview()">
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $lt)
                                <option value="{{ $lt->id }}" {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>
                                    {{ $lt->name }} ({{ $lt->code }}) - {{ $lt->is_paid ? 'Paid' : 'Unpaid' }}
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="form-control" required onchange="syncDates()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="end_date" value="{{ old('end_date', date('Y-m-d')) }}" class="form-control" required onchange="fetchSandwichPreview()">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_half_day" value="1" id="is_half_day" {{ old('is_half_day') ? 'checked' : '' }} onchange="toggleHalfDay()">
                                <label class="form-check-label small fw-semibold" for="is_half_day">Half-Day Application</label>
                            </div>
                        </div>
                        <div class="col-md-6" id="half_day_type_wrapper" style="display: {{ old('is_half_day') ? 'block' : 'none' }};">
                            <select name="half_day_type" id="half_day_type" class="form-select form-select-sm">
                                <option value="First Half" {{ old('half_day_type') === 'First Half' ? 'selected' : '' }}>First Half</option>
                                <option value="Second Half" {{ old('half_day_type') === 'Second Half' ? 'selected' : '' }}>Second Half</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Leave <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3" placeholder="Provide clear reason for absence..." required>{{ old('reason') }}</textarea>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if(Auth::user()->isHr())
                        <div class="card bg-warning-subtle border-warning p-3 mb-3">
                            <h6 class="text-warning-emphasis fw-bold mb-2 small"><i class="bi bi-shield-lock me-1"></i> HR Notice Period Override</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_notice_period_override" value="1" id="is_notice_period_override" {{ old('is_notice_period_override') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="is_notice_period_override">Authorize Notice Period Exemption</label>
                            </div>
                            <input type="text" name="notice_period_justification" value="{{ old('notice_period_justification') }}" class="form-control form-control-sm" placeholder="Mandatory HR justification if employee is in notice period...">
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('leaves.applications.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-accent px-4">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Sandwich Calculation Preview -->
    <div class="col-lg-5">
        <div class="card card-custom">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-slate-900"><i class="bi bi-calculator me-1"></i> Calculation & Sandwich Preview</h6>
            </div>
            <div class="card-body p-3" id="previewContainer">
                <div class="text-center py-4 text-muted" id="previewPlaceholder">
                    <i class="bi bi-calendar3 fs-2 d-block mb-2"></i>
                    Select dates and leave type to preview deduction breakdown.
                </div>

                <div id="previewResults" style="display: none;">
                    <div class="alert alert-info py-2 mb-3 small d-flex justify-content-between align-items-center">
                        <span id="previewPolicyName" class="fw-semibold">Default Policy</span>
                        <span id="previewSandwichBadge" class="badge bg-warning text-dark">Sandwich Applied</span>
                    </div>

                    <div class="row g-2 text-center mb-3">
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <div class="text-muted smaller">Requested</div>
                                <div class="fw-bold text-slate-800" id="previewRequestedDays">0 d</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <div class="text-muted smaller">Non-Working Included</div>
                                <div class="fw-bold text-warning-emphasis" id="previewInterveningDays">0 d</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light border-teal">
                                <div class="text-muted smaller">Total Deduction</div>
                                <div class="fw-bold text-teal fs-6" id="previewTotalDays">0 d</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-slate-800 small fw-bold mb-2">Day-by-Day Audit Breakdown</h6>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle mb-0 smaller">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Deduct</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody id="previewBreakdownTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function syncDates() {
    const start = document.getElementById('start_date').value;
    const endInput = document.getElementById('end_date');
    if (!endInput.value || endInput.value < start) {
        endInput.value = start;
    }
    fetchSandwichPreview();
}

function toggleHalfDay() {
    const isHalfDay = document.getElementById('is_half_day').checked;
    const wrapper = document.getElementById('half_day_type_wrapper');
    const start = document.getElementById('start_date').value;
    const endInput = document.getElementById('end_date');

    wrapper.style.display = isHalfDay ? 'block' : 'none';
    if (isHalfDay) {
        endInput.value = start;
    }
    fetchSandwichPreview();
}

function fetchSandwichPreview() {
    const empId = document.getElementById('employee_id').value;
    const leaveTypeId = document.getElementById('leave_type_id').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const isHalfDay = document.getElementById('is_half_day').checked ? 1 : 0;

    if (!empId || !leaveTypeId || !startDate || !endDate) {
        return;
    }

    fetch('{{ route("leaves.preview-sandwich") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            employee_id: empId,
            leave_type_id: leaveTypeId,
            start_date: startDate,
            end_date: endDate,
            is_half_day: isHalfDay
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) return;

        document.getElementById('previewPlaceholder').style.display = 'none';
        document.getElementById('previewResults').style.display = 'block';

        document.getElementById('previewPolicyName').textContent = data.policy_name || 'Sandwich Policy';
        document.getElementById('previewSandwichBadge').style.display = data.sandwich_applied ? 'inline-block' : 'none';

        document.getElementById('previewRequestedDays').textContent = data.requested_days + ' d';
        document.getElementById('previewInterveningDays').textContent = (data.intervening_weekly_offs + data.intervening_holidays) + ' d';
        document.getElementById('previewTotalDays').textContent = data.total_payable_deduction + ' d';

        const tbody = document.getElementById('previewBreakdownTable');
        tbody.innerHTML = '';

        if (Array.isArray(data.breakdown)) {
            data.breakdown.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-nowrap fw-semibold">${row.date} <span class="text-muted smaller">(${row.day_name.substr(0, 3)})</span></td>
                    <td><span class="badge ${row.day_type === 'Working Day' ? 'bg-light text-dark' : (row.day_type === 'Holiday' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning-emphasis')}">${row.day_type}</span></td>
                    <td>${row.is_leave_deducted ? '<span class="text-danger fw-bold">+' + row.deduction_units + '</span>' : '<span class="text-muted">0</span>'}</td>
                    <td class="smaller text-muted">${row.reason}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    })
    .catch(err => console.error(err));
}

document.addEventListener('DOMContentLoaded', function() {
    fetchSandwichPreview();
});
</script>
@endsection

