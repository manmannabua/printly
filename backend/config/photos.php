<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profile photo signed-URL TTL
    |--------------------------------------------------------------------------
    |
    | How long a signed profile-photo URL remains valid. Each API response
    | embeds a freshly-signed URL via Employee::getProfilePhotoUrlAttribute,
    | so a leaked URL (logs, screenshots, mailed reports) becomes harmless
    | once the TTL elapses. Browsers will refetch a new URL on the next
    | resource fetch.
    |
    */
    'profile_url_ttl_seconds' => (int) env('PROFILE_PHOTO_URL_TTL_SECONDS', 3600),
];
