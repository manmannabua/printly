<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Application Submitted — {{ $companyName }}</title>
    <meta name="robots" content="noindex">
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak data-page="confirmation" data-navigation="{{ json_encode($navigation) }}" data-site-settings="{{ json_encode($siteSettings) }}" data-company="{{ $companyName }}">
        <noscript>
            <div style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 2rem;">
                <h1>Application Submitted</h1>
                <p>Thank you for your application! We have received your submission and will review it shortly.</p>
                <p><a href="{{ careers_path() }}">Browse more positions</a></p>
            </div>
        </noscript>
    </div>
</body>
</html>
