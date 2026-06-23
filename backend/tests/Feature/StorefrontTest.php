<?php

use App\Jobs\AnalyzeOrderFile;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::create([
        'name' => 'Campus Print Hub', 'slug' => 'campus-print-hub',
        'plan' => 'pro', 'status' => 'active', 'currency' => 'PHP',
    ]);

    $type = $this->store->productTypes()->create([
        'name' => 'Document printing', 'pricing_mode' => ProductType::PRICING_FILE_BASED, 'is_active' => true,
    ]);
    $this->product = $this->store->products()->create([
        'product_type_id' => $type->id, 'name' => 'Short bond', 'base_price_cents' => 200, 'is_active' => true,
    ]);
    $this->product->priceRules()->create([
        'store_id' => $this->store->id, 'attribute' => 'color', 'match_value' => 'color',
        'modifier_type' => 'per_page', 'amount_cents' => 300,
    ]);
    // An inactive product must never appear or be orderable.
    $this->hidden = $this->store->products()->create([
        'product_type_id' => $type->id, 'name' => 'Retired item', 'base_price_cents' => 999, 'is_active' => false,
    ]);
});

it('serves the public catalog with active products only', function () {
    $res = $this->getJson('/api/v1/s/campus-print-hub')
        ->assertOk()
        ->assertJsonPath('data.store.slug', 'campus-print-hub')
        ->assertJsonPath('data.product_types.0.products.0.name', 'Short bond');

    $names = collect($res->json('data.product_types.0.products'))->pluck('name');
    expect($names)->toContain('Short bond')->not->toContain('Retired item');
    // No internal fields leak.
    expect($res->json('data.store'))->not->toHaveKey('plan');
});

it('404s for an inactive or unknown store', function () {
    $this->store->update(['status' => 'suspended']);
    $this->getJson('/api/v1/s/campus-print-hub')->assertNotFound();
    $this->getJson('/api/v1/s/does-not-exist')->assertNotFound();
});

it('accepts a guest file upload and queues analysis', function () {
    Storage::fake('private');
    Queue::fake();

    $res = $this->postJson('/api/v1/s/campus-print-hub/files', [
        'file' => UploadedFile::fake()->create('thesis.pdf', 120, 'application/pdf'),
    ])->assertCreated()->assertJsonPath('data.analysis_status', 'pending');

    Storage::disk('private')->assertExists(OrderFile::find($res->json('data.id'))->storage_path);
    Queue::assertPushed(AnalyzeOrderFile::class);
});

it('quotes a product for the customer without creating an order', function () {
    $this->postJson('/api/v1/s/campus-print-hub/quote', [
        'product_id' => $this->product->id,
        'page_count' => 10,
        'color' => 'color',
        'copies' => 2,
    ])
        ->assertOk()
        ->assertJsonPath('data.total_cents', 10000); // (200+300)*10*2

    expect(Order::count())->toBe(0);
});

it('places a guest order and returns a tracking code', function () {
    Storage::fake('private');

    // Upload a file first, then attach it at checkout.
    $fileId = $this->postJson('/api/v1/s/campus-print-hub/files', [
        'file' => UploadedFile::fake()->create('thesis.pdf', 120, 'application/pdf'),
    ])->json('data.id');

    $res = $this->postJson('/api/v1/s/campus-print-hub/orders', [
        'customer' => ['name' => 'Juan', 'phone' => '09170000000'],
        'pay_method' => 'cash_on_pickup',
        'items' => [[
            'product_id' => $this->product->id,
            'spec' => ['page_count' => 10, 'color' => 'color', 'copies' => 2],
            'file_ids' => [$fileId],
        ]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending_payment')
        ->assertJsonPath('data.total_cents', 10000);

    expect($res->json('data.code'))->toStartWith('PRT-');

    $order = Order::firstWhere('code', $res->json('data.code'));
    expect(Customer::where('phone', '09170000000')->where('is_guest', true)->exists())->toBeTrue()
        ->and(OrderFile::find($fileId)->order_item_id)->toBe($order->items()->first()->id);

    // The public status page resolves the code.
    $this->getJson("/api/v1/orders/{$res->json('data.code')}")
        ->assertOk()
        ->assertJsonPath('data.status', 'pending_payment');
});

it('rejects an order for an inactive product', function () {
    $this->postJson('/api/v1/s/campus-print-hub/orders', [
        'customer' => ['phone' => '09170000000'],
        'items' => [['product_id' => $this->hidden->id, 'spec' => ['page_count' => 1]]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('requires guest contact details to place an order', function () {
    $this->postJson('/api/v1/s/campus-print-hub/orders', [
        'customer' => [],
        'items' => [['product_id' => $this->product->id, 'spec' => ['page_count' => 1]]],
    ])->assertStatus(422);
});
