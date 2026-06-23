<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'product_type_id' => $this->product_type_id,
            'name' => $this->name,
            'base_price_cents' => $this->base_price_cents,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'product_type' => new ProductTypeResource($this->whenLoaded('productType')),
            'price_rules' => PriceRuleResource::collection($this->whenLoaded('priceRules')),
            'price_rules_count' => $this->when(isset($this->price_rules_count), fn () => $this->price_rules_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
