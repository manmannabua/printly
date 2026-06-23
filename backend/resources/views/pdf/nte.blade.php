<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notice to Explain — {{ $nte->reference_no }}</title>
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
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold;
            background: #fee2e2; color: #991b1b; text-transform: uppercase; }
        .deadline-box { border: 1px solid #b91c1c; padding: 8px 10px; margin: 12px 0;
            background: #fef2f2; color: #991b1b; font-weight: bold; }
        .sig-block { display: table; width: 100%; margin-top: 50px; }
        .sig-col { display: table-cell; width: 50%; padding: 0 15px; }
        .sig-line { border-top: 1px solid #333; padding-top: 4px; min-height: 60px; }
        .sig-label { font-size: 10px; color: #555; margin-top: 4px; }
        .footer { margin-top: 30px; font-size: 9px; color: #888; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>

    <h1>NOTICE TO EXPLAIN</h1>
    <div class="ref">Reference No: <strong>{{ $nte->reference_no }}</strong> &nbsp;|&nbsp; Date Issued: {{ ($nte->issued_at ?? now())->format('F d, Y') }}</div>

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
                    {{ $nte->issued_by_role === 'team_leader' ? 'Team Leader' : 'Human Resources' }}
                    @if($issuerEmployee?->position) — {{ $issuerEmployee->position->name }} @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Incident Details</div>
        <table>
            <tr>
                <td class="label">Category</td>
                <td class="value"><span class="badge">{{ str_replace('_', ' ', $nte->category) }}</span></td>
            </tr>
            <tr>
                <td class="label">Date of Incident</td>
                <td class="value">{{ $nte->incident_date?->format('F d, Y') }}</td>
            </tr>
            @if($nte->incident_location)
            <tr>
                <td class="label">Location</td>
                <td class="value">{{ $nte->incident_location }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Policy or Rule Violated</div>
        <div class="body-text">{!! $nte->policy_violated !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Specific Allegations</div>
        <div class="body-text">{!! $nte->allegations !!}</div>
    </div>

    @if($nte->response_deadline_at)
    <div class="deadline-box">
        You are required to submit a written explanation on or before
        <strong>{{ $nte->response_deadline_at->format('F d, Y \a\t h:i A') }}</strong>.
        Failure to respond within this period may be taken as a waiver of your right to be heard.
    </div>
    @endif

    <div class="sig-block">
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-label">
                <strong>{{ $issuerEmployee ? trim($issuerEmployee->first_name.' '.$issuerEmployee->last_name) : $issuer->email }}</strong><br>
                Notice Issuer ({{ $nte->issued_by_role === 'team_leader' ? 'Team Leader' : 'HR' }})
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-line"></div>
            <div class="sig-label">
                <strong>{{ trim($employee->first_name.' '.$employee->last_name) }}</strong><br>
                Acknowledged by Employee
            </div>
        </div>
    </div>

    <div class="footer">
        This document is generated electronically and signed via the HRIS e-signature engine.
        Reference: {{ $nte->reference_no }}
    </div>

</body>
</html>
