<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * Production hosts come from FRONTEND_URL / CAREERS_URL. We filter out
     * any non-HTTPS entries in production so a misconfigured env can't
     * silently allow plaintext origins on a real deployment.
     */
    'allowed_origins' => array_values(array_filter(
        [
            env('FRONTEND_URL', 'http://localhost:5173'),
            env('CAREERS_URL'),
            env('MOBILE_WEB_URL'),
        ],
        function (?string $origin): bool {
            if (!$origin) {
                return false;
            }
            if (env('APP_ENV') === 'production') {
                return str_starts_with($origin, 'https://');
            }
            return true;
        },
    )),

    /*
     * Local-dev allowlist. Only the actual SPA dev ports — not any random
     * localhost port. Add more here if you spin up additional dev servers.
     */
    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : [
            '#^http://(localhost|127\.0\.0\.1):(5173|5174|5190|3000|7357|9090|8091|3456)$#',
            '#^http://10\.0\.2\.2:8000$#',
        ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
