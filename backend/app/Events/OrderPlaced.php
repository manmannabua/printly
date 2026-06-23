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
 * A new order landed on the store queue (planning §8, channel store.{id}.orders).
 */
class OrderPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order) {}

    /**
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            // Public, code-scoped channel for the guest order-status page (same
            // access model as the public REST endpoint — see OrderStatusChanged).
            new Channel("orders.{$this->order->code}"),
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
            'status' => $this->order->status,
            'total_cents' => $this->order->total_cents,
            'placed_at' => $this->order->placed_at?->toISOString(),
        ];
    }
}
