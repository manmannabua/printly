<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Catalog\CreateProductTypeRequest;
use App\Http\Requests\Catalog\UpdateProductTypeRequest;
use App\Http\Resources\ProductTypeResource;
use App\Models\AuditLog;
use App\Models\ProductType;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTypeController extends BaseController
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $query = $store->productTypes()->withCount('products');

        $this->applyFilters($query, $request, [
            'search' => ['name'],
            'exact' => ['pricing_mode', 'is_active'],
        ]);
        $this->applySorting($query, $request, ['name', 'sort_order', 'created_at'], 'sort_order', 'asc');

        return $this->paginateOrAll($query, $request, ProductTypeResource::class);
    }

    public function store(CreateProductTypeRequest $request, Store $store): JsonResponse
    {
        $type = $store->productTypes()->create($request->validated());
        AuditLog::log($type, 'created', null, $type->toArray());

        return $this->success(new ProductTypeResource($type), 'Product type created.', 201);
    }

    public function show(Store $store, ProductType $productType): JsonResponse
    {
        $this->ensureOwned($store, $productType);
        $productType->load('products')->loadCount('products');

        return $this->success(new ProductTypeResource($productType));
    }

    public function update(UpdateProductTypeRequest $request, Store $store, ProductType $productType): JsonResponse
    {
        $this->ensureOwned($store, $productType);
        $old = $productType->toArray();
        $productType->update($request->validated());
        AuditLog::log($productType, 'updated', $old, $productType->fresh()->toArray());

        return $this->success(new ProductTypeResource($productType), 'Product type updated.');
    }

    public function destroy(Store $store, ProductType $productType): JsonResponse
    {
        $this->ensureOwned($store, $productType);
        $snapshot = $productType->toArray();
        $productType->delete();
        AuditLog::log($productType, 'deleted', $snapshot);

        return $this->success(null, 'Product type deleted.');
    }

    private function ensureOwned(Store $store, ProductType $productType): void
    {
        abort_unless($productType->store_id === $store->id, 404, 'Product type not found.');
    }
}
