<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'order_code' => $this->whenLoaded('order', fn () => $this->order->code),
            'order_file_id' => $this->order_file_id,
            'file_name' => $this->whenLoaded('orderFile', fn () => $this->orderFile->original_name),
            'printer_id' => $this->printer_id,
            'printer_name' => $this->whenLoaded('printer', fn () => $this->printer?->name),
            'status' => $this->status,
            'copies' => $this->copies,
            'error' => $this->error,
            'attempts' => $this->attempts,
            'sent_at' => $this->sent_at?->toISOString(),
            'printed_at' => $this->printed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
