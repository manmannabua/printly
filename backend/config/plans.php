<?php

/*
|--------------------------------------------------------------------------
| Printly subscription plans (Printly → store)
|--------------------------------------------------------------------------
| The single source of truth for what each plan costs and unlocks. This is
| the revenue model: features here are gated by App\Services\PlanCatalog and
| enforced via the `plan:` middleware + Store::allows(). Money is integer
| centavos (₱499.00 → 49900), consistent with the rest of the app.
|
| Feature keys (referenced in code):
|   online_payments  → connect PayMongo / take GCash-Maya-card online
|   reports          → sales & peak-hour analytics
|   chat             → in-app customer/staff messaging
|   auto_print       → the print-agent bridge (the headline "auto" upsell)
|
| Limit keys (null = unlimited):
|   staff_seats      → store members allowed
|   products         → catalogue size
*/

return [

    // Order matters: used for upgrade/downgrade comparisons (index = rank).
    'order' => ['starter', 'pro', 'auto'],

    'default' => 'starter',

    'plans' => [

        'starter' => [
            'name' => 'Starter',
            'tagline' => 'Get selling — storefront, queue, manual printing.',
            'price_cents' => 0,
            'features' => [
                'storefront',
                'manual_print',
                'cash_on_pickup',
            ],
            'limits' => [
                'staff_seats' => 2,
                'products' => 20,
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'tagline' => 'Take money online and see how the shop is doing.',
            'price_cents' => 49900,
            'features' => [
                'storefront',
                'manual_print',
                'cash_on_pickup',
                'online_payments',
                'reports',
                'chat',
            ],
            'limits' => [
                'staff_seats' => 8,
                'products' => null,
            ],
        ],

        'auto' => [
            'name' => 'Auto',
            'tagline' => 'Hands-off printing — jobs route straight to your printers.',
            'price_cents' => 99900,
            'features' => [
                'storefront',
                'manual_print',
                'cash_on_pickup',
                'online_payments',
                'reports',
                'chat',
                'auto_print',
            ],
            'limits' => [
                'staff_seats' => null,
                'products' => null,
            ],
        ],

    ],

];
