<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Pay Statement — {{ $employee->full_name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; padding: 30px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; font-size: 11px; color: #555; margin-bottom: 20px; }
        .section { margin-bottom: 16px; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 6px; }
        th { background: #f0f0f0; font-weight: bold; text-align: left; }
        .label-col { width: 55%; }
        .amount-col { width: 45%; text-align: right; }
        .total-row td { border-top: 1px solid #999; font-weight: bold; padding-top: 6px; }
        .net-row td { background: #1a1a1a; color: #fff; font-weight: bold; font-size: 12px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 40%; font-weight: bold; padding: 2px 0; }
        .info-value { display: table-cell; padding: 2px 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-draft    { background: #e5e7eb; color: #374151; }
        .badge-computed { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-released { background: #dbeafe; color: #1e40af; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; text-align: center; }
        .sig-row { display: table; width: 100%; margin-top: 40px; }
        .sig-col { display: table-cell; width: 50%; padding: 0 10px; text-align: center; }
        .sig-line { border-top: 1px solid #333; margin: 0 auto; width: 80%; margin-top: 40px; padding-top: 4px; }
    </style>
</head>
<body>

    <h1>FINAL PAY STATEMENT</h1>
    <div class="subtitle">{{ $employee->client?->name ?? 'Company' }}</div>

    {{-- Employee Info --}}
    <div class="section">
        <div class="section-title">Employee Information</div>
        <table>
            <tr>
                <td class="label-col"><strong>Employee Name</strong></td>
                <td>{{ $employee->full_name }}</td>
                <td class="label-col"><strong>Employee No.</strong></td>
                <td>{{ $employee->employee_number }}</td>
            </tr>
            <tr>
                <td><strong>Position</strong></td>
                <td>{{ $employee->position?->name ?? '—' }}</td>
                <td><strong>Department</strong></td>
                <td>{{ $employee->department?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td><strong>Hire Date</strong></td>
                <td>{{ \Carbon\Carbon::parse($employee->hire_date)->format('F d, Y') }}</td>
                <td><strong>Separation Type</strong></td>
                <td>{{ ucwords(str_replace('_', ' ', $employee->separation_type ?? '—')) }}</td>
            </tr>
            <tr>
                <td><strong>Termination Date</strong></td>
                <td>{{ $employee->termination_date ? \Carbon\Carbon::parse($employee->termination_date)->format('F d, Y') : '—' }}</td>
                <td><strong>Last Working Date</strong></td>
                <td>{{ $employee->last_working_date ? \Carbon\Carbon::parse($employee->last_working_date)->format('F d, Y') : '—' }}</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td colspan="3">
                    <span class="badge badge-{{ $record->status }}">{{ strtoupper($record->status) }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Earnings --}}
    <div class="section">
        <div class="section-title">Earnings / Additions</div>
        <table>
            <tr>
                <th class="label-col">Description</th>
                <th class="amount-col">Days / Units</th>
                <th class="amount-col">Amount (PHP)</th>
            </tr>
            <tr>
                <td>Last Salary (Prorated)</td>
                <td style="text-align:right">{{ $record->last_salary_days }} days</td>
                <td style="text-align:right">{{ number_format((float)$record->last_salary_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Leave Encashment</td>
                <td style="text-align:right">{{ number_format((float)$record->leave_encashment_days, 2) }} days</td>
                <td style="text-align:right">{{ number_format((float)$record->leave_encashment_amount, 2) }}</td>
            </tr>
            <tr>
                <td>13th Month Pay (Prorated)</td>
                <td style="text-align:right">—</td>
                <td style="text-align:right">{{ number_format((float)$record->thirteenth_month_amount, 2) }}</td>
            </tr>
            @if((float)$record->separation_pay_amount > 0)
            <tr>
                <td>Separation Pay</td>
                <td style="text-align:right">—</td>
                <td style="text-align:right">{{ number_format((float)$record->separation_pay_amount, 2) }}</td>
            </tr>
            @endif
            @if((float)$record->other_additions > 0)
            <tr>
                <td>Other Additions @if($record->other_additions_notes)<br><small style="color:#555">{{ $record->other_additions_notes }}</small>@endif</td>
                <td style="text-align:right">—</td>
                <td style="text-align:right">{{ number_format((float)$record->other_additions, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="2"><strong>Gross Final Pay</strong></td>
                <td style="text-align:right"><strong>{{ number_format((float)$record->gross_final_pay, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- Deductions --}}
    <div class="section">
        <div class="section-title">Deductions</div>
        <table>
            <tr>
                <th class="label-col">Description</th>
                <th class="amount-col">Amount (PHP)</th>
            </tr>
            @if((float)$record->outstanding_loan_amount > 0)
            <tr>
                <td>Outstanding Loan Balance</td>
                <td style="text-align:right">{{ number_format((float)$record->outstanding_loan_amount, 2) }}</td>
            </tr>
            @endif
            @if((float)$record->tax_due > 0)
            <tr>
                <td>Withholding Tax</td>
                <td style="text-align:right">{{ number_format((float)$record->tax_due, 2) }}</td>
            </tr>
            @endif
            @if((float)$record->other_deductions > 0)
            <tr>
                <td>Other Deductions @if($record->other_deductions_notes)<br><small style="color:#555">{{ $record->other_deductions_notes }}</small>@endif</td>
                <td style="text-align:right">{{ number_format((float)$record->other_deductions, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td><strong>Total Deductions</strong></td>
                <td style="text-align:right"><strong>{{ number_format((float)$record->total_deductions, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- Net Final Pay --}}
    <table style="margin-bottom:20px">
        <tr class="net-row">
            <td class="label-col" style="padding:8px 6px">NET FINAL PAY</td>
            <td style="text-align:right; padding:8px 6px; font-size:14px">
                PHP {{ number_format((float)$record->net_final_pay, 2) }}
            </td>
        </tr>
    </table>

    @if($record->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <p style="font-size:10px; color:#555">{{ $record->notes }}</p>
    </div>
    @endif

    {{-- Signature Block --}}
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-line">
                <strong>Prepared by</strong><br>
                {{ $record->computedByUser?->employee?->full_name ?? $record->computedByUser?->email ?? '—' }}<br>
                <small>{{ $record->computed_at?->format('F d, Y') }}</small>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-line">
                <strong>Approved by</strong><br>
                {{ $record->approvedByUser?->employee?->full_name ?? $record->approvedByUser?->email ?? '—' }}<br>
                <small>{{ $record->approved_at?->format('F d, Y') ?? 'Pending' }}</small>
            </div>
        </div>
    </div>

    <div class="footer">
        Generated: {{ $generatedAt }} &nbsp;|&nbsp; This document is system-generated and confidential.
    </div>

</body>
</html>
