<?php

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::where('name', 'admin')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);

    $this->store = Store::create([
        'name' => 'Campus Print Hub', 'slug' => 'campus-print-hub', 'plan' => 'pro', 'status' => 'active',
    ]);

    $type = $this->store->productTypes()->create([
        'name' => 'Document printing', 'pricing_mode' => ProductType::PRICING_FILE_BASED,
    ]);
    $this->product = $this->store->products()->create([
        'product_type_id' => $type->id, 'name' => 'Short bond', 'base_price_cents' => 200,
    ]);
    $this->product->priceRules()->create([
        'store_id' => $this->store->id, 'attribute' => 'color', 'match_value' => 'color',
        'modifier_type' => 'per_page', 'amount_cents' => 300,
    ]);
});

function placeOrder(Store $store, Product $product): Order
{
    return app(OrderService::class)->create($store, null, [[
        'product_id' => $product->id,
        'spec' => ['page_count' => 5, 'color' => 'color', 'copies' => 2],
    ]]);
}

it('creates an order with a frozen pricing snapshot and broadcasts OrderPlaced', function () {
    Event::fake([OrderPlaced::class]);

    $res = $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$this->store->id}/orders", [
            'customer' => ['name' => 'Juan', 'phone' => '09170000000'],
            'pay_method' => 'gcash',
            'items' => [[
                'product_id' => $this->product->id,
                'spec' => ['page_count' => 5, 'color' => 'color', 'copies' => 2],
            ]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending_payment')
        ->assertJsonPath('data.total_cents', 5000); // (200+300)*5*2

    expect($res->json('data.code'))->toStartWith('PRT-')
        ->and($res->json('data.items.0.line_total_cents'))->toBe(5000)
        ->and($res->json('data.items.0.unit_breakdown'))->not->toBeEmpty();

    // A guest customer was created and the creation event logged.
    expect(Customer::where('phone', '09170000000')->where('is_guest', true)->exists())->toBeTrue();
    $order = Order::firstWhere('code', $res->json('data.code'));
    expect($order->events()->where('to_status', 'pending_payment')->whereNull('from_status')->count())->toBe(1);

    Event::assertDispatched(OrderPlaced::class);
});

it('walks an order through the fulfillment state machine', function () {
    $order = placeOrder($this->store, $this->product);

    foreach (['paid', 'accepted', 'in_progress', 'ready', 'completed'] as $status) {
        $this->actingAs($this->admin)
            ->patchJson("/api/v1/stores/{$this->store->id}/orders/{$order->id}", ['status' => $status])
            ->assertOk()
            ->assertJsonPath('data.status', $status);
    }

    $order->refresh();
    expect($order->accepted_at)->not->toBeNull()
        ->and($order->ready_at)->not->toBeNull()
        ->and($order->completed_at)->not->toBeNull()
        ->and($order->payment_status)->toBe('paid')
        // creation + 5 transitions
        ->and($order->events()->count())->toBe(6);
});

it('rejects an illegal state transition with 422', function () {
    $order = placeOrder($this->store, $this->product);

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/stores/{$this->store->id}/orders/{$order->id}", ['status' => 'completed'])
        ->assertStatus(422);

    expect($order->refresh()->status)->toBe('pending_payment');
});

it('broadcasts OrderStatusChanged on transition', function () {
    $order = placeOrder($this->store, $this->product);
    Event::fake([OrderStatusChanged::class]);

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/stores/{$this->store->id}/orders/{$order->id}", ['status' => 'paid'])
        ->assertOk();

    Event::assertDispatched(OrderStatusChanged::class, fn ($e) => $e->fromStatus === 'pending_payment'
        && $e->order->status === 'paid');
});

it('exposes public order status by code without auth', function () {
    $order = placeOrder($this->store, $this->product);

    $this->getJson("/api/v1/orders/{$order->code}")
        ->assertOk()
        ->assertJsonPath('data.code', $order->code)
        ->assertJsonPath('data.status', 'pending_payment')
        ->assertJsonPath('data.store.slug', 'campus-print-hub');
});

it('scopes the queue board and blocks non-member stores', function () {
    placeOrder($this->store, $this->product);

    // Owner who belongs to a different store cannot see this store's queue.
    $owner = User::factory()->create();
    $owner->roles()->attach(Role::where('name', 'store_owner')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);

    $this->actingAs($owner)
        ->getJson("/api/v1/stores/{$this->store->id}/orders")
        ->assertNotFound();

    // Admin sees the queue.
    $this->actingAs($this->admin)
        ->getJson("/api/v1/stores/{$this->store->id}/orders")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
