@extends('layouts.app')

@section('title', 'Employee Loans & Salary Advances — Enterprise HRMS')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">{{ $isEss ? 'My Loans & Salary Advances' : 'Employee Loans & Advance Management' }}</h4>
            <p class="text-muted small mb-0">{{ $isEss ? 'Track your active advances, monthly EMI deductions, and apply for loans' : 'Manage corporate loans, advances, EMI deductions & early closures' }}</p>
        </div>
        <a href="{{ route('payroll.loans.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Apply for Loan / Advance
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Loan ID</th>
                        @if(!$isEss)
                            <th>Employee</th>
                        @endif
                        <th>Type</th>
                        <th>Principal</th>
                        <th>Tenure</th>
                        <th>Monthly EMI</th>
                        <th>Recovered / Remaining</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        <tr>
                            <td class="ps-3 fw-bold text-teal-700">{{ $loan->loan_application_number }}</td>
                            @if(!$isEss)
                                <td>
                                    <div class="fw-semibold">{{ $loan->employee?->full_name }}</div>
                                    <div class="text-muted small">{{ $loan->employee?->employee_code }} • {{ $loan->employee?->department?->name }}</div>
                                </td>
                            @endif
                            <td>
                                <span class="badge bg-light text-dark border">{{ $loan->loan_type }}</span>
                            </td>
                            <td class="fw-bold text-slate-800">₹{{ number_format($loan->principal_amount, 2) }}</td>
                            <td>{{ $loan->tenure_months }} Mos</td>
                            <td class="fw-semibold text-danger">₹{{ number_format($loan->monthly_emi, 2) }}</td>
                            <td>
                                <div>₹{{ number_format($loan->total_recovered, 2) }} / <span class="fw-bold text-slate-900">₹{{ number_format($loan->remaining_balance, 2) }}</span></div>
                                <div class="progress mt-1" style="height: 4px;">
                                    @php
                                        $pct = $loan->principal_amount > 0 ? min(100, round(($loan->total_recovered / $loan->principal_amount) * 100)) : 0;
                                    @endphp
                                    <div class="progress-bar bg-teal-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td>
                                @if($loan->status === 'Active')
                                    <span class="badge bg-success">Active</span>
                                @elseif($loan->status === 'Closed')
                                    <span class="badge bg-secondary">Closed</span>
                                @elseif($loan->status === 'Applied')
                                    <span class="badge bg-warning text-dark">Applied</span>
                                @elseif(in_array($loan->status, ['Manager Approved', 'HR Approved']))
                                    <span class="badge bg-info text-dark">{{ $loan->status }}</span>
                                @elseif($loan->status === 'Rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ $loan->status }}</span>
                                @endif
                            </td>
                            <td class="pe-3 text-end">
                                <a href="{{ route('payroll.loans.show', $loan) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isEss ? 8 : 9 }}" class="text-center py-4 text-muted">No loans or advances recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($loans->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $loans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

