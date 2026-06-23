<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice of Offense — {{ $noo->reference_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; padding: 36px; line-height: 1.45; }
        h1 { font-size: 18px; text-align: center; letter-spacing: 1px; margin-bottom: 4px; }
        .ref { text-align: center; font-size: 10px; color: #555; margin-bottom: 22px; }
        .section { margin-bottom: 14px; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase;
            letter-spacing: 0.5px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 6px; vertical-align: top; }
        .label { width: 28%; font-weight: bold; }
        .value { width: 72%; }
        .body-text { font-size: 11px; text-align: justify; }
        .body-text p { margin-bottom: 5px; }
        .body-text ul, .body-text ol { margin: 4px 0 4px 20px; padding-left: 8px; }
        .body-text ul li { list-style-type: disc; margin-bottom: 2px; }
        .body-text ol li { list-style-type: decimal; margin-bottom: 2px; }
        .body-text h2 { font-size: 13px; font-weight: bold; margin: 6px 0 3px; }
        .body-text h3 { font-size: 12px; font-weight: bold; margin: 4px 0 2px; }
        .body-text strong { font-weight: bold; }
        .body-text em { font-style: italic; }
        .action-box { border: 1px solid #92400e; padding: 8px 10px; margin: 12px 0;
            background: #fffbeb; color: #78350f; font-weight: bold; font-size: 11px; }
        .action-box p { margin-bottom: 4px; }
        .action-box ul, .action-box ol { margin: 4px 0 4px 20px; padding-left: 8px; }
        .action-box ul li { list-style-type: disc; margin-bottom: 2px; }
        .action-box ol li { list-style-type: decimal; margin-bottom: 2px; }
        .nte-ref { font-size: 10px; color: #555; font-style: italic; margin-top: 4px; }
        .sig-block { display: table; width: 100%; margin-top: 50px; }
        .sig-col { display: table-cell; width: 33.33%; padding: 0 10px; }
        .sig-col:first-child { padding-left: 0; }
        .sig-col:last-child { padding-right: 0; }
        .sig-line { border-top: 1px solid #333; padding-top: 4px; min-height: 60px; }
        .sig-label { font-size: 10px; color: #555; margin-top: 4px; }
        .footer { margin-top: 30px; font-size: 9px; color: #888; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>

    <h1>NOTICE OF OFFENSE</h1>
    <div class="ref">Reference No: <strong>{{ $noo->reference_no }}</strong> &nbsp;|&nbsp; Date Issued: {{ ($noo->issued_at ?? now())->format('F d, Y') }}</div>

    <div class="section">
        <div class="section-title">Issued To</div>
        <table>
            <tr>
                <td class="label">Name</td>
                <td class="value">{{ trim($employee->first_name.' '.$employee->last_name) }}</td>
            </tr>
            <tr>
                <td class="label">Employee No.</td>
                <td class="value">{{ $employee->employee_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Position</td>
                <td class="value">{{ $employee->position?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value">{{ $employee->department?->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Issued By</div>
        <table>
            <tr>
                <td class="label">Name</td>
                <td class="value">
                    @if($issuerEmployee)
                        {{ trim($issuerEmployee->first_name.' '.$issuerEmployee->last_name) }}
                    @else
                        {{ $issuer->email }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Role</td>
                <td class="value">
                    {{ $noo->issued_by_role === 'team_leader' ? 'Team Leader' : 'Human Resources' }}
                    @if($issuerEmployee?->position) — {{ $issuerEmployee->position->name }} @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Offense Committed</div>
        <div class="body-text">{!! $noo->offense_committed !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Disciplinary Action</div>
        <div class="action-box">{!! $noo->disciplinary_action !!}</div>
    </div>

    @if($noo->expectations)
    <div class="section">
        <div class="section-title">Expectations Going Forward</div>
        <div class="body-text">{!! $noo->expectations !!}</div>
    </div>
    @endif

    @if($nte)
    <div class="nte-ref">
        This Notice of Offense is issued in connection with Notice to Explain
        <strong>{{ $nte->reference_no }}</strong>
        @if($nte->issued_at) dated {{ $nte->issued_at->format('F d, Y') }} @endif.
    </div>
    @endif

    <div class="section" style="margin-top: 20px;">
        <div class="section-title">Acknowledgment Receipt</div>
        <p style="font-size: 11px;">
            By signing below, the employee acknowledges receipt of this Notice of Offense and understands
            the disciplinary action stated herein. This acknowledgment does not necessarily constitute
            agreement with the contents of this notice.
        </p>
    </div>

    <div class="sig-block">
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-label">
                <strong>{{ $issuerEmployee ? trim($issuerEmployee->first_name.' '.$issuerEmployee->last_name) : $issuer->email }}</strong><br>
                Prepared By<br>
                {{ $noo->issued_by_role === 'team_leader' ? 'Team Leader' : 'HR' }}
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-label">
                <strong>{{ trim($employee->first_name.' '.$employee->last_name) }}</strong><br>
                Acknowledged and Received By<br>
                Employee
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-label">
                &nbsp;<br>
                Copy Received by HRD
            </div>
        </div>
    </div>

    <div class="footer">
        This document is generated electronically and signed via the HRIS e-signature engine.
        Reference: {{ $noo->reference_no }}
    </div>

</body>
</html>
