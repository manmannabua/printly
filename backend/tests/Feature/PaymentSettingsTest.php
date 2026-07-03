<?php

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->owner->roles()->attach(Role::where('name', 'store_owner')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);

    $this->store = Store::create(['name' => 'Shop', 'slug' => 'shop', 'status' => 'active']);
    $this->store->users()->attach($this->owner->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);
});

function settingsUrl(string $storeId): string
{
    return "/api/v1/stores/{$storeId}/payment-settings";
}

it('shows a secret-free connection view with the store webhook url', function () {
    $data = $this->actingAs($this->owner)
        ->getJson(settingsUrl($this->store->id))
        ->assertOk()->json('data');

    expect($data['payments_enabled'])->toBeFalse();
    expect($data['has_secret_key'])->toBeFalse();
    expect($data['accepts_online'])->toBeFalse();
    expect($data['webhook_url'])->toContain("/webhooks/paymongo/{$this->store->id}");
    // Secrets never appear in the payload.
    expect($data)->not->toHaveKeys(['paymongo_secret_key', 'paymongo_webhook_secret']);
});

it('connects PayMongo and encrypts the keys at rest', function () {
    $data = $this->actingAs($this->owner)
        ->putJson(settingsUrl($this->store->id), [
            'payments_enabled' => true,
            'paymongo_secret_key' => 'sk_test_abc123',
            'paymongo_webhook_secret' => 'whsec_xyz',
        ])->assertOk()->json('data');

    expect($data['has_secret_key'])->toBeTrue();
    expect($data['accepts_online'])->toBeTrue();

    $this->store->refresh();
    expect($this->store->paymongo_secret_key)->toBe('sk_test_abc123');
    // Stored ciphertext must not be the plaintext.
    $raw = \DB::table('stores')->where('id', $this->store->id)->value('paymongo_secret_key');
    expect($raw)->not->toBe('sk_test_abc123');
});

it('does not wipe an existing key when the secret field is left blank', function () {
    $this->store->update(['paymongo_secret_key' => 'sk_test_keep', 'payments_enabled' => true]);

    $this->actingAs($this->owner)
        ->putJson(settingsUrl($this->store->id), ['payments_enabled' => false])
        ->assertOk();

    $this->store->refresh();
    expect($this->store->paymongo_secret_key)->toBe('sk_test_keep');
    expect($this->store->payments_enabled)->toBeFalse();
});

it('disconnects by clearing keys and disabling payments', function () {
    $this->store->update([
        'paymongo_secret_key' => 'sk_test_abc',
        'paymongo_webhook_secret' => 'whsec_abc',
        'payments_enabled' => true,
    ]);

    $this->actingAs($this->owner)
        ->deleteJson(settingsUrl($this->store->id))
        ->assertOk();

    $this->store->refresh();
    expect($this->store->paymongo_secret_key)->toBeNull();
    expect($this->store->payments_enabled)->toBeFalse();
});

it('forbids a staff member from touching payment settings', function () {
    $staff = User::factory()->create();
    $staff->roles()->attach(Role::where('name', 'staff')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);
    $this->store->users()->attach($staff->id, ['id' => (string) Str::uuid(), 'role' => 'staff']);

    $this->actingAs($staff)->getJson(settingsUrl($this->store->id))->assertForbidden();
    $this->actingAs($staff)
        ->putJson(settingsUrl($this->store->id), ['payments_enabled' => true])
        ->assertForbidden();
});
