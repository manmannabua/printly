<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calendar Feature Toggle
    |--------------------------------------------------------------------------
    |
    | Master switch for the calendar module. When disabled, calendar endpoints
    | return 404 and sync hooks are no-ops.
    |
    */

    'enabled' => (bool) env('CALENDAR_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Calendar Sync Toggle
    |--------------------------------------------------------------------------
    |
    | Controls whether domain events (leave requests, attendance update
    | requests) automatically sync to the calendar_events table.
    |
    */

    'sync_enabled' => (bool) env('CALENDAR_SYNC_ENABLED', false),

];
