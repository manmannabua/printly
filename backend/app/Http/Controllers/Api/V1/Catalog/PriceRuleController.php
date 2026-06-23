<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Catalog\CreatePriceRuleRequest;
use App\Http\Requests\Catalog\UpdatePriceRuleRequest;
use App\Http\Resources\PriceRuleResource;
use App\Models\AuditLog;
use App\Models\PriceRule;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class PriceRuleController extends BaseController
{
    public function index(Store $store, Product $product): JsonResponse
    {
        $this->ensureProduct($store, $product);

        return $this->success(PriceRuleResource::collection($product->priceRules()->get()));
    }

    public function store(CreatePriceRuleRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->ensureProduct($store, $product);

        $rule = $product->priceRules()->create([
            ...$request->validated(),
            'store_id' => $store->id,
        ]);
        AuditLog::log($rule, 'created', null, $rule->toArray());

        return $this->success(new PriceRuleResource($rule), 'Price rule created.', 201);
    }

    public function update(UpdatePriceRuleRequest $request, Store $store, Product $product, PriceRule $priceRule): JsonResponse
    {
        $this->ensureRule($store, $product, $priceRule);
        $old = $priceRule->toArray();
        $priceRule->update($request->validated());
        AuditLog::log($priceRule, 'updated', $old, $priceRule->fresh()->toArray());

        return $this->success(new PriceRuleResource($priceRule), 'Price rule updated.');
    }

    public function destroy(Store $store, Product $product, PriceRule $priceRule): JsonResponse
    {
        $this->ensureRule($store, $product, $priceRule);
        $snapshot = $priceRule->toArray();
        $priceRule->delete();
        AuditLog::log($priceRule, 'deleted', $snapshot);

        return $this->success(null, 'Price rule deleted.');
    }

    private function ensureProduct(Store $store, Product $product): void
    {
        abort_unless($product->store_id === $store->id, 404, 'Product not found.');
    }

    private function ensureRule(Store $store, Product $product, PriceRule $priceRule): void
    {
        $this->ensureProduct($store, $product);
        abort_unless($priceRule->product_id === $product->id, 404, 'Price rule not found.');
    }
}
