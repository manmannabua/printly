<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class PublicOrderStatusController extends BaseController
{
    /**
     * Customer-facing order status, looked up by the QR/order code.
     * No auth: the unguessable code is the bearer (planning §7, GET /orders/{code}).
     * Returns only what the customer needs — no internal notes or actor ids.
     */
    public function show(string $code): JsonResponse
    {
        $order = Order::with('store:id,name,slug')
            ->where('code', $code)
            ->first();

        abort_unless($order !== null, 404, 'Order not found.');

        return $this->success([
            'code' => $order->code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_cents' => $order->total_cents,
            'store' => [
                'name' => $order->store?->name,
                'slug' => $order->store?->slug,
            ],
            'placed_at' => $order->placed_at?->toISOString(),
            'ready_at' => $order->ready_at?->toISOString(),
            'completed_at' => $order->completed_at?->toISOString(),
        ]);
    }
}
