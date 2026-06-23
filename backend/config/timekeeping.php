<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | Enable/disable the idempotency middleware for clock endpoints.
    | When disabled, the middleware is a passthrough (no dedup check).
    |
    */

    'idempotency_enabled' => env('IDEMPOTENCY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Late-Sync Thresholds
    |--------------------------------------------------------------------------
    |
    | Entries synced more than late_sync_hours after client_recorded_at are
    | flagged for admin review. Entries older than max_sync_hours are rejected.
    |
    */

    'late_sync_hours' => (int) env('LATE_SYNC_HOURS', 24),

    'max_sync_hours' => (int) env('MAX_SYNC_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum clock actions per minute per authenticated user.
    |
    */

    'rate_limit_per_minute' => (int) env('CLOCK_RATE_LIMIT', 20),

    /*
    |--------------------------------------------------------------------------
    | Geolocation
    |--------------------------------------------------------------------------
    |
    | When enabled, the PWA will capture GPS coordinates on each clock action.
    | Coordinates are optional — clock actions always succeed even if GPS is
    | denied or unavailable. Enforcement is controlled per-employee via the
    | geolocation_required flag on the employees table.
    |
    */

    'photo_max_size_kb' => (int) env('TIMEKEEPING_PHOTO_MAX_SIZE_KB', 500),

    'photo_retention_days' => (int) env('TIMEKEEPING_PHOTO_RETENTION_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Photo Archive (before retention cleanup)
    |--------------------------------------------------------------------------
    |
    | When set, the cleanup command copies each expiring photo to this
    | filesystem disk (e.g. a cold-storage bucket) with a JSON metadata
    | sidecar before deleting it from the primary disk. Set to null to
    | skip archiving entirely.
    |
    */

    'photo_archive_disk' => env('TIMEKEEPING_PHOTO_ARCHIVE_DISK'),

];
