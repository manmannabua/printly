<?php

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fileForOrder(Order $order, Store $store): OrderFile
{
    $item = $order->items()->create([
        'store_id' => $store->id, 'product_id' => null, 'product_name' => 'Doc',
        'pricing_mode' => 'file_based', 'quantity' => 1, 'line_total_cents' => 100,
    ]);
    $path = "orders/{$store->id}/{$item->id}.pdf";
    Storage::disk('private')->put($path, '%PDF fake');

    return OrderFile::create([
        'store_id' => $store->id, 'order_item_id' => $item->id,
        'original_name' => 'doc.pdf', 'mime' => 'application/pdf', 'size_bytes' => 9,
        'storage_path' => $path, 'analysis_status' => 'done',
    ]);
}

beforeEach(function () {
    Storage::fake('private');
    $this->store = Store::create([
        'name' => 'Hub', 'slug' => 'hub', 'plan' => 'pro', 'status' => 'active',
    ]);
});

it('purges files for orders terminal longer than the retention window', function () {
    // Old completed order — eligible.
    $old = $this->store->orders()->create([
        'code' => 'PRT-OLD', 'status' => Order::STATUS_COMPLETED,
        'completed_at' => now()->subDays(10), 'placed_at' => now()->subDays(11),
    ]);
    $oldFile = fileForOrder($old, $this->store);

    // Recently completed — keep.
    $recent = $this->store->orders()->create([
        'code' => 'PRT-NEW', 'status' => Order::STATUS_COMPLETED,
        'completed_at' => now()->subDay(), 'placed_at' => now()->subDays(2),
    ]);
    $recentFile = fileForOrder($recent, $this->store);

    // Active order — never purged regardless of age.
    $active = $this->store->orders()->create([
        'code' => 'PRT-ACT', 'status' => Order::STATUS_IN_PROGRESS, 'placed_at' => now()->subDays(30),
    ]);
    $activeFile = fileForOrder($active, $this->store);

    $this->artisan('orders:purge-files')->assertSuccessful();

    expect(OrderFile::find($oldFile->id))->toBeNull();
    Storage::disk('private')->assertMissing($oldFile->storage_path);

    expect(OrderFile::find($recentFile->id))->not->toBeNull()
        ->and(OrderFile::find($activeFile->id))->not->toBeNull();
    Storage::disk('private')->assertExists($recentFile->storage_path);
});
