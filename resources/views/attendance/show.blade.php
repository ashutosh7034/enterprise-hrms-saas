@extends('layouts.app')

@section('title', 'Attendance Audit Detail — Enterprise HRMS')

@section('content')
<div class="mb-4">
    <a href="{{ route('attendance.index', ['date' => $attendance->attendance_date->format('Y-m-d')]) }}" class="text-decoration-none text-muted" style="font-size: 13.5px;">
        <i class="bi bi-arrow-left me-1"></i> Back to Muster
    </a>
    <h1 class="h3 mt-2 mb-1" style="color: var(--brand-primary-dark); font-weight: 700;">
        Daily Attendance: {{ $attendance->employee?->full_name }} ({{ $attendance->attendance_date->format('d M Y') }})
    </h1>
    <p class="text-muted mb-0" style="font-size: 13.5px;">Audit verification of paired punches, duration calculations, shift policy deviations & overtime.</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Summary Card -->
        <div class="card card-custom mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background-color: #E2E8F0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #475569;">
                        {{ substr($attendance->employee?->first_name ?? 'E', 0, 1) }}{{ substr($attendance->employee?->last_name ?? 'M', 0, 1) }}
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark">{{ $attendance->employee?->full_name }}</div>
                        <div class="text-muted small">{{ $attendance->employee?->employee_code }} • Gate Pass: {{ $attendance->employee?->gate_pass_number ?: 'N/A' }}</div>
                    </div>
                </div>

                <div class="list-group list-group-flush border-top border-bottom my-3">
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Muster Status</span>
                        <span class="fw-bold">{{ $attendance->status }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Total Working Time</span>
                        <span class="fw-bold text-primary">{{ $attendance->formatted_work_duration }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">First Punch IN</span>
                        <span class="fw-semibold">{{ $attendance->first_in ? $attendance->first_in->format('H:i:s') : 'None' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Last Punch OUT</span>
                        <span class="fw-semibold">{{ $attendance->last_out ? $attendance->last_out->format('H:i:s') : 'None' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Late Arrival</span>
                        <span class="{{ $attendance->late_arrival_minutes > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $attendance->late_arrival_minutes }} mins</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Early Departure</span>
                        <span class="{{ $attendance->early_departure_minutes > 0 ? 'text-warning fw-bold' : 'text-muted' }}">{{ $attendance->early_departure_minutes }} mins</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Overtime</span>
                        <span class="text-success fw-bold">+{{ $attendance->overtime_minutes }} mins</span>
                    </div>
                </div>

                <div class="text-muted small">
                    <i class="bi bi-clock-history me-1"></i> Calculated at: {{ $attendance->calculated_at ? $attendance->calculated_at->format('d M Y, H:i:s') : 'N/A' }}
                </div>
            </div>
        </div>

        <!-- Shift Policy Details -->
        <div class="card card-custom">
            <div class="card-body p-4">
                <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.05em;">Scheduled Shift Policy</h6>
                @if($attendance->shift)
                    <div class="fw-bold text-dark">{{ $attendance->shift->name }} ({{ $attendance->shift->code }})</div>
                    <div class="text-muted small mb-3">Planned Schedule: {{ $attendance->shift->formatted_timings }}</div>
                    
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Grace Period: {{ $attendance->shift->grace_period_minutes }}m</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Full Day Threshold: {{ intdiv($attendance->shift->full_day_threshold_minutes, 60) }} hours</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Half Day Threshold: {{ intdiv($attendance->shift->half_day_threshold_minutes, 60) }} hours</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> Deducted Break: {{ $attendance->shift->break_duration_minutes }}m</li>
                    </ul>
                @else
                    <div class="text-muted">No scheduled shift mapped. Standard 8-hour company fallback applied.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Punch Timeline Card -->
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-fingerprint me-2 text-primary"></i>Biometric Punch Timeline</h6>
                <span class="badge bg-light text-dark border">{{ $attendance->total_punches }} recorded transactions</span>
            </div>
            <div class="card-body p-4">
                @php $punches = (array) $attendance->punch_records_json; @endphp
                @if(count($punches) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Punch Timestamp</th>
                                    <th>Direction</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($punches as $index => $p)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="font-monospace fw-semibold">{{ $p['time'] ?? 'N/A' }}</td>
                                        <td>
                                            @if(($p['direction'] ?? '') === 'IN')
                                                <span class="badge bg-success text-white">IN</span>
                                            @elseif(($p['direction'] ?? '') === 'OUT')
                                                <span class="badge bg-danger text-white">OUT</span>
                                            @else
                                                <span class="badge bg-secondary text-white">{{ $p['direction'] ?? 'UNKNOWN' }}</span>
                                            @endif
                                        </td>
                                        <td><span class="text-success small"><i class="bi bi-check-circle me-1"></i>Valid Raw Ingest</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x d-block mb-2" style="font-size: 32px; color: #CBD5E1;"></i>
                        Zero punches captured for this shift window.
                    </div>
                @endif
            </div>
        </div>

        <!-- Regularization & Audit Information -->
        @if($attendance->regularization)
            <div class="card card-custom border-start border-4 border-info">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-info"><i class="bi bi-pencil-square me-2"></i>Attendance Regularization Applied</h6>
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <span class="text-muted small">Type:</span>
                            <div class="fw-semibold">{{ $attendance->regularization->regularization_type }}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted small">Status:</span>
                            <div class="fw-semibold text-success">{{ $attendance->regularization->status }}</div>
                        </div>
                        <div class="col-md-12 mt-2">
                            <span class="text-muted small">Employee Justification / Reason:</span>
                            <p class="mb-0 bg-light p-2 rounded small mt-1">{{ $attendance->regularization->reason }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

