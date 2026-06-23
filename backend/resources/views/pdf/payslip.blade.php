<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        @page { margin: 30px 40px; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        .page-wrapper {
            width: 420px;
            margin: 0 auto;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
        }
        .header .company-name {
            font-weight: bold;
            font-size: 12px;
        }
        .separator {
            border: none;
            border-top: 1px solid #000;
            margin: 8px 0;
        }
        .info-section {
            margin-bottom: 12px;
        }
        .info-line {
            white-space: pre;
        }
        table.receipt {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
        }
        table.receipt td {
            padding: 0;
            vertical-align: top;
            line-height: 1.4;
        }
        table.receipt .lbl {
            text-align: left;
        }
        table.receipt .sub-amt {
            text-align: right;
            width: 80px;
        }
        table.receipt .main-amt {
            text-align: right;
            width: 95px;
        }
        table.receipt .indent1 .lbl { padding-left: 20px; }
        table.receipt .indent2 .lbl { padding-left: 40px; }
        .underline-amt {
            text-decoration: underline;
        }
        .section-gap td {
            padding-top: 10px;
        }
        .final-line td {
            padding-top: 10px;
            font-weight: bold;
            font-size: 13px;
        }
        .final-line .main-amt {
            border-top: 2px solid #000;
            border-bottom: 3px double #000;
            padding-top: 4px;
        }
        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .generated-at {
            margin-top: 4px;
            font-size: 8px;
            color: #999;
            text-align: center;
        }
        @if($showWatermark)
        .watermark {
            position: fixed;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 64px;
            font-weight: bold;
            color: rgba(200, 200, 200, 0.25);
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
            letter-spacing: 8px;
            text-transform: uppercase;
        }
        @endif
    </style>
</head>
<body>
    @if($showWatermark)
    <div class="watermark">CONFIDENTIAL</div>
    @endif

    <div class="page-wrapper">
        {{-- Company Header --}}
        <div class="header">
            <div class="company-name">{{ $companySettings['company_name'] ?? 'Company Name' }}</div>
            @if(!empty($companySettings['company_address_line1']))
                <div>{{ $companySettings['company_address_line1'] }}</div>
            @endif
            @if(!empty($companySettings['company_address_line2']))
                <div>{{ $companySettings['company_address_line2'] }}</div>
            @endif
            @if(!empty($companySettings['company_city']))
                <div>{{ $companySettings['company_city'] }}</div>
            @endif
            @if(!empty($companySettings['company_phone']))
                <div>Tel: {{ $companySettings['company_phone'] }}</div>
            @endif
        </div>

        <hr class="separator">

        {{-- Employee Info --}}
        <div class="info-section">
            <div class="info-line">ID Number :     {{ $employee->employee_number }}</div>
            <div class="info-line">Employee Name : {{ $employee->last_name }}, {{ $employee->first_name }} {{ substr($employee->middle_name ?? '', 0, 1) }}{{ ($employee->middle_name ? '.' : '') }}</div>
            <div class="info-line">Pay Period :    {{ $payrollRun->pay_date ? $payrollRun->pay_date->format('F d, Y') : $payrollRun->period_end->format('F d, Y') }}</div>
            <div class="info-line">Department :    {{ $employee->department->name ?? 'N/A' }}</div>
            <div class="info-line">Workgroup :     {{ $employee->team->name ?? 'N/A' }}</div>
        </div>

        <hr class="separator">

        {{-- Pay Body --}}
        <table class="receipt">
            {{-- Basic Pay Section --}}
            @php
                $basicPay = $earnings->where('code', 'BASIC')->first();
            @endphp
            <tr>
                <td class="lbl">Basic Monthly Pay</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->basic_salary, 2) }}</td>
            </tr>
            <tr>
                <td class="lbl">Hourly Rate</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->hourly_rate, 2) }}</td>
            </tr>
            <tr>
                <td class="lbl">Basic Pay</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) ($basicPay ? $basicPay->amount : $payslip->gross_salary), 2) }}</td>
            </tr>

            {{-- Other Earnings (taxable allowances, salary components) --}}
            @foreach($earnings->filter(fn($e) => $e->code !== 'BASIC') as $item)
            <tr class="indent1">
                <td class="lbl">{{ strtoupper($item->label ?? $item->code) }}</td>
                <td class="sub-amt">{{ number_format((float) $item->amount, 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- Add (premiums / OT) --}}
            <tr>
                <td class="lbl">Add</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->premiums_total, 2) }}</td>
            </tr>
            @foreach($premiumRows as $row)
            <tr class="indent1">
                <td class="lbl">{{ $row['label'] }}</td>
                <td class="sub-amt">{{ number_format($row['amount'], 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- Taxable Bonuses --}}
            <tr>
                <td class="lbl">Taxable Bonuses</td>
                <td class="sub-amt"></td>
                <td class="main-amt underline-amt">{{ number_format((float) $payslip->bonuses_taxable_total, 2) }}</td>
            </tr>

            {{-- Gross Pay --}}
            <tr>
                <td class="lbl">Gross Pay</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->gross_salary, 2) }}</td>
            </tr>

            {{-- Less (Government EE Contributions) --}}
            <tr>
                <td class="lbl">Less</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->government_deductions_total, 2) }}</td>
            </tr>
            @foreach($govRows as $row)
            <tr class="indent1">
                <td class="lbl">{{ $row['label'] }}</td>
                <td class="sub-amt">{{ number_format($row['amount'], 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- Attendance Deductions --}}
            <tr class="indent1">
                <td class="lbl">ATTENDANCE</td>
                <td class="sub-amt">{{ number_format((float) $payslip->attendance_deductions_total, 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @foreach($attendanceRows as $row)
            <tr class="indent2">
                <td class="lbl">{{ $row['label'] }}</td>
                <td class="sub-amt">{{ number_format($row['amount'], 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- Taxable Pay --}}
            <tr class="section-gap">
                <td class="lbl">TAXABLE PAY</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->taxable_income, 2) }}</td>
            </tr>

            {{-- Withholding Tax --}}
            <tr>
                <td class="lbl">Less: WTAX</td>
                <td class="sub-amt"></td>
                <td class="main-amt underline-amt">{{ number_format((float) $payslip->withholding_tax, 2) }}</td>
            </tr>

            {{-- Net Pay After Tax --}}
            <tr>
                <td class="lbl">NET PAY AFTER TAX</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->net_pay_after_tax, 2) }}</td>
            </tr>

            {{-- Less Others (post-tax deductions) --}}
            <tr>
                <td class="lbl">Less Others:</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->other_deductions, 2) }}</td>
            </tr>
            @foreach($postTaxDeductions as $item)
            <tr class="indent1">
                <td class="lbl">{{ strtoupper($item->code ?? $item->label) }}</td>
                <td class="sub-amt">{{ number_format(abs((float) $item->amount), 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- De Minimis --}}
            @php
                $deMinimisDisplayTotal = (float) $payslip->de_minimis_total + $nonTaxableEarnings->sum(fn($e) => (float) $e->amount);
            @endphp
            <tr>
                <td class="lbl">Add: DE-MINIMIS</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format($deMinimisDisplayTotal, 2) }}</td>
            </tr>
            @foreach($nonTaxableEarnings as $item)
            <tr class="indent1">
                <td class="lbl">{{ strtoupper($item->label ?? $item->code) }}</td>
                <td class="sub-amt">{{ number_format((float) $item->amount, 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach
            @foreach($deMinimis as $item)
            <tr class="indent1">
                <td class="lbl">{{ strtoupper($item->code ?? $item->label) }}</td>
                <td class="sub-amt">{{ number_format((float) $item->amount, 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- ADD/(LESS) --}}
            <tr>
                <td class="lbl">ADD/(LESS)</td>
                <td class="sub-amt"></td>
                <td class="main-amt underline-amt">{{ number_format((float) $payslip->excess_de_minimis_total, 2) }}</td>
            </tr>
            @foreach($excessDeMinimis as $item)
            <tr class="indent1">
                <td class="lbl">{{ strtoupper($item->code ?? $item->label) }}</td>
                <td class="sub-amt">{{ number_format((float) $item->amount, 2) }}</td>
                <td class="main-amt"></td>
            </tr>
            @endforeach

            {{-- Final Pay --}}
            <tr class="final-line">
                <td class="lbl">FINAL PAY</td>
                <td class="sub-amt"></td>
                <td class="main-amt">{{ number_format((float) $payslip->final_pay, 2) }}</td>
            </tr>
        </table>

        <div class="footer">
            This is a system-generated payslip. For questions, contact your HR department.
        </div>
        <div class="generated-at">
            Generated: {{ $generatedAt }}
        </div>
    </div>
</body>
</html>
