<?php

namespace App\Http\Controllers\Api\V1\Storefront\Concerns;

use App\Models\Store;

trait ResolvesStorefront
{
    /**
     * Resolve a publicly-visible store by its slug. Only active stores are
     * reachable from the storefront; anything else 404s (existence not leaked).
     */
    protected function activeStore(string $slug): Store
    {
        $store = Store::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        abort_unless($store !== null, 404, 'Store not found.');

        return $store;
    }
}
