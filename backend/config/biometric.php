<?php

return [
    /*
     * Static bearer token the biometric-bridge service sends in the
     * Authorization header. Required.
     */
    'api_key' => env('BIOMETRIC_API_KEY'),

    /*
     * Optional previous key to accept during rotation. Leave null once
     * the old key has fully rolled out.
     */
    'previous_api_key' => env('BIOMETRIC_PREVIOUS_API_KEY'),

    /*
     * Optional comma-separated allowlist of bridge PC IPs. Empty = any IP
     * allowed (API key is the only check).
     */
    'allowed_ips' => array_filter(array_map('trim', explode(',', (string) env('BIOMETRIC_ALLOWED_IPS', '')))),

    /*
     * Retention for resolved unmatched-punch rows, in days.
     */
    'unmatched_retention_days' => (int) env('BIOMETRIC_UNMATCHED_RETENTION_DAYS', 90),

    /*
     * Minutes of silence from a device before the heartbeat check
     * considers it "disconnected". Used by biometric:heartbeat-check.
     */
    'heartbeat_silence_minutes' => (int) env('BIOMETRIC_HEARTBEAT_SILENCE_MINUTES', 15),
];
