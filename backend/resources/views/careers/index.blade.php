<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Careers — {{ $companyName }}</title>
    <meta name="description" content="Explore career opportunities at {{ $companyName }}. Find your next role and apply today.">
    <meta property="og:title" content="Careers — {{ $companyName }}">
    <meta property="og:description" content="Explore career opportunities at {{ $companyName }}.">
    <meta property="og:type" content="website">
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak data-page="index" data-jobs="{{ json_encode($jobs->map(fn ($job) => [
        'id' => $job->id,
        'title' => $job->title,
        'slug' => $job->slug,
        'location' => $job->location,
        'is_remote' => $job->is_remote,
        'salary_range_min' => $job->show_salary ? $job->salary_range_min : null,
        'salary_range_max' => $job->show_salary ? $job->salary_range_max : null,
        'salary_currency' => $job->show_salary ? $job->salary_currency : null,
        'show_salary' => $job->show_salary,
        'published_at' => $job->published_at?->toDateTimeString(),
        'closes_at' => $job->closes_at?->toDateTimeString(),
        'department' => $job->department ? ['id' => $job->department->id, 'name' => $job->department->name] : null,
        'employment_type' => $job->employmentType ? ['id' => $job->employmentType->id, 'name' => $job->employmentType->name] : null,
    ])) }}" data-departments="{{ json_encode($departments) }}" data-employment-types="{{ json_encode($employmentTypes) }}" data-navigation="{{ json_encode($navigation) }}" data-site-settings="{{ json_encode($siteSettings) }}" data-company="{{ $companyName }}">
        {{-- Server-rendered fallback for SEO crawlers --}}
        <noscript>
            <header style="padding: 2rem; text-align: center;">
                <h1>Careers at {{ $companyName }}</h1>
                <p>Explore our open positions and find your next opportunity.</p>
            </header>
            <main style="max-width: 800px; margin: 0 auto; padding: 1rem;">
                @forelse ($jobs as $job)
                    <article style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <h2><a href="{{ careers_path('jobs/' . $job->slug) }}">{{ $job->title }}</a></h2>
                        @if($job->department)
                            <p>{{ $job->department->name }}</p>
                        @endif
                        <p>{{ $job->location ?? 'Remote' }}</p>
                        @if($job->employmentType)
                            <span>{{ $job->employmentType->name }}</span>
                        @endif
                    </article>
                @empty
                    <p>No open positions at this time. Check back soon!</p>
                @endforelse
            </main>
        </noscript>
    </div>
</body>
</html>
