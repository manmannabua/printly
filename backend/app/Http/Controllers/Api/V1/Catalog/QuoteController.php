<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Store;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends BaseController
{
    public function __construct(private readonly PricingService $pricing)
    {
    }

    /**
     * Return an itemized price for a product given a file spec (file_based) or
     * option selections (spec_based). Mirrors the storefront /quote endpoint
     * that the customer flow will use, but auth-gated for catalog testing.
     */
    public function __invoke(Request $request, Store $store, Product $product): JsonResponse
    {
        abort_unless($product->store_id === $store->id, 404, 'Product not found.');

        $product->load(['productType', 'priceRules', 'options']);
        $mode = $product->productType->pricing_mode;

        if ($mode === ProductType::PRICING_FILE_BASED) {
            $data = $request->validate([
                'page_count' => ['required', 'integer', 'min:1'],
                'paper_size' => ['nullable', 'string', 'max:50'],
                'color' => ['nullable', 'in:color,bw'],
                'duplex' => ['nullable', 'boolean'],
                'copies' => ['nullable', 'integer', 'min:1'],
            ]);

            $quote = $this->pricing->quoteFileBased($product, $data);
        } else {
            $data = $request->validate([
                'selections' => ['nullable', 'array'],
                'selections.*.option_id' => ['required', 'uuid'],
                'selections.*.choice' => ['required', 'string'],
                'quantity' => ['nullable', 'integer', 'min:1'],
            ]);

            $quote = $this->pricing->quoteSpecBased(
                $product,
                $data['selections'] ?? [],
                (int) ($data['quantity'] ?? 1),
            );
        }

        return $this->success([
            'product_id' => $product->id,
            'pricing_mode' => $mode,
            ...$quote,
        ]);
    }
}
