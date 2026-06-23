<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'pricing_mode' => $this->pricing_mode,
            'quantity' => $this->quantity,
            'unit_breakdown' => $this->unit_breakdown,
            'spec_selections' => $this->spec_selections,
            'line_total_cents' => $this->line_total_cents,
            'files' => OrderFileResource::collection($this->whenLoaded('files')),
        ];
    }
}
