<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Apply — {{ $job->title }} — {{ $companyName }}</title>
    <meta name="description" content="Apply for {{ $job->title }} at {{ $companyName }}.">
    <meta name="robots" content="noindex">
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak data-page="apply" data-job="{{ json_encode([
        'id' => $job->id,
        'title' => $job->title,
        'slug' => $job->slug,
        'location' => $job->location,
        'closes_at' => $job->closes_at?->toDateTimeString(),
        'department' => $job->department ? ['name' => $job->department->name] : null,
    ]) }}" data-navigation="{{ json_encode($navigation) }}" data-site-settings="{{ json_encode($siteSettings) }}" data-company="{{ $companyName }}" data-resume-upload-enabled="{{ $resumeUploadEnabled ? '1' : '0' }}">
        <noscript>
            <div style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 2rem;">
                <h1>Apply for {{ $job->title }}</h1>
                <p>JavaScript is required to submit your application. Please enable JavaScript and reload this page.</p>
                <p><a href="{{ careers_path('jobs/' . $job->slug) }}">&larr; Back to job details</a></p>
            </div>
        </noscript>
    </div>
</body>
</html>
