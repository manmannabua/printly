<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'code' => $this->code,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'pay_method' => $this->pay_method,
            'subtotal_cents' => $this->subtotal_cents,
            'fee_cents' => $this->fee_cents,
            'total_cents' => $this->total_cents,
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'ready_at' => $this->ready_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'events' => OrderEventResource::collection($this->whenLoaded('events')),
            'print_jobs' => PrintJobResource::collection($this->whenLoaded('printJobs')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
