<?php

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        'payments_enabled' => true,
        'paymongo_secret_key' => 'sk_test_123',
        'paymongo_webhook_secret' => 'whsk_test_123',
    ]);

    $type = $this->store->productTypes()->create([
        'name' => 'Document printing', 'pricing_mode' => ProductType::PRICING_FILE_BASED,
    ]);
    $this->product = $this->store->products()->create([
        'product_type_id' => $type->id, 'name' => 'Short bond', 'base_price_cents' => 200,
    ]);
});

function placePaidOrder(Store $store, Product $product): Order
{
    return app(OrderService::class)->create($store, null, [[
        'product_id' => $product->id,
        'spec' => ['page_count' => 5, 'copies' => 1],
    ]]);
}

it('encrypts PayMongo secrets at rest', function () {
    $raw = \DB::table('stores')->where('id', $this->store->id)->value('paymongo_secret_key');
    expect($raw)->not->toBe('sk_test_123') // stored ciphertext
        ->and($this->store->fresh()->paymongo_secret_key)->toBe('sk_test_123'); // decrypts back
});

it('opens a PayMongo checkout for an online storefront order', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => ['id' => 'cs_test_1', 'attributes' => ['checkout_url' => 'https://pay.test/cs_test_1']],
        ], 200),
    ]);

    $res = $this->postJson('/api/v1/s/campus-print-hub/orders', [
        'customer' => ['phone' => '09170000000'],
        'pay_method' => 'gcash',
        'items' => [['product_id' => $this->product->id, 'spec' => ['page_count' => 5, 'copies' => 1]]],
    ])->assertCreated();

    expect($res->json('data.checkout_url'))->toBe('https://pay.test/cs_test_1');

    $payment = Payment::firstWhere('provider_ref', 'cs_test_1');
    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe('pending')
        ->and($payment->amount_cents)->toBe(1000);
});

it('marks the order paid on a valid webhook', function () {
    $order = placePaidOrder($this->store, $this->product);
    $payment = Payment::create([
        'order_id' => $order->id, 'store_id' => $this->store->id, 'provider' => 'paymongo',
        'provider_ref' => 'cs_test_2', 'amount_cents' => 1000, 'status' => 'pending',
    ]);

    $payload = json_encode([
        'data' => ['attributes' => [
            'type' => 'checkout_session.payment.paid',
            'data' => ['id' => 'cs_test_2', 'attributes' => ['payments' => [['id' => 'pay_test_2']]]],
        ]],
    ]);
    $t = (string) time();
    $sig = hash_hmac('sha256', "{$t}.{$payload}", 'whsk_test_123');

    $this->call('POST', "/api/v1/webhooks/paymongo/{$this->store->id}", [], [], [], [
        'HTTP_PAYMONGO_SIGNATURE' => "t={$t},te={$sig},li={$sig}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertOk();

    expect($payment->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->provider_payment_ref)->toBe('pay_test_2')
        // A paid order auto-accepts, so it settles in "accepted".
        ->and($order->fresh()->status)->toBe('accepted')
        ->and($order->events()->where('to_status', 'paid')->where('actor_type', OrderEvent::ACTOR_SYSTEM)->exists())->toBeTrue();
});

it('rejects a webhook with a bad signature', function () {
    $order = placePaidOrder($this->store, $this->product);
    Payment::create([
        'order_id' => $order->id, 'store_id' => $this->store->id, 'provider' => 'paymongo',
        'provider_ref' => 'cs_test_3', 'amount_cents' => 1000, 'status' => 'pending',
    ]);

    $payload = json_encode(['data' => ['attributes' => ['type' => 'checkout_session.payment.paid', 'data' => ['id' => 'cs_test_3']]]]);

    $this->call('POST', "/api/v1/webhooks/paymongo/{$this->store->id}", [], [], [], [
        'HTTP_PAYMONGO_SIGNATURE' => 't=123,li=deadbeef',
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertStatus(401);

    expect($order->fresh()->status)->toBe('pending_payment');
});

it('rejects a webhook with a stale signature timestamp', function () {
    $order = placePaidOrder($this->store, $this->product);
    Payment::create([
        'order_id' => $order->id, 'store_id' => $this->store->id, 'provider' => 'paymongo',
        'provider_ref' => 'cs_test_4', 'amount_cents' => 1000, 'status' => 'pending',
    ]);

    $payload = json_encode(['data' => ['attributes' => ['type' => 'checkout_session.payment.paid', 'data' => ['id' => 'cs_test_4']]]]);
    $t = (string) (time() - 600);
    $sig = hash_hmac('sha256', "{$t}.{$payload}", 'whsk_test_123');

    $this->call('POST', "/api/v1/webhooks/paymongo/{$this->store->id}", [], [], [], [
        'HTTP_PAYMONGO_SIGNATURE' => "t={$t},li={$sig}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload)->assertStatus(401);

    expect($order->fresh()->status)->toBe('pending_payment');
});

it('refunds via PayMongo when an order is marked refunded', function () {
    Http::fake(['api.paymongo.com/*' => Http::response(['data' => ['id' => 'ref_test_1']], 200)]);

    $order = placePaidOrder($this->store, $this->product);
    $payment = Payment::create([
        'order_id' => $order->id, 'store_id' => $this->store->id, 'provider' => 'paymongo',
        'provider_ref' => 'cs_x', 'provider_payment_ref' => 'pay_x', 'amount_cents' => 1000, 'status' => 'paid',
    ]);
    // Drive to a refundable state.
    app(OrderService::class)->transition($order, 'paid');
    app(OrderService::class)->transition($order, 'rejected');

    $this->actingAs($this->admin)
        ->patchJson("/api/v1/stores/{$this->store->id}/orders/{$order->id}", ['status' => 'refunded'])
        ->assertOk();

    expect($order->fresh()->status)->toBe('refunded')
        ->and($payment->fresh()->status)->toBe('refunded')
        ->and($payment->refunds()->where('status', 'succeeded')->exists())->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/refunds'));
});

it('returns a validation error when online checkout cannot be created', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response(['errors' => [['detail' => 'temporary failure']]], 500),
    ]);

    $this->postJson('/api/v1/s/campus-print-hub/orders', [
        'customer' => ['phone' => '09170000000'],
        'pay_method' => 'gcash',
        'items' => [['product_id' => $this->product->id, 'spec' => ['page_count' => 5, 'copies' => 1]]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pay_method']);

    expect(Payment::count())->toBe(0)
        ->and(Order::first()?->status)->toBe(Order::STATUS_CANCELLED);
});
