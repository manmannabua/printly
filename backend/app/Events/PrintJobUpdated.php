<?php

namespace App\Events;

use App\Models\PrintJob;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A print job was created or changed status. Broadcast to the store queue board
 * (store.{id}.orders) so the printers panel and order cards update live. The
 * in-store agent itself does not listen here — it polls the agent API.
 */
class PrintJobUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly PrintJob $job) {}

    /**
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("store.{$this->job->store_id}.orders"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->job->id,
            'order_id' => $this->job->order_id,
            'printer_id' => $this->job->printer_id,
            'status' => $this->job->status,
            'error' => $this->job->error,
        ];
    }
}
