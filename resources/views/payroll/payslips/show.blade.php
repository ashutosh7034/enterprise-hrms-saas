@extends('layouts.app')

@section('title', "Payslip {$payslip->payslip_number} — Enterprise HRMS")

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-slate-800">Payslip Statement</h4>
            <p class="text-muted small mb-0">{{ $payslip->payslip_number }} — {{ date('F Y', mktime(0, 0, 0, $payslip->month, 1, $payslip->year)) }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll.payslips.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Payslips
            </a>
            <a href="{{ route('payroll.payslips.download', $payslip) }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Payslip Paper Layout -->
    <div class="card border-0 shadow-sm rounded-3 mx-auto" style="max-width: 900px;">
        <div class="card-body p-5">
            <!-- Company Header -->
            <div class="border-bottom pb-4 mb-4 text-center">
                <h3 class="fw-bold text-slate-900 mb-1">{{ $payslip->company?->name }}</h3>
                <p class="text-muted small mb-1">{{ $payslip->company?->registered_address ?? 'Industrial Area, Pune, Maharashtra' }}</p>
                <div class="badge bg-teal-50 text-teal-800 border border-teal-200 px-3 py-2 fs-6 mt-2">
                    SALARY PAYSLIP FOR THE MONTH OF {{ strtoupper(date('F Y', mktime(0, 0, 0, $payslip->month, 1, $payslip->year))) }}
                </div>
            </div>

            <!-- Employee & Attendance Info Grid -->
            <div class="row g-3 mb-4 bg-light p-3 rounded-3 small">
                <div class="col-md-6">
                    <div class="row g-1">
                        <div class="col-5 text-muted">Employee Code:</div>
                        <div class="col-7 fw-bold">{{ $payslip->employee?->employee_code }}</div>
                        <div class="col-5 text-muted">Employee Name:</div>
                        <div class="col-7 fw-bold">{{ $payslip->employee?->full_name }}</div>
                        <div class="col-5 text-muted">Department:</div>
                        <div class="col-7">{{ $payslip->employee?->department?->name ?? 'N/A' }}</div>
                        <div class="col-5 text-muted">Designation:</div>
                        <div class="col-7">{{ $payslip->employee?->designation?->name ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-1">
                        <div class="col-5 text-muted">Date of Joining:</div>
                        <div class="col-7">{{ $payslip->employee?->date_of_joining?->format('d M Y') ?? 'N/A' }}</div>
                        <div class="col-5 text-muted">Location / Plant:</div>
                        <div class="col-7">{{ $payslip->employee?->location?->name ?? 'N/A' }}</div>
                        <div class="col-5 text-muted">PAN Number:</div>
                        <div class="col-7">{{ $payslip->employee?->masked_pan ?? 'N/A' }}</div>
                        <div class="col-5 text-muted">Aadhaar (Masked):</div>
                        <div class="col-7">{{ $payslip->employee?->aadhaar_masked ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Days Summary Strip -->
            <div class="row g-2 text-center mb-4">
                <div class="col">
                    <div class="p-2 border rounded">
                        <div class="text-muted small">Calendar Days</div>
                        <div class="fw-bold fs-6">{{ $payslip->calendar_days }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 border rounded">
                        <div class="text-muted small">Worked Days</div>
                        <div class="fw-bold fs-6 text-primary">{{ $payslip->worked_days }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 border rounded">
                        <div class="text-muted small">Paid Leaves</div>
                        <div class="fw-bold fs-6 text-info">{{ $payslip->paid_leave_days }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 border rounded">
                        <div class="text-muted small">Weekly Offs</div>
                        <div class="fw-bold fs-6">{{ $payslip->weekly_off_days }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 border rounded">
                        <div class="text-muted small">Loss of Pay (LOP)</div>
                        <div class="fw-bold fs-6 text-danger">{{ $payslip->loss_of_pay_days }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="p-2 border rounded bg-success-subtle border-success-subtle">
                        <div class="text-success small fw-semibold">Paid Days</div>
                        <div class="fw-bold fs-6 text-success">{{ $payslip->paid_days }}</div>
                    </div>
                </div>
            </div>

            <!-- Earnings vs Deductions Two-Column Table -->
            <div class="row g-4 mb-4">
                <!-- Earnings Column -->
                <div class="col-md-6">
                    <div class="border rounded-3 overflow-hidden h-100">
                        <div class="bg-light p-2 fw-bold text-slate-800 border-bottom d-flex justify-content-between">
                            <span>Earnings</span>
                            <span>Amount (₹)</span>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-3">Basic Salary</td>
                                    <td class="pe-3 text-end fw-semibold">₹{{ number_format($payslip->basic_salary, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">House Rent Allowance (HRA)</td>
                                    <td class="pe-3 text-end fw-semibold">₹{{ number_format($payslip->hra, 2) }}</td>
                                </tr>
                                @if($payslip->da > 0)
                                    <tr>
                                        <td class="ps-3">Dearness Allowance (DA)</td>
                                        <td class="pe-3 text-end fw-semibold">₹{{ number_format($payslip->da, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="ps-3">Special / Other Allowances</td>
                                    <td class="pe-3 text-end fw-semibold">₹{{ number_format($payslip->allowances_amount, 2) }}</td>
                                </tr>
                                @if($payslip->overtime_amount > 0)
                                    <tr>
                                        <td class="ps-3">Overtime Pay ({{ $payslip->overtime_hours }} hrs)</td>
                                        <td class="pe-3 text-end fw-semibold text-warning">₹{{ number_format($payslip->overtime_amount, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="border-top bg-light">
                                <tr class="fw-bold">
                                    <td class="ps-3">Gross Earnings</td>
                                    <td class="pe-3 text-end fs-6 text-slate-900">₹{{ number_format($payslip->gross_earnings, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Deductions Column -->
                <div class="col-md-6">
                    <div class="border rounded-3 overflow-hidden h-100">
                        <div class="bg-light p-2 fw-bold text-slate-800 border-bottom d-flex justify-content-between">
                            <span>Deductions</span>
                            <span>Amount (₹)</span>
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-3">Provident Fund (Employee)</td>
                                    <td class="pe-3 text-end">₹{{ number_format($payslip->pf_employee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">ESI (Employee)</td>
                                    <td class="pe-3 text-end">₹{{ number_format($payslip->esi_employee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3">Professional Tax</td>
                                    <td class="pe-3 text-end">₹{{ number_format($payslip->professional_tax, 2) }}</td>
                                </tr>
                                @if($payslip->labour_welfare_fund > 0)
                                    <tr>
                                        <td class="ps-3">Labour Welfare Fund (LWF)</td>
                                        <td class="pe-3 text-end">₹{{ number_format($payslip->labour_welfare_fund, 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip->income_tax_tds > 0)
                                    <tr>
                                        <td class="ps-3">Income Tax (TDS)</td>
                                        <td class="pe-3 text-end">₹{{ number_format($payslip->income_tax_tds, 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip->loan_emi_deduction > 0)
                                    <tr>
                                        <td class="ps-3">Loan / Advance EMI</td>
                                        <td class="pe-3 text-end text-danger">₹{{ number_format($payslip->loan_emi_deduction, 2) }}</td>
                                    </tr>
                                @endif
                                @if($payslip->fine_recovery_deduction > 0)
                                    <tr>
                                        <td class="ps-3">Asset Damage Fine Recovery</td>
                                        <td class="pe-3 text-end text-danger">₹{{ number_format($payslip->fine_recovery_deduction, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="border-top bg-light">
                                <tr class="fw-bold">
                                    <td class="ps-3">Total Deductions</td>
                                    <td class="pe-3 text-end fs-6 text-danger">₹{{ number_format($payslip->total_deductions, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Net Salary Banner -->
            <div class="p-3 bg-teal-50 border border-teal-200 rounded-3 d-flex justify-content-between align-items-center mb-4">
                <div>
                    <div class="text-teal-900 small fw-semibold text-uppercase">Net Take-Home Salary</div>
                    <div class="text-muted small">Transferred to Bank Account via NEFT</div>
                </div>
                <div class="text-end">
                    <span class="fs-3 fw-bold text-teal-800">₹{{ number_format($payslip->net_salary, 2) }}</span>
                </div>
            </div>

            <div class="text-muted small text-center pt-3 border-top">
                This is a computer-generated salary statement and does not require a physical signature.
            </div>
        </div>
    </div>
</div>
@endsection

