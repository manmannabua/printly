<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\Storefront\Concerns\ResolvesStorefront;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;

class StorefrontController extends BaseController
{
    use ResolvesStorefront;

    /**
     * Public store page: store basics + active catalog (planning §7, GET /s/{slug}).
     * Hand-built so only active types/products are exposed and no internal fields
     * (plan/status) leak to customers.
     */
    public function show(string $slug): JsonResponse
    {
        $store = $this->activeStore($slug);

        $types = $store->productTypes()
            ->where('is_active', true)
            ->with(['products' => fn ($q) => $q->where('is_active', true)
                ->with(['priceRules', 'options'])
                ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        return $this->success([
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'currency' => $store->currency,
                'timezone' => $store->timezone,
                'address' => $store->address,
                'settings' => $this->publicSettings($store->settings ?? []),
            ],
            'product_types' => $types->map(fn (ProductType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'pricing_mode' => $type->pricing_mode,
                'fulfillment' => $type->fulfillment,
                'products' => $type->products->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'pricing_mode' => $type->pricing_mode,
                    'base_price_cents' => $product->base_price_cents,
                    'price_rules' => $product->priceRules->map(fn ($r) => [
                        'attribute' => $r->attribute,
                        'match_value' => $r->match_value,
                        'modifier_type' => $r->modifier_type,
                        'amount_cents' => $r->amount_cents,
                        'multiplier' => $r->multiplier,
                    ])->values(),
                    'options' => $product->options->map(fn ($o) => [
                        'id' => $o->id,
                        'name' => $o->name,
                        'choices' => $o->choices ?? [],
                    ])->values(),
                ])->values(),
            ])->values(),
        ]);
    }

    /**
     * Only customer-relevant settings; never expose internal flags wholesale.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function publicSettings(array $settings): array
    {
        return [
            'accepts_guest' => (bool) ($settings['accepts_guest'] ?? true),
            'pay_on_pickup_allowed' => (bool) ($settings['pay_on_pickup_allowed'] ?? false),
        ];
    }
}
