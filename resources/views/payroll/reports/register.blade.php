@extends('layouts.app')

@section('title', "Payroll Register {$run->run_batch_number} — Enterprise HRMS")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Payroll Wage Register</h4>
            <p class="text-muted small mb-0">{{ $run->company?->name }} • Batch: {{ $run->run_batch_number }} • Period: {{ date('F Y', mktime(0, 0, 0, $run->month, 1, $run->year)) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.reports.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Reports Catalog
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Register
            </button>
        </div>
    </div>

    <!-- Summary Statistics Banner -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Total Gross Wages</div>
                <div class="fw-bold fs-4 text-slate-800">₹{{ number_format($data['summary']['total_gross'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Statutory Deductions (EE)</div>
                <div class="fw-bold fs-4 text-danger">₹{{ number_format($run->total_statutory_deductions, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="text-muted small">Employer Contributions</div>
                <div class="fw-bold fs-4 text-info">₹{{ number_format($data['summary']['total_employer_contributions'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-success">
                <div class="text-muted small">Net Disbursement</div>
                <div class="fw-bold fs-4 text-success">₹{{ number_format($data['summary']['total_net_payout'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Master Register Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0 text-nowrap small">
                <thead class="bg-light text-muted text-uppercase">
                    <tr>
                        <th rowspan="2" class="align-middle ps-3">Emp ID</th>
                        <th rowspan="2" class="align-middle">Employee Name</th>
                        <th rowspan="2" class="align-middle">Department</th>
                        <th colspan="3" class="text-center bg-teal-50">Days & OT</th>
                        <th colspan="4" class="text-center bg-primary-subtle">Earnings Breakdown (₹)</th>
                        <th colspan="5" class="text-center bg-danger-subtle">Statutory Deductions (₹)</th>
                        <th rowspan="2" class="align-middle text-end text-danger">Loans / Fines</th>
                        <th rowspan="2" class="align-middle text-end text-danger">Total Ded.</th>
                        <th rowspan="2" class="align-middle text-end pe-3 bg-success-subtle fw-bold">Net Salary (₹)</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-teal-50">Paid</th>
                        <th class="text-center bg-teal-50">LOP</th>
                        <th class="text-center bg-teal-50">OT Pay</th>
                        <th class="text-end bg-primary-subtle">Basic</th>
                        <th class="text-end bg-primary-subtle">HRA</th>
                        <th class="text-end bg-primary-subtle">Allowances</th>
                        <th class="text-end bg-primary-subtle fw-bold">Gross</th>
                        <th class="text-end bg-danger-subtle">PF</th>
                        <th class="text-end bg-danger-subtle">ESI</th>
                        <th class="text-end bg-danger-subtle">PT</th>
                        <th class="text-end bg-danger-subtle">LWF</th>
                        <th class="text-end bg-danger-subtle">TDS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['employees'] as $row)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $row->employee?->employee_code }}</td>
                            <td>{{ $row->employee?->full_name }}</td>
                            <td>{{ $row->employee?->department?->name }}</td>
                            <td class="text-center">{{ $row->paid_days }}</td>
                            <td class="text-center text-danger">{{ $row->loss_of_pay_days }}</td>
                            <td class="text-center">{{ number_format($row->overtime_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($row->basic_salary, 2) }}</td>
                            <td class="text-end">{{ number_format($row->hra, 2) }}</td>
                            <td class="text-end">{{ number_format($row->allowances_amount, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->gross_earnings, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($row->pf_employee, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($row->esi_employee, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($row->professional_tax, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($row->labour_welfare_fund, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($row->income_tax_tds, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($row->loan_emi_deduction + $row->fine_recovery_deduction, 2) }}</td>
                            <td class="text-end text-danger fw-bold">{{ number_format($row->total_deductions, 2) }}</td>
                            <td class="text-end pe-3 fw-bold text-success fs-6 bg-success-subtle">₹{{ number_format($row->net_salary, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="9" class="ps-3 text-uppercase">Grand Total Summary</td>
                        <td class="text-end">₹{{ number_format($data['summary']['total_gross'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_pf_employee'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_esi_employee'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_pt'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_lwf_employee'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_tds'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_loan_emi'] + $data['summary']['total_fine_recovery'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format($data['summary']['total_deductions'], 2) }}</td>
                        <td class="text-end pe-3 text-success fs-6 bg-success-subtle">₹{{ number_format($data['summary']['total_net_payout'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

