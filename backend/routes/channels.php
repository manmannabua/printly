<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Routes & Channels
|--------------------------------------------------------------------------
|
| Register the /broadcasting/auth endpoint with Sanctum authentication and
| any private/presence channels the app supports. Real-time features are out
| of scope for Phase 1 — only the per-user channel is registered.
|
*/

Broadcast::routes(['middleware' => ['web', 'auth:sanctum']]);

/**
 * Private user channel — only the user themselves can listen.
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});
