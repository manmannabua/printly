<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Contact Us — {{ $companyName }}</title>
    <meta name="description" content="Get in touch with {{ $companyName }}. We'd love to hear from you.">
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak data-page="contact" data-navigation="{{ json_encode($navigation) }}" data-site-settings="{{ json_encode($siteSettings) }}" data-company="{{ $companyName }}">
        <noscript>
            <div style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 2rem;">
                <h1>Contact Us</h1>
                <p>Please enable JavaScript to use our contact form, or reach out to us via the contact information listed on our website.</p>
                <p><a href="{{ careers_path() }}">Back to Careers</a></p>
            </div>
        </noscript>
    </div>
</body>
</html>
