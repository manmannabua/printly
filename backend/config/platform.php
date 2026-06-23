<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Admin Emails
    |--------------------------------------------------------------------------
    |
    | Email allowlist identifying users that operate the platform itself
    | (multi-tenant SaaS) rather than being a tenant administrator.
    |
    | When the allowlist is **empty** (e.g. single-tenant deployments), the
    | concept does not apply and any user with the `admin` role is treated as
    | a platform admin — preserving legacy behavior.
    |
    | When the allowlist is **populated**, only users whose email appears in
    | the list are platform admins. Other users — including tenant admins
    | who hold the `admin` role — are treated as tenant-scoped, which causes
    | platform-only resources (the `admin` role, `demo_access_*` audit
    | events, etc.) to be hidden from their UI.
    |
    | Set via env: PLATFORM_ADMIN_EMAILS="alice@company.com,bob@company.com"
    |
    */

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PLATFORM_ADMIN_EMAILS', '')),
    ))),

];
