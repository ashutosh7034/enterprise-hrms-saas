@extends('layouts.app')

@section('title', "Month-on-Month Payroll Variance — {$run->run_batch_number}")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Month-on-Month Payroll Variance Analysis</h4>
            <p class="text-muted small mb-0">{{ $run->company?->name }} • Current: {{ $variance['current_period'] }} vs Previous: {{ $variance['previous_period'] }}</p>
        </div>
        <a href="{{ route('payroll.reports.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Reports
        </a>
    </div>

    <!-- Variance Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 text-center">
                <div class="text-muted small fw-semibold text-uppercase">Headcount Variance</div>
                <div class="fs-2 fw-bold my-2 {{ $variance['headcount_diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $variance['headcount_diff'] > 0 ? '+' : '' }}{{ $variance['headcount_diff'] }} Employees
                </div>
                <div class="text-muted small">Change in processed headcount</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 text-center">
                <div class="text-muted small fw-semibold text-uppercase">Gross Payout Variance</div>
                <div class="fs-2 fw-bold my-2 {{ $variance['gross_payout_diff'] >= 0 ? 'text-primary' : 'text-danger' }}">
                    {{ $variance['gross_payout_diff'] > 0 ? '+' : '' }}₹{{ number_format($variance['gross_payout_diff'], 2) }}
                </div>
                <div class="badge bg-light text-dark border">
                    {{ $variance['percentage_gross_change'] }}% change
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 text-center">
                <div class="text-muted small fw-semibold text-uppercase">Net Disbursement Variance</div>
                <div class="fs-2 fw-bold my-2 {{ $variance['net_payout_diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $variance['net_payout_diff'] > 0 ? '+' : '' }}₹{{ number_format($variance['net_payout_diff'], 2) }}
                </div>
                <div class="text-muted small">Variance in actual bank payout</div>
            </div>
        </div>
    </div>
</div>
@endsection

