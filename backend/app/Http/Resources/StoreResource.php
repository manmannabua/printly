<?php

namespace App\Http\Resources;

use App\Services\PlanCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'plan' => $this->plan,
            // Feature keys the plan unlocks — lets the UI hint upsells without a
            // round-trip. Authoritative (lapse-aware) entitlements live on the
            // subscription endpoint; this is derived cheaply from the column.
            'plan_features' => PlanCatalog::features($this->plan),
            'status' => $this->status,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'address' => $this->address,
            'settings' => $this->settings ?? [],
            'product_types_count' => $this->when(isset($this->product_types_count), fn () => $this->product_types_count),
            'products_count' => $this->when(isset($this->products_count), fn () => $this->products_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
