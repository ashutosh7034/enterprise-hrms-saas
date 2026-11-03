<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payslip - {{ $payslip->payslip_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 15px; }
        .company-name { font-size: 18px; font-weight: bold; color: #0f172a; text-transform: uppercase; margin-bottom: 4px; }
        .company-address { font-size: 10px; color: #64748b; margin-bottom: 6px; }
        .title-badge { font-size: 12px; font-weight: bold; color: #0d9488; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-table, .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 4px 6px; font-size: 10px; }
        .info-table .label { color: #64748b; width: 22%; }
        .info-table .value { font-weight: bold; width: 28%; }
        .attendance-box { width: 100%; border: 1px solid #cbd5e1; border-collapse: collapse; margin-bottom: 15px; text-align: center; }
        .attendance-box th { background-color: #f1f5f9; padding: 5px; font-size: 9px; text-transform: uppercase; color: #475569; border: 1px solid #cbd5e1; }
        .attendance-box td { padding: 6px; font-size: 11px; font-weight: bold; border: 1px solid #cbd5e1; }
        .dual-col-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .dual-col-table td { vertical-align: top; width: 50%; }
        .section-table { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; }
        .section-table th { background-color: #f8fafc; padding: 6px 8px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; text-align: left; }
        .section-table th.amount { text-align: right; }
        .section-table td { padding: 5px 8px; font-size: 10px; border-bottom: 1px solid #f1f5f9; }
        .section-table td.amount { text-align: right; }
        .section-table tr.total-row td { background-color: #f8fafc; font-weight: bold; border-top: 1px solid #cbd5e1; font-size: 11px; }
        .net-pay-banner { background-color: #f0fdfa; border: 1px solid #5eead4; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .net-pay-title { font-size: 10px; color: #134e4a; text-transform: uppercase; font-weight: bold; }
        .net-pay-amount { font-size: 18px; font-weight: bold; color: #0f766e; text-align: right; }
        .footer { text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $payslip->company?->name }}</div>
        <div class="company-address">{{ $payslip->company?->registered_address ?? 'Industrial Area, Pune, Maharashtra' }}</div>
        <div class="title-badge">SALARY PAYSLIP FOR {{ strtoupper(date('F Y', mktime(0, 0, 0, $payslip->month, 1, $payslip->year))) }}</div>
    </div>

    <table class="info-table" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
        <tr>
            <td class="label">Employee ID:</td>
            <td class="value">{{ $payslip->employee?->employee_code }}</td>
            <td class="label">Date of Joining:</td>
            <td class="value">{{ $payslip->employee?->date_of_joining?->format('d M Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Employee Name:</td>
            <td class="value">{{ $payslip->employee?->full_name }}</td>
            <td class="label">Location / Plant:</td>
            <td class="value">{{ $payslip->employee?->location?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Department:</td>
            <td class="value">{{ $payslip->employee?->department?->name ?? 'N/A' }}</td>
            <td class="label">PAN Number:</td>
            <td class="value">{{ $payslip->employee?->masked_pan ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Designation:</td>
            <td class="value">{{ $payslip->employee?->designation?->name ?? 'N/A' }}</td>
            <td class="label">Payslip Number:</td>
            <td class="value">{{ $payslip->payslip_number }}</td>
        </tr>
    </table>

    <table class="attendance-box">
        <tr>
            <th>Calendar Days</th>
            <th>Worked Days</th>
            <th>Paid Leaves</th>
            <th>Weekly Offs</th>
            <th>Holidays</th>
            <th>Loss of Pay (LOP)</th>
            <th style="background-color: #ccfbf1; color: #0f766e;">Paid Days</th>
        </tr>
        <tr>
            <td>{{ $payslip->calendar_days }}</td>
            <td style="color: #0369a1;">{{ $payslip->worked_days }}</td>
            <td style="color: #0d9488;">{{ $payslip->paid_leave_days }}</td>
            <td>{{ $payslip->weekly_off_days }}</td>
            <td>{{ $payslip->holiday_days }}</td>
            <td style="color: #e11d48;">{{ $payslip->loss_of_pay_days }}</td>
            <td style="background-color: #f0fdfa; color: #0f766e; font-size: 13px;">{{ $payslip->paid_days }}</td>
        </tr>
    </table>

    <table class="dual-col-table">
        <tr>
            <td style="padding-right: 6px;">
                <table class="section-table">
                    <tr>
                        <th>Earnings Component</th>
                        <th class="amount">Amount (INR)</th>
                    </tr>
                    <tr>
                        <td>Basic Salary</td>
                        <td class="amount">{{ number_format($payslip->basic_salary, 2) }}</td>
                    </tr>
                    <tr>
                        <td>House Rent Allowance (HRA)</td>
                        <td class="amount">{{ number_format($payslip->hra, 2) }}</td>
                    </tr>
                    @if($payslip->da > 0)
                        <tr>
                            <td>Dearness Allowance (DA)</td>
                            <td class="amount">{{ number_format($payslip->da, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Special / Other Allowances</td>
                        <td class="amount">{{ number_format($payslip->allowances_amount, 2) }}</td>
                    </tr>
                    @if($payslip->overtime_amount > 0)
                        <tr>
                            <td>Overtime Pay ({{ $payslip->overtime_hours }} hrs)</td>
                            <td class="amount">{{ number_format($payslip->overtime_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Gross Earnings</td>
                        <td class="amount">{{ number_format($payslip->gross_earnings, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="padding-left: 6px;">
                <table class="section-table">
                    <tr>
                        <th>Deductions Component</th>
                        <th class="amount">Amount (INR)</th>
                    </tr>
                    <tr>
                        <td>Provident Fund (Employee)</td>
                        <td class="amount">{{ number_format($payslip->pf_employee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ESI (Employee)</td>
                        <td class="amount">{{ number_format($payslip->esi_employee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Professional Tax</td>
                        <td class="amount">{{ number_format($payslip->professional_tax, 2) }}</td>
                    </tr>
                    @if($payslip->labour_welfare_fund > 0)
                        <tr>
                            <td>Labour Welfare Fund (LWF)</td>
                            <td class="amount">{{ number_format($payslip->labour_welfare_fund, 2) }}</td>
                        </tr>
                    @endif
                    @if($payslip->income_tax_tds > 0)
                        <tr>
                            <td>Income Tax (TDS)</td>
                            <td class="amount">{{ number_format($payslip->income_tax_tds, 2) }}</td>
                        </tr>
                    @endif
                    @if($payslip->loan_emi_deduction > 0)
                        <tr>
                            <td>Loan / Advance EMI</td>
                            <td class="amount">{{ number_format($payslip->loan_emi_deduction, 2) }}</td>
                        </tr>
                    @endif
                    @if($payslip->fine_recovery_deduction > 0)
                        <tr>
                            <td>Asset Fine Recovery</td>
                            <td class="amount">{{ number_format($payslip->fine_recovery_deduction, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Deductions</td>
                        <td class="amount" style="color: #e11d48;">{{ number_format($payslip->total_deductions, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="width: 100%; background-color: #f0fdfa; border: 1px solid #5eead4; padding: 8px 12px; margin-bottom: 20px;">
        <tr>
            <td style="font-size: 11px; font-weight: bold; color: #134e4a;">
                NET SALARY PAYABLE:
            </td>
            <td style="text-align: right; font-size: 16px; font-weight: bold; color: #0f766e;">
                INR {{ number_format($payslip->net_salary, 2) }}
            </td>
        </tr>
    </table>

    <div class="footer">
        This is a system-generated document and requires no signature. • Generated by Enterprise HRMS
    </div>
</body>
</html>

