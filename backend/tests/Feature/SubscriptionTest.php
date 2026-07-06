<?php

use App\Models\Role;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::where('name', 'admin')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);

    $this->owner = User::factory()->create();
    $this->owner->roles()->attach(Role::where('name', 'store_owner')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);

    $this->store = Store::create(['name' => 'Shop', 'slug' => 'shop', 'plan' => 'starter', 'status' => 'active']);
    $this->store->users()->attach($this->owner->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);
});

function subUrl(string $storeId): string
{
    return "/api/v1/stores/{$storeId}/subscription";
}

it('auto-creates a subscription on first view with entitlements and the plan catalogue', function () {
    $data = $this->actingAs($this->owner)
        ->getJson(subUrl($this->store->id))
        ->assertOk()->json('data');

    expect($data['plan'])->toBe('starter');
    // Starter does not include the paid features.
    expect($data['features'])->not->toContain('auto_print');
    expect($data['features'])->not->toContain('online_payments');
    // The full ladder is returned for the comparison grid.
    expect(collect($data['plans'])->pluck('code')->all())->toBe(['starter', 'pro', 'auto']);
    // Owner cannot self-manage billing.
    expect($data['can_manage'])->toBeFalse();

    $this->assertDatabaseHas('subscriptions', ['store_id' => $this->store->id, 'plan' => 'starter']);
});

it('lets an admin change the plan and mirrors it onto the store', function () {
    $data = $this->actingAs($this->admin)
        ->putJson(subUrl($this->store->id), ['plan' => 'auto'])
        ->assertOk()->json('data');

    expect($data['plan'])->toBe('auto');
    expect($data['features'])->toContain('auto_print');
    expect($data['status'])->toBe(Subscription::STATUS_ACTIVE);

    expect($this->store->fresh()->plan)->toBe('auto');
});

it('forbids an owner (view-only) from changing the plan', function () {
    $this->actingAs($this->owner)
        ->putJson(subUrl($this->store->id), ['plan' => 'auto'])
        ->assertForbidden();
});

it('rejects an unknown plan', function () {
    $this->actingAs($this->admin)
        ->putJson(subUrl($this->store->id), ['plan' => 'enterprise'])
        ->assertStatus(422);
});

it('blocks configuring auto-print until the plan unlocks it (402)', function () {
    // Starter store: the plan gate rejects print-agent creation.
    $this->actingAs($this->owner)
        ->postJson("/api/v1/stores/{$this->store->id}/print-agents", ['name' => 'Front PC'])
        ->assertStatus(402)
        ->assertJsonPath('errors.plan.feature', 'auto_print');

    // Upgrade to Auto → the same call now succeeds.
    app(SubscriptionService::class)->changePlan($this->store, 'auto');

    $this->actingAs($this->owner)
        ->postJson("/api/v1/stores/{$this->store->id}/print-agents", ['name' => 'Front PC'])
        ->assertCreated();
});

it('keeps auto-print off unless the plan includes it, even when the toggle is on', function () {
    $this->store->update(['settings' => ['auto_print' => true]]);
    expect($this->store->fresh()->autoPrintEnabled())->toBeFalse();

    app(SubscriptionService::class)->changePlan($this->store, 'auto');
    expect($this->store->fresh()->autoPrintEnabled())->toBeTrue();
});

it('drops plan features when the subscription lapses', function () {
    app(SubscriptionService::class)->changePlan($this->store, 'auto');
    expect($this->store->fresh()->allows('auto_print'))->toBeTrue();

    // A past-due subscription falls back to the default plan's features.
    $this->store->subscription->update(['status' => Subscription::STATUS_PAST_DUE]);
    expect($this->store->fresh()->allows('auto_print'))->toBeFalse();
});
