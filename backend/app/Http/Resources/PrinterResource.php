<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrinterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'print_agent_id' => $this->print_agent_id,
            'name' => $this->name,
            'capabilities' => $this->capabilities ?? ['sizes' => [], 'color' => false],
            'is_active' => $this->is_active,
            'agent' => new PrintAgentResource($this->whenLoaded('agent')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
