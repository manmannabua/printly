<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event-to-listener mappings are handled by Laravel's automatic discovery
     * via type-hinted handle() methods on listener classes.
     *
     * Do NOT add explicit $listen mappings here — they will cause duplicate
     * listener registration alongside auto-discovery in Laravel 12.
     */
}
