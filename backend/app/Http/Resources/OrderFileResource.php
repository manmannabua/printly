<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderFileResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_item_id' => $this->order_item_id,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size_bytes' => $this->size_bytes,
            'page_count' => $this->page_count,
            'paper_size' => $this->paper_size,
            'is_color' => $this->is_color,
            'analysis_status' => $this->analysis_status,
            'analysis_error' => $this->analysis_error,
            'created_at' => $this->created_at,
        ];
    }
}
