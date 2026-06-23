<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>{{ $job->title }} — Careers at {{ $companyName }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($job->description), 160) }}">
    <meta property="og:title" content="{{ $job->title }} — {{ $companyName }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($job->description), 160) }}">
    <meta property="og:type" content="website">
    <script type="application/ld+json" nonce="{{ csp_nonce() }}">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak data-page="show" data-job="{{ json_encode([
        'id' => $job->id,
        'title' => $job->title,
        'slug' => $job->slug,
        'location' => $job->show_location ? $job->location : null,
        'is_remote' => $job->is_remote,
        'salary_range_min' => $job->show_salary ? $job->salary_range_min : null,
        'salary_range_max' => $job->show_salary ? $job->salary_range_max : null,
        'salary_currency' => $job->show_salary ? $job->salary_currency : null,
        'show_salary' => $job->show_salary,
        'description' => $job->description,
        'requirements' => $job->show_requirements ? $job->requirements : null,
        'benefits' => $job->show_benefits ? $job->benefits : null,
        'published_at' => $job->published_at?->toDateTimeString(),
        'closes_at' => $job->closes_at?->toDateTimeString(),
        'department' => $job->show_department && $job->department ? ['id' => $job->department->id, 'name' => $job->department->name] : null,
        'position' => $job->position ? ['id' => $job->position->id, 'title' => $job->position->title] : null,
        'employment_type' => $job->show_employment_type && $job->employmentType ? ['id' => $job->employmentType->id, 'name' => $job->employmentType->name] : null,
    ]) }}" data-navigation="{{ json_encode($navigation) }}" data-site-settings="{{ json_encode($siteSettings) }}" data-company="{{ $companyName }}">
        {{-- Server-rendered fallback for SEO crawlers --}}
        <noscript>
            <header style="padding: 2rem; text-align: center; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white;">
                <h1 style="margin: 0 0 0.5rem;">{{ $job->title }}</h1>
                <p style="margin: 0;"><a href="{{ careers_path() }}" style="color: #e0e7ff;">&larr; Back to all positions</a></p>
            </header>
            <main style="max-width: 800px; margin: 0 auto; padding: 1rem;">
                <article>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        @if($job->department)
                            <span style="background: #eff6ff; color: #1d4ed8; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem;">{{ $job->department->name }}</span>
                        @endif
                        @if($job->employmentType)
                            <span style="background: #f3f4f6; color: #374151; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem;">{{ $job->employmentType->name }}</span>
                        @endif
                        @if($job->is_remote)
                            <span style="background: #f0fdf4; color: #15803d; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem;">Remote</span>
                        @endif
                    </div>
                    <p><strong>Location:</strong> {{ $job->location ?? 'Remote' }}</p>
                    @if($job->show_salary && $job->salary_range_min)
                        <p><strong>Salary:</strong> {{ $job->salary_currency }} {{ number_format($job->salary_range_min) }} - {{ number_format($job->salary_range_max) }}</p>
                    @endif

                    <h2>Description</h2>
                    <div>{!! nl2br(e($job->description)) !!}</div>

                    <h2>Requirements</h2>
                    <div>{!! nl2br(e($job->requirements)) !!}</div>

                    @if($job->benefits)
                        <h2>Benefits</h2>
                        <div>{!! nl2br(e($job->benefits)) !!}</div>
                    @endif

                    <p style="margin-top: 2rem;">
                        <a href="{{ careers_path('jobs/' . $job->slug . '/apply') }}" style="display: inline-block; padding: 0.75rem 1.5rem; background: #4f46e5; color: white; border-radius: 0.5rem; text-decoration: none;">Apply Now</a>
                    </p>
                </article>
            </main>
        </noscript>
    </div>
</body>
</html>
