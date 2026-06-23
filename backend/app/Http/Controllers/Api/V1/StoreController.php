<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Store\CreateStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\AuditLog;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Store::withCount(['productTypes', 'products']);
        $this->scopeStoresForUser($query, $request);

        $this->applyFilters($query, $request, [
            'search' => ['name', 'slug', 'address'],
            'exact' => ['plan', 'status'],
        ]);
        $this->applySorting($query, $request, ['name', 'plan', 'status', 'created_at'], 'created_at');

        return $this->paginateOrAll($query, $request, StoreResource::class);
    }

    public function store(CreateStoreRequest $request): JsonResponse
    {
        $store = Store::create($request->validated());

        // Attach the creator as owner so non-admins keep access to what they
        // created (admins manage every store regardless of membership).
        $user = $request->user();
        if ($user && ! $user->is_admin) {
            $store->users()->attach($user->id, [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'role' => 'owner',
            ]);
        }

        AuditLog::log($store, 'created', null, $store->toArray());

        return $this->success(new StoreResource($store), 'Store created successfully.', 201);
    }

    public function show(Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $store->loadCount(['productTypes', 'products']);

        return $this->success(new StoreResource($store));
    }

    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $old = $store->toArray();
        $store->update($request->validated());
        AuditLog::log($store, 'updated', $old, $store->fresh()->toArray());

        return $this->success(new StoreResource($store), 'Store updated successfully.');
    }

    public function destroy(Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $snapshot = $store->toArray();
        $store->delete();
        AuditLog::log($store, 'deleted', $snapshot);

        return $this->success(null, 'Store deleted successfully.');
    }
}
