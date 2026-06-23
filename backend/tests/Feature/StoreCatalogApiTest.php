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

it('blocks store access without permission', function () {
    $viewer = User::factory()->create(); // no roles → no permissions

    $this->actingAs($viewer)->getJson('/api/v1/stores')->assertForbidden();
    $this->actingAs($viewer)->postJson('/api/v1/stores', ['name' => 'X', 'slug' => 'x'])->assertForbidden();
});

it('validates store creation input', function () {
    $this->actingAs($this->admin)
        ->postJson('/api/v1/stores', ['name' => '', 'slug' => 'Bad Slug!'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'slug']);
});
