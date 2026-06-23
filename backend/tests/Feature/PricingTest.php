<?php

use App\Models\Product;
use App\Models\ProductType;
use App\Models\Store;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStore(): Store
{
    return Store::create([
        'name' => 'Campus Print Hub',
        'slug' => 'campus-print-hub',
        'plan' => 'pro',
        'status' => 'active',
    ]);
}

function makeFileProduct(Store $store, int $basePerPage): Product
{
    $type = $store->productTypes()->create([
        'name' => 'Document printing',
        'pricing_mode' => ProductType::PRICING_FILE_BASED,
    ]);

    return $store->products()->create([
        'product_type_id' => $type->id,
        'name' => 'Short bond',
        'base_price_cents' => $basePerPage,
    ]);
}

it('prices a plain B&W document at base per-page rate', function () {
    $product = makeFileProduct(makeStore(), 200); // ₱2.00/page
    $product->load('priceRules');

    $quote = app(PricingService::class)->quoteFileBased($product, [
        'page_count' => 10,
        'color' => 'bw',
    ]);

    expect($quote['total_cents'])->toBe(2000); // 10 × 200
});

it('applies per-page, per-page and multiplier rules with copies', function () {
    $store = makeStore();
    $product = makeFileProduct($store, 200);
    $product->priceRules()->createMany([
        ['store_id' => $store->id, 'attribute' => 'color', 'match_value' => 'color', 'modifier_type' => 'per_page', 'amount_cents' => 300],
        ['store_id' => $store->id, 'attribute' => 'paper_size', 'match_value' => 'A4', 'modifier_type' => 'per_page', 'amount_cents' => 100],
        ['store_id' => $store->id, 'attribute' => 'duplex', 'match_value' => 'duplex', 'modifier_type' => 'multiplier', 'multiplier' => 0.9],
    ]);
    $product->load('priceRules');

    // perPage = 200 + 300 + 100 = 600; × 10 pages × 0.9 = 5400; × 2 copies = 10800
    $quote = app(PricingService::class)->quoteFileBased($product, [
        'page_count' => 10,
        'color' => 'color',
        'paper_size' => 'A4',
        'duplex' => true,
        'copies' => 2,
    ]);

    expect($quote['total_cents'])->toBe(10800)
        ->and($quote['copies'])->toBe(2)
        ->and($quote['breakdown'])->not->toBeEmpty();
});

it('ignores rules whose attribute value does not match the file spec', function () {
    $store = makeStore();
    $product = makeFileProduct($store, 200);
    // Color surcharge should NOT apply to a B&W job.
    $product->priceRules()->create([
        'store_id' => $store->id, 'attribute' => 'color', 'match_value' => 'color',
        'modifier_type' => 'per_page', 'amount_cents' => 300,
    ]);
    $product->load('priceRules');

    $quote = app(PricingService::class)->quoteFileBased($product, [
        'page_count' => 5,
        'color' => 'bw',
    ]);

    expect($quote['total_cents'])->toBe(1000); // 5 × 200, no color surcharge
});

it('prices a spec_based product from base + option deltas × quantity', function () {
    $store = makeStore();
    $type = $store->productTypes()->create([
        'name' => 'Tarpaulin',
        'pricing_mode' => ProductType::PRICING_SPEC_BASED,
    ]);
    $product = $store->products()->create([
        'product_type_id' => $type->id,
        'name' => 'Custom tarp',
        'base_price_cents' => 0,
    ]);
    $option = $product->options()->create([
        'store_id' => $store->id,
        'name' => 'Size',
        'choices' => [
            ['label' => '2x3 ft', 'price_delta_cents' => 15000],
            ['label' => '3x5 ft', 'price_delta_cents' => 30000],
        ],
    ]);
    $product->load('options');

    $quote = app(PricingService::class)->quoteSpecBased(
        $product,
        [['option_id' => $option->id, 'choice' => '3x5 ft']],
        2,
    );

    expect($quote['total_cents'])->toBe(60000); // 30000 × 2
});
