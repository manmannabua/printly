<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <title>Update your application — {{ $companyName }}</title>
    <meta name="description" content="Update your application at {{ $companyName }}.">
    <meta name="robots" content="noindex">
    <meta name="careers-base" content="{{ careers_path() === '/' ? '' : careers_path() }}">
    <style>[v-cloak] { display: none; }</style>
    @vite('resources/js/careers/main.ts')
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="careers-app" v-cloak data-page="edit" data-edit-token="{{ $token }}" data-navigation="{{ json_encode($navigation) }}" data-site-settings="{{ json_encode($siteSettings) }}" data-company="{{ $companyName }}">
        <noscript>
            <div style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 2rem;">
                <h1>Update your application</h1>
                <p>JavaScript is required to edit your application. Please enable JavaScript and reload this page.</p>
            </div>
        </noscript>
    </div>
</body>
</html>
