<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Portal Client ID
    |--------------------------------------------------------------------------
    |
    | The client ID whose job postings are displayed on the public careers
    | portal. If null, the public portal returns 503 (not configured).
    | Single-tenant MVP — one client per deployment.
    |
    */

    'public_client_id' => env('RECRUITMENT_PUBLIC_CLIENT_ID', null),

    'careers_domain' => env('CAREERS_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Resume Upload (Step 1)
    |--------------------------------------------------------------------------
    |
    | Toggles the "Upload PDF Resume" card on the careers application form.
    | When false, applicants only see the "Create from Scratch" option.
    | Useful when the resume parser is unavailable or being iterated on.
    |
    */

    'resume_upload_enabled' => env('CAREERS_RESUME_UPLOAD_ENABLED', true),

];
