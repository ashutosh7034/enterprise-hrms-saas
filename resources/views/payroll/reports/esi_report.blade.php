@extends('layouts.app')

@section('title', "ESI Monthly Return — {$run->run_batch_number}")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Employee State Insurance (ESI) Return</h4>
            <p class="text-muted small mb-0">{{ $run->company?->name }} • Batch: {{ $run->run_batch_number }} • Month: {{ date('F Y', mktime(0, 0, 0, $run->month, 1, $run->year)) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.reports.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Reports
            </a>
            <a href="{{ route('payroll.reports.esi', ['run' => $run->id, 'export' => 'csv']) }}" class="btn btn-warning btn-sm px-3 shadow-sm text-dark">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Export ESI CSV
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Employee Code</th>
                        <th>Employee Name</th>
                        <th class="text-center">Worked Days</th>
                        <th class="text-end">Gross Wages (₹)</th>
                        <th class="text-end">EE Contribution (0.75%)</th>
                        <th class="pe-3 text-end">ER Contribution (3.25%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $rec['employee_code'] }}</td>
                            <td>{{ $rec['employee_name'] }}</td>
                            <td class="text-center">{{ $rec['worked_days'] }}</td>
                            <td class="text-end">₹{{ number_format($rec['gross_wages'], 2) }}</td>
                            <td class="text-end text-danger fw-semibold">₹{{ number_format($rec['employee_contribution'], 2) }}</td>
                            <td class="pe-3 text-end text-info fw-semibold">₹{{ number_format($rec['employer_contribution'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No ESI covered employees in this payroll run (wages above ₹21,000 threshold or exempt).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

