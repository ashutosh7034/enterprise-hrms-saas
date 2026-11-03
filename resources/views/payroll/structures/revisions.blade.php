@extends('layouts.app')

@section('title', 'Salary Revision History — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Salary Revision Audit Trail</h4>
            <p class="text-muted small mb-0">{{ $employee->full_name }} ({{ $employee->employee_code }}) — Complete historical progression of salary amendments</p>
        </div>
        <a href="{{ route('payroll.structures.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Structures
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Effective Date</th>
                        <th>Old Annual CTC</th>
                        <th>New Annual CTC</th>
                        <th>Increment %</th>
                        <th>Authorized By</th>
                        <th>Revision Reason / Notes</th>
                        <th class="pe-3">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revisions as $rev)
                        <tr>
                            <td class="ps-3 fw-bold text-teal-700">{{ $rev->effective_from_date->format('d M Y') }}</td>
                            <td class="text-muted">₹{{ number_format($rev->old_ctc_annual, 2) }}</td>
                            <td class="fw-bold text-success fs-6">₹{{ number_format($rev->new_ctc_annual, 2) }}</td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    +{{ number_format($rev->increment_percentage, 2) }}%
                                </span>
                            </td>
                            <td>{{ $rev->changer?->name ?? 'System Admin' }}</td>
                            <td>{{ $rev->reason ?? 'Periodic Revision' }}</td>
                            <td class="pe-3 text-muted small">{{ $rev->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No revision history found. This employee is on their initial compensation structure.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

