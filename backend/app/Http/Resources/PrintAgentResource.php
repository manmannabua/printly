<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintAgentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'printers_count' => $this->when(isset($this->printers_count), fn () => $this->printers_count),
            'printers' => PrinterResource::collection($this->whenLoaded('printers')),
            // Only present on create / regenerate — never persisted or re-shown.
            'token' => $this->when(isset($this->plain_token), fn () => $this->plain_token),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
