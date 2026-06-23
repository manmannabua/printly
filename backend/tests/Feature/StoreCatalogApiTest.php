<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::where('name', 'admin')->first()->id, [
        'id' => (string) Str::uuid(),
        'created_at' => now(),
    ]);
});

it('drives the full store → catalog → quote flow over HTTP', function () {
    // Create a store.
    $store = $this->actingAs($this->admin)->postJson('/api/v1/stores', [
        'name' => 'Campus Print Hub',
        'slug' => 'campus-print-hub',
        'plan' => 'pro',
        'status' => 'active',
    ])->assertCreated()->json('data');

    $storeId = $store['id'];

    // Create a file-based product type.
    $type = $this->actingAs($this->admin)->postJson("/api/v1/stores/{$storeId}/product-types", [
        'name' => 'Document printing',
        'pricing_mode' => 'file_based',
    ])->assertCreated()->json('data');

    // Create a product at ₱2.00/page base.
    $product = $this->actingAs($this->admin)->postJson("/api/v1/stores/{$storeId}/products", [
        'product_type_id' => $type['id'],
        'name' => 'Short bond',
        'base_price_cents' => 200,
    ])->assertCreated()->json('data');

    $productId = $product['id'];

    // Add rules: color +₱3/page, A4 +₱1/page, duplex ×0.9.
    foreach ([
        ['attribute' => 'color', 'match_value' => 'color', 'modifier_type' => 'per_page', 'amount_cents' => 300],
        ['attribute' => 'paper_size', 'match_value' => 'A4', 'modifier_type' => 'per_page', 'amount_cents' => 100],
        ['attribute' => 'duplex', 'match_value' => 'duplex', 'modifier_type' => 'multiplier', 'multiplier' => 0.9],
    ] as $rule) {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/stores/{$storeId}/products/{$productId}/price-rules", $rule)
            ->assertCreated();
    }

    // Quote: 10 color A4 duplex pages × 2 copies = (200+300+100)×10×0.9×2 = 10800.
    $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$storeId}/products/{$productId}/quote", [
            'page_count' => 10,
            'color' => 'color',
            'paper_size' => 'A4',
            'duplex' => true,
            'copies' => 2,
        ])
        ->assertOk()
        ->assertJsonPath('data.total_cents', 10800);

    // Lists the frontend pages call.
    $this->actingAs($this->admin)->getJson('/api/v1/stores')->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($this->admin)->getJson("/api/v1/stores/{$storeId}/products")->assertOk()->assertJsonCount(1, 'data');
});

it('rejects a price rule on an attribute the pricing engine ignores', function () {
    $store = $this->actingAs($this->admin)->postJson('/api/v1/stores', [
        'name' => 'Campus Print Hub', 'slug' => 'campus-print-hub', 'plan' => 'pro', 'status' => 'active',
    ])->assertCreated()->json('data');

    $type = $this->actingAs($this->admin)->postJson("/api/v1/stores/{$store['id']}/product-types", [
        'name' => 'Document printing', 'pricing_mode' => 'file_based',
    ])->assertCreated()->json('data');

    $product = $this->actingAs($this->admin)->postJson("/api/v1/stores/{$store['id']}/products", [
        'product_type_id' => $type['id'], 'name' => 'Short bond', 'base_price_cents' => 200,
    ])->assertCreated()->json('data');

    $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$store['id']}/products/{$product['id']}/price-rules", [
            'attribute' => 'copies', // not matched by PricingService → would be a dead rule
            'match_value' => '10',
            'modifier_type' => 'per_page',
            'amount_cents' => 100,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['attribute']);
});

it('blocks store access without permission', function () {
    $viewer = User::factory()->create(); // no roles → no permissions

    $this->actingAs($viewer)->getJson('/api/v1/stores')->assertForbidden();
    $this->actingAs($viewer)->postJson('/api/v1/stores', ['name' => 'X', 'slug' => 'x'])->assertForbidden();
});

it('scopes stores to the user’s memberships for non-admins', function () {
    // Admin provisions two stores.
    $mine = $this->actingAs($this->admin)->postJson('/api/v1/stores', [
        'name' => 'Mine', 'slug' => 'mine', 'plan' => 'pro', 'status' => 'active',
    ])->assertCreated()->json('data');
    $theirs = $this->actingAs($this->admin)->postJson('/api/v1/stores', [
        'name' => 'Theirs', 'slug' => 'theirs', 'plan' => 'pro', 'status' => 'active',
    ])->assertCreated()->json('data');

    // A store owner who belongs only to "mine".
    $owner = User::factory()->create();
    $owner->roles()->attach(Role::where('name', 'store_owner')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);
    \App\Models\Store::find($mine['id'])->users()->attach($owner->id, [
        'id' => (string) Str::uuid(), 'role' => 'owner',
    ]);

    // Index returns only their store.
    $this->actingAs($owner)->getJson('/api/v1/stores')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine['id']);

    // Direct access to a store they don't belong to is 404 (existence hidden).
    $this->actingAs($owner)->getJson("/api/v1/stores/{$theirs['id']}")->assertNotFound();
    $this->actingAs($owner)->getJson("/api/v1/stores/{$theirs['id']}/products")->assertNotFound();

    // Their own store is reachable.
    $this->actingAs($owner)->getJson("/api/v1/stores/{$mine['id']}")->assertOk();
});

it('validates store creation input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/stores', ['name' => '', 'slug' => 'Bad Slug!'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'slug']);
});
