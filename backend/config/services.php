<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'maps_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],

    'firebase' => [
        // FCM HTTP v1 — service account JSON path from Firebase Console → Project Settings → Service Accounts
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform (hris-provision) — custom domain callbacks
    |--------------------------------------------------------------------------
    |
    | The hris-provision platform sends signed requests to this VM when the
    | client adds/removes a custom domain. The shared HMAC secret and the
    | platform URL are baked into each client VM's .env at provision time.
    |
    */
    'platform' => [
        'hmac_secret' => env('PLATFORM_HMAC_SECRET'),
        'url'         => env('PLATFORM_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TURN Server (coturn) — WebRTC NAT traversal for voice calls
    |--------------------------------------------------------------------------
    |
    | Set TURN_SECRET to your coturn static-auth-secret.
    | If TURN_HOST is empty, the API returns only STUN servers (development mode).
    |
    */
    'turn' => [
        'host'       => env('TURN_HOST', ''),
        'port'       => env('TURN_PORT', 3478),
        'tls_port'   => env('TURN_TLS_PORT', 5349),
        'secret'     => env('TURN_SECRET', ''),
        'ttl'        => env('TURN_TTL', 86400),
    ],

];
