<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Catalog\CreateProductRequest;
use App\Http\Requests\Catalog\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $query = Product::query()->where('store_id', $store->id)->with('productType')->withCount('priceRules');

        $this->applyFilters($query, $request, [
            'search' => ['name'],
            'exact' => ['product_type_id', 'is_active'],
        ]);
        $this->applySorting($query, $request, ['name', 'base_price_cents', 'sort_order', 'created_at'], 'sort_order', 'asc');

        return $this->paginateOrAll($query, $request, ProductResource::class);
    }

    public function store(CreateProductRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $product = $store->products()->create($request->validated());
        AuditLog::log($product, 'created', null, $product->toArray());

        return $this->success(new ProductResource($product->load('productType')), 'Product created.', 201);
    }

    public function show(Store $store, Product $product): JsonResponse
    {
        $this->ensureOwned($store, $product);
        $product->load(['productType', 'priceRules']);

        return $this->success(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->ensureOwned($store, $product);
        $old = $product->toArray();
        $product->update($request->validated());
        AuditLog::log($product, 'updated', $old, $product->fresh()->toArray());

        return $this->success(new ProductResource($product->load('productType')), 'Product updated.');
    }

    public function destroy(Store $store, Product $product): JsonResponse
    {
        $this->ensureOwned($store, $product);
        $snapshot = $product->toArray();
        $product->delete();
        AuditLog::log($product, 'deleted', $snapshot);

        return $this->success(null, 'Product deleted.');
    }

    private function ensureOwned(Store $store, Product $product): void
    {
        $this->authorizeStore($store);
        abort_unless($product->store_id === $store->id, 404, 'Product not found.');
    }
}
