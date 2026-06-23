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

/**
 * Store queue board — staff/owners of the store (admins always). Planning §8.
 */
Broadcast::channel('store.{storeId}.orders', function ($user, $storeId) {
    return $user->belongsToStore($storeId);
});

/**
 * Per-order channel — store members may subscribe (e.g. an order detail view).
 * The customer's own subscription uses a guest-token broadcast auth added with
 * the storefront (step 5); for now only authenticated store members are allowed.
 */
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    $order = \App\Models\Order::find($orderId);

    return $order !== null && $user->belongsToStore($order->store_id);
});

/**
 * Chat conversation channel — admins always; store members for their store's
 * conversations. The guest customer side uses the public chat.order.{code}
 * channel instead (no auth), so it is not handled here.
 */
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\ChatConversation::find($conversationId);

    if ($conversation === null) {
        return false;
    }

    return $user->is_admin
        || ($conversation->store_id !== null && $user->belongsToStore($conversation->store_id));
});
