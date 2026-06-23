<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An order moved to a new status. Reaches both the customer's status page
 * (order.{id}) and the store queue board (store.{id}.orders) — planning §8.
 */
class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?string $fromStatus,
    ) {}

    /**
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            // Public, code-scoped channel for the guest order-status page. The
            // order code is already the sole secret protecting the public REST
            // endpoint, so a code-keyed channel has the same access model — no
            // broadcast auth handshake needed, and the payload carries no PII.
            new Channel("orders.{$this->order->code}"),
            new PrivateChannel("order.{$this->order->id}"),
            new PrivateChannel("store.{$this->order->store_id}.orders"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'code' => $this->order->code,
            'from_status' => $this->fromStatus,
            'status' => $this->order->status,
        ];
    }
}
