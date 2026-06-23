<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\Storefront\Concerns\ResolvesStorefront;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontQuoteController extends BaseController
{
    use ResolvesStorefront;

    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Itemized price for one product (planning §7, POST /s/{slug}/quote).
     * No order is created — pure pricing preview the customer sees before checkout.
     */
    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $store = $this->activeStore($slug);

        $productId = $request->input('product_id');
        $product = Product::where('store_id', $store->id)
            ->where('is_active', true)
            ->with(['productType', 'priceRules', 'options'])
            ->find($productId);

        abort_unless($product !== null, 404, 'Product not found.');

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
