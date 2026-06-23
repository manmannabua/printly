<!-- Resume Template v1.1 -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resume - {{ $applicant->full_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 15px 30px 30px 30px; position: relative; }
        .watermark { position: fixed; top: 45%; left: 15%; font-size: 48px; color: rgba(0,0,0,0.04); transform: rotate(-35deg); z-index: -1; white-space: nowrap; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid {{ $primaryColor }}; }
        .header .photo { width: 120px; height: 120px; border-radius: 60px; margin: 0 auto 10px auto; display: block; object-fit: cover; }
        .header h1 { margin: 0 0 5px 0; font-size: 22px; color: #1e3a5f; letter-spacing: 1px; }
        .header .contact { font-size: 11px; color: #555; }
        .header .contact span { margin: 0 8px; }
        .header .contact-address { margin-top: 4px; }
        .section { margin-bottom: 18px; }
        .section h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: {{ $primaryColor }}; padding-bottom: 4px; margin: 0 0 10px 0; }
        .section hr { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0 0 0; }
        .entry { margin-bottom: 10px; page-break-inside: avoid; }
        .entry-header { display: table; width: 100%; }
        .entry-title { display: table-cell; font-weight: bold; font-size: 11px; }
        .entry-date { display: table-cell; text-align: right; font-size: 10px; color: #777; }
        .entry-subtitle { font-size: 10px; color: #555; margin-top: 1px; }
        .entry-address { font-size: 9px; color: #888; margin-top: 1px; font-style: italic; }
        .entry-meta { font-size: 9px; color: #888; margin-top: 1px; text-transform: capitalize; }
        .entry-desc { font-size: 10px; color: #444; margin-top: 3px; }
        .entry-reason { font-size: 9px; color: #777; margin-top: 3px; font-style: italic; }
        .skills-grid { display: table; width: 100%; }
        .skills-row { display: table-row; }
        .skill-category { display: table-cell; width: 130px; font-weight: bold; font-size: 10px; color: #555; padding: 3px 0; vertical-align: top; text-transform: capitalize; }
        .skill-list { display: table-cell; font-size: 10px; padding: 3px 0; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 3px 8px; font-size: 10px; vertical-align: top; }
        .info-table td.label { font-weight: bold; width: 130px; color: #555; }
        .card-grid { width: 100%; border-collapse: separate; border-spacing: 8px 8px; margin: -8px; }
        .card-cell { width: 50%; vertical-align: top; padding: 0; }
        .card { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; }
        .card-title { font-size: 11px; font-weight: bold; color: #1e3a5f; margin-bottom: 4px; padding-bottom: 3px; border-bottom: 1px solid #f3f4f6; }
        .card-row { font-size: 10px; padding: 1px 0; }
        .card-label { display: inline-block; font-weight: bold; color: #555; min-width: 80px; }
        .card-value { color: #333; }
        .cover-letter { font-size: 10px; line-height: 1.5; color: #444; white-space: pre-wrap; }
        .footer { position: fixed; bottom: 15px; left: 30px; right: 30px; font-size: 8px; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    @if($showWatermark)
        <div class="watermark">Generated via {{ $appName }} Careers</div>
    @endif

    {{-- Header --}}
    <div class="header">
        <img src="{{ $photoDataUri }}" class="photo" alt="Photo">
        <h1>{{ $applicant->full_name }}</h1>
        <div class="contact">
            @unless($hideContact)
                {{ $applicant->email }}
                @if($applicant->phone)
                    <span>|</span> {{ $applicant->phone }}
                @endif
            @endunless
            @if($applicant->current_position && $applicant->current_company)
                <span>|</span> {{ $applicant->current_position }} at {{ $applicant->current_company }}
            @elseif($applicant->current_position)
                <span>|</span> {{ $applicant->current_position }}
            @endif
        </div>
        @unless($hideContact)
            @if($applicant->address)
                <div class="contact contact-address">{{ $applicant->address }}</div>
            @endif
        @endunless
    </div>

    {{-- Personal Information --}}
    @php
        $personalRows = array_filter([
            ['Date of Birth', $applicant->date_of_birth],
            ['Birth Place',    $applicant->birth_place],
            ['Nationality',    $applicant->nationality],
            ['Gender',         $applicant->gender],
            ['Civil Status',   $applicant->civil_status],
            ['Father\'s Name', $applicant->father_name],
            ['Mother\'s Name', $applicant->mother_name],
        ], fn ($r) => !empty($r[1]));
    @endphp
    @if(!empty($personalRows))
        <div class="section">
            <h2>Personal Information</h2>
            <table class="info-table">
                @foreach($personalRows as [$label, $value])
                    <tr>
                        <td class="label">{{ $label }}</td>
                        <td style="text-transform: {{ in_array($label, ['Gender', 'Civil Status']) ? 'capitalize' : 'none' }};">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
            <hr>
        </div>
    @endif

    {{-- Government IDs --}}
    @if(!$hideContact && $governmentId && ($governmentId->tin || $governmentId->sss || $governmentId->philhealth || $governmentId->pagibig))
        <div class="section">
            <h2>Government IDs</h2>
            <table class="info-table">
                @if($governmentId->tin)
                    <tr><td class="label">TIN</td><td>{{ $governmentId->tin }}</td></tr>
                @endif
                @if($governmentId->sss)
                    <tr><td class="label">SSS</td><td>{{ $governmentId->sss }}</td></tr>
                @endif
                @if($governmentId->philhealth)
                    <tr><td class="label">PhilHealth</td><td>{{ $governmentId->philhealth }}</td></tr>
                @endif
                @if($governmentId->pagibig)
                    <tr><td class="label">HDMF (Pag-IBIG)</td><td>{{ $governmentId->pagibig }}</td></tr>
                @endif
            </table>
            <hr>
        </div>
    @endif

    {{-- Emergency Contact --}}
    @if(!$hideContact && $emergencyContact)
        <div class="section">
            <h2>Emergency Contact</h2>
            <table class="card-grid">
                <tr>
                    <td class="card-cell">
                        <div class="card">
                            <div class="card-title">{{ $emergencyContact->name }}</div>
                            @if($emergencyContact->relationship)
                                <div class="card-row"><span class="card-label">Relationship:</span> <span class="card-value">{{ $emergencyContact->relationship }}</span></div>
                            @endif
                            <div class="card-row"><span class="card-label">Mobile:</span> <span class="card-value">{{ $emergencyContact->phone_primary }}</span></div>
                            @if($emergencyContact->phone_secondary)
                                <div class="card-row"><span class="card-label">Alt. Number:</span> <span class="card-value">{{ $emergencyContact->phone_secondary }}</span></div>
                            @endif
                        </div>
                    </td>
                    <td class="card-cell"></td>
                </tr>
            </table>
            <hr>
        </div>
    @endif

    {{-- Cover Letter --}}
    @if($applicant->cover_letter)
        <div class="section">
            <h2>Cover Letter</h2>
            <div class="cover-letter">{{ $applicant->cover_letter }}</div>
            <hr>
        </div>
    @endif

    {{-- Work Experience --}}
    @if($workExperiences->isNotEmpty())
        <div class="section">
            <h2>Work Experience</h2>
            @foreach($workExperiences as $exp)
                <div class="entry">
                    <div class="entry-header">
                        <span class="entry-title">{{ $exp->job_title }}</span>
                        <span class="entry-date">
                            @if($exp->start_date){{ \Carbon\Carbon::parse($exp->start_date)->format('M Y') }}@endif
                            @if($exp->start_date && ($exp->is_current || $exp->end_date))
                                &ndash;
                            @endif
                            @if($exp->is_current)
                                Present
                            @elseif($exp->end_date)
                                {{ \Carbon\Carbon::parse($exp->end_date)->format('M Y') }}
                            @endif
                        </span>
                    </div>
                    <div class="entry-subtitle">{{ $exp->company }}</div>
                    @if($exp->company_address)
                        <div class="entry-address">{{ $exp->company_address }}</div>
                    @endif
                    @if($exp->description)
                        <div class="entry-desc">{!! strip_tags($exp->description, '<p><br><ul><ol><li><strong><em><b><i>') !!}</div>
                    @endif
                    @if($exp->reason_for_leaving)
                        <div class="entry-reason"><strong>Reason for leaving:</strong> {{ $exp->reason_for_leaving }}</div>
                    @endif
                </div>
            @endforeach
            <hr>
        </div>
    @endif

    {{-- Education --}}
    @if($educations->isNotEmpty())
        <div class="section">
            <h2>Education</h2>
            @foreach($educations as $edu)
                <div class="entry">
                    <div class="entry-header">
                        <span class="entry-title">
                            @if($edu->degree){{ $edu->degree }}@if($edu->field_of_study) in {{ $edu->field_of_study }}@endif
                            @else{{ $edu->institution }}
                            @endif
                        </span>
                        <span class="entry-date">
                            @if($edu->start_date){{ \Carbon\Carbon::parse($edu->start_date)->format('Y') }}@endif
                            @if($edu->start_date && ($edu->is_current || $edu->end_date))
                                &ndash;
                            @endif
                            @if($edu->is_current)
                                Present
                            @elseif($edu->end_date)
                                {{ \Carbon\Carbon::parse($edu->end_date)->format('Y') }}
                            @endif
                        </span>
                    </div>
                    @if($edu->degree)
                        <div class="entry-subtitle">{{ $edu->institution }}</div>
                    @endif
                    @if($edu->level || $edu->status)
                        <div class="entry-meta">{{ trim(($edu->level ?? '') . ($edu->level && $edu->status ? ' · ' : '') . ($edu->status ?? '')) }}</div>
                    @endif
                    @if($edu->description)
                        <div class="entry-desc">{{ $edu->description }}</div>
                    @endif
                </div>
            @endforeach
            <hr>
        </div>
    @endif

    {{-- Skills --}}
    @if($skillsFlat->isNotEmpty())
        <div class="section">
            <h2>Skills</h2>
            @foreach($skillsFlat as $skill)
                <table style="width:100%; margin-bottom:4px; border-collapse:collapse;">
                    <tr>
                        <td style="width:130px; font-weight:bold; font-size:10px; padding:2px 0; vertical-align:middle;">
                            {{ $skill->name }}
                            @if($skill->years_experience !== null)
                                <span style="font-weight:normal; color:#777; font-size:9px;">&middot; {{ $skill->years_experience }}y</span>
                            @endif
                        </td>
                        <td style="padding:2px 4px; vertical-align:middle;">
                            <table style="width:100%; border-collapse:collapse;">
                                <tr>
                                    <td style="width:{{ $skill->proficiency_level }}%; background:{{ $primaryColor }}; height:8px; padding:0;"></td>
                                    <td style="background:#e5e7eb; height:8px; padding:0;"></td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:35px; text-align:right; font-size:10px; color:#555; padding:2px 0; vertical-align:middle;">{{ $skill->proficiency_level }}%</td>
                    </tr>
                </table>
            @endforeach
            <hr>
        </div>
    @endif

    {{-- References --}}
    @if($references->isNotEmpty())
        <div class="section">
            <h2>References</h2>
            @php $refRows = $references->chunk(2)->values(); @endphp
            @foreach($refRows as $pair)
                <table class="card-grid">
                    <tr>
                        @foreach($pair as $ref)
                            <td class="card-cell">
                                <div class="card">
                                    <div class="card-title">{{ $ref->name }}</div>
                                    @if($ref->relationship)
                                        <div class="card-row"><span class="card-label">Relationship:</span> <span class="card-value">{{ $ref->relationship }}</span></div>
                                    @endif
                                    @if($ref->position && $ref->company)
                                        <div class="card-row"><span class="card-label">Role:</span> <span class="card-value">{{ $ref->position }} at {{ $ref->company }}</span></div>
                                    @elseif($ref->company)
                                        <div class="card-row"><span class="card-label">Company:</span> <span class="card-value">{{ $ref->company }}</span></div>
                                    @elseif($ref->position)
                                        <div class="card-row"><span class="card-label">Position:</span> <span class="card-value">{{ $ref->position }}</span></div>
                                    @endif
                                    @if($ref->email && !$hideContact)
                                        <div class="card-row"><span class="card-label">Email:</span> <span class="card-value">{{ $ref->email }}</span></div>
                                    @endif
                                    @if($ref->phone && !$hideContact)
                                        <div class="card-row"><span class="card-label">Phone:</span> <span class="card-value">{{ $ref->phone }}</span></div>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                        @if($pair->count() === 1)
                            <td class="card-cell"></td>
                        @endif
                    </tr>
                </table>
            @endforeach
        </div>
    @endif

    <div class="footer">Generated on {{ now()->format('F j, Y') }} by {{ $appName }}</div>
</body>
</html>
