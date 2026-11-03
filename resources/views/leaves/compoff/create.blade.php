@extends('layouts.app')

@section('title', 'Claim Comp-Off')

@section('content')
<div class="mb-4">
    <a href="{{ route('leaves.compoff.index') }}" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> Back to Comp-Off Ledger
    </a>
    <h1 class="h4 mb-1 text-slate-900 font-bold mt-2">Claim Compensatory Off</h1>
    <p class="text-muted small mb-0">Select an eligible verified attendance record for working on a weekly off or holiday.</p>
</div>

@if($errors->has('compoff_error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ $errors->first('compoff_error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-custom">
    <div class="card-body p-4">
        <form action="{{ route('leaves.compoff.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ (old('employee_id') == $emp->id || ($employee && $employee->id == $emp->id)) ? 'selected' : '' }}>
                                {{ $emp->employee_code }} - {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->department?->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Qualifying Attendance Day <span class="text-danger">*</span></label>
                    <select name="attendance_record_id" class="form-select @error('attendance_record_id') is-invalid @enderror" required>
                        <option value="">Select Qualifying Work Record</option>
                        @foreach($eligibleAttendances as $att)
                            <option value="{{ $att->id }}" {{ old('attendance_record_id') == $att->id ? 'selected' : '' }}>
                                {{ $att->attendance_date->format('d M Y') }} &bull; Status: {{ $att->status }} &bull; Worked: {{ round($att->actual_worked_minutes / 60, 1) }} hrs
                            </option>
                        @endforeach
                    </select>
                    @error('attendance_record_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="text-muted smaller mt-1">Requires minimum 4 hours (240 mins) verified work on off/holiday.</div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Duty Description & Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3" placeholder="Explain the project, maintenance, or emergency requirement that necessitated working on off-day...">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('leaves.compoff.index') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-accent px-4">Submit Comp-Off Claim</button>
            </div>
        </form>
    </div>
</div>
@endsection

