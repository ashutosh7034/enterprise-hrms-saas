<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>FNF Statement - {{ $settlement->settlement_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 15px; }
        .company-name { font-size: 18px; font-weight: bold; color: #0f172a; text-transform: uppercase; margin-bottom: 4px; }
        .company-address { font-size: 10px; color: #64748b; margin-bottom: 6px; }
        .title-badge { font-size: 12px; font-weight: bold; color: #0d9488; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; }
        .info-table td { padding: 5px 8px; font-size: 10px; }
        .info-table .label { color: #64748b; width: 22%; }
        .info-table .value { font-weight: bold; width: 28%; }
        .dual-col-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .dual-col-table td { vertical-align: top; width: 50%; }
        .section-table { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; }
        .section-table th { background-color: #f8fafc; padding: 6px 8px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; text-align: left; }
        .section-table th.amount { text-align: right; }
        .section-table td { padding: 5px 8px; font-size: 10px; border-bottom: 1px solid #f1f5f9; }
        .section-table td.amount { text-align: right; }
        .section-table tr.total-row td { background-color: #f8fafc; font-weight: bold; border-top: 1px solid #cbd5e1; font-size: 11px; }
        .net-banner { padding: 10px 14px; border-radius: 4px; margin-bottom: 25px; }
        .net-payable { background-color: #f0fdfa; border: 1px solid #5eead4; }
        .net-recoverable { background-color: #fff1f2; border: 1px solid #fecdd3; }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 40px; text-align: center; }
        .sign-table td { width: 33.33%; padding-top: 30px; font-size: 10px; color: #64748b; }
        .sign-line { border-top: 1px solid #94a3b8; width: 80%; margin: 0 auto; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settlement->company?->name }}</div>
        <div class="company-address">{{ $settlement->company?->registered_address ?? 'Industrial Area, Pune, Maharashtra' }}</div>
        <div class="title-badge">FULL & FINAL SETTLEMENT & EXIT CLEARANCE STATEMENT</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Settlement Number:</td>
            <td class="value">{{ $settlement->settlement_number }}</td>
            <td class="label">Resignation Date:</td>
            <td class="value">{{ $settlement->resignation_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Employee ID:</td>
            <td class="value">{{ $settlement->employee?->employee_code }}</td>
            <td class="label">Last Working Day:</td>
            <td class="value">{{ $settlement->last_working_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Employee Name:</td>
            <td class="value">{{ $settlement->employee?->full_name }}</td>
            <td class="label">Notice Required / Served:</td>
            <td class="value">{{ $settlement->notice_period_required_days }}d / {{ $settlement->notice_period_served_days }}d</td>
        </tr>
        <tr>
            <td class="label">Department / Designation:</td>
            <td class="value">{{ $settlement->employee?->department?->name }} / {{ $settlement->employee?->designation?->name }}</td>
            <td class="label">Status:</td>
            <td class="value">{{ $settlement->status }}</td>
        </tr>
    </table>

    <table class="dual-col-table">
        <tr>
            <td style="padding-right: 6px;">
                <table class="section-table">
                    <tr>
                        <th>Settlement Payable Dues</th>
                        <th class="amount">Amount (INR)</th>
                    </tr>
                    <tr>
                        <td>Unpaid Salary ({{ $settlement->unpaid_salary_days }} days)</td>
                        <td class="amount">{{ number_format($settlement->unpaid_salary_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Leave Encashment ({{ $settlement->leave_encashment_days }} PL days)</td>
                        <td class="amount">{{ number_format($settlement->leave_encashment_amount, 2) }}</td>
                    </tr>
                    @if($settlement->gratuity_amount > 0)
                        <tr>
                            <td>Gratuity Payout</td>
                            <td class="amount">{{ number_format($settlement->gratuity_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Gross Settlement Dues</td>
                        <td class="amount">{{ number_format($settlement->gross_settlement_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="padding-left: 6px;">
                <table class="section-table">
                    <tr>
                        <th>Exit Deductions & Offsets</th>
                        <th class="amount">Amount (INR)</th>
                    </tr>
                    @if($settlement->notice_shortfall_deduction > 0)
                        <tr>
                            <td>Notice Period Shortfall ({{ $settlement->notice_shortfall_days }}d)</td>
                            <td class="amount">{{ number_format($settlement->notice_shortfall_deduction, 2) }}</td>
                        </tr>
                    @endif
                    @if($settlement->loan_outstanding_deduction > 0)
                        <tr>
                            <td>Outstanding Loan Principal</td>
                            <td class="amount">{{ number_format($settlement->loan_outstanding_deduction, 2) }}</td>
                        </tr>
                    @endif
                    @if($settlement->asset_damage_fine_deduction > 0)
                        <tr>
                            <td>Asset Damage / Fine Clearance</td>
                            <td class="amount">{{ number_format($settlement->asset_damage_fine_deduction, 2) }}</td>
                        </tr>
                    @endif
                    @if($settlement->other_deductions_amount > 0)
                        <tr>
                            <td>Other Exit Deductions</td>
                            <td class="amount">{{ number_format($settlement->other_deductions_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Deductions</td>
                        <td class="amount" style="color: #e11d48;">{{ number_format($settlement->total_deductions_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="width: 100%;" class="net-banner {{ $settlement->settlement_type === 'Payable to Employee' ? 'net-payable' : 'net-recoverable' }}">
        <tr>
            <td style="font-weight: bold; font-size: 11px; text-transform: uppercase;">
                {{ $settlement->settlement_type }}:
            </td>
            <td style="text-align: right; font-size: 16px; font-weight: bold; {{ $settlement->settlement_type === 'Payable to Employee' ? 'color: #0f766e;' : 'color: #e11d48;' }}">
                INR {{ number_format($settlement->net_settlement_amount, 2) }}
            </td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-line">Employee Signature</div>
            </td>
            <td>
                <div class="sign-line">HR Verified</div>
            </td>
            <td>
                <div class="sign-line">Finance Approved</div>
            </td>
        </tr>
    </table>
</body>
</html>

