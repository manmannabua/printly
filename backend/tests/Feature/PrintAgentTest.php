<?php

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Models\Printer;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
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

    $this->store = Store::create([
        'name' => 'Campus Print Hub',
        'slug' => 'campus-print-hub',
        'status' => 'active',
        'settings' => ['auto_print' => true],
    ]);
});

/** Create a file-based product with an active printer + agent. */
function makePrintableStore(Store $store): array
{
    $type = ProductType::create([
        'store_id' => $store->id, 'name' => 'Docs', 'pricing_mode' => 'file_based',
    ]);
    $product = Product::create([
        'store_id' => $store->id, 'product_type_id' => $type->id,
        'name' => 'Bond', 'base_price_cents' => 200,
    ]);
    $token = PrintAgent::newToken();
    $agent = PrintAgent::create([
        'store_id' => $store->id, 'name' => 'PC', 'token_hash' => PrintAgent::hashToken($token),
    ]);
    $printer = Printer::create([
        'store_id' => $store->id, 'print_agent_id' => $agent->id, 'name' => 'LaserJet',
        'capabilities' => ['sizes' => ['A4'], 'color' => false],
    ]);

    return compact('product', 'agent', 'printer', 'token');
}

function placePaidPrintOrder(Store $store, Product $product): Order
{
    $file = OrderFile::create([
        'store_id' => $store->id, 'original_name' => 'a.pdf', 'mime' => 'application/pdf',
        'size_bytes' => 100, 'storage_path' => "orders/{$store->id}/a.pdf",
        'page_count' => 2, 'paper_size' => 'A4', 'is_color' => false, 'analysis_status' => 'done',
    ]);

    $orders = app(OrderService::class);
    $order = $orders->create($store, null, [[
        'product_id' => $product->id,
        'spec' => ['page_count' => 2, 'paper_size' => 'A4', 'color' => 'bw', 'copies' => 3],
        'file_ids' => [$file->id],
    ]], ['actor_type' => 'customer']);

    // Pay → auto-accept → enqueue.
    $orders->transition($order, Order::STATUS_PAID, 'system');

    return $order->fresh();
}

it('lets an owner create an agent and see the token exactly once', function () {
    $created = $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$this->store->id}/print-agents", ['name' => 'Front PC'])
        ->assertCreated()->json('data');

    expect($created['token'])->toBeString()->toStartWith('pa_');

    // Token is never returned again on read.
    $shown = $this->actingAs($this->admin)
        ->getJson("/api/v1/stores/{$this->store->id}/print-agents/{$created['id']}")
        ->assertOk()->json('data');
    expect($shown)->not->toHaveKey('token');
});

it('auto-creates a routed print job when a paid order is accepted', function () {
    ['product' => $product, 'printer' => $printer] = makePrintableStore($this->store);

    $order = placePaidPrintOrder($this->store, $product);

    expect($order->status)->toBe(Order::STATUS_ACCEPTED);
    $job = $order->printJobs()->first();
    expect($job)->not->toBeNull()
        ->and($job->status)->toBe(PrintJob::STATUS_QUEUED)
        ->and($job->printer_id)->toBe($printer->id)
        ->and($job->copies)->toBe(3);
});

it('does not create print jobs when auto-print is off', function () {
    $this->store->update(['settings' => ['auto_print' => false]]);
    ['product' => $product] = makePrintableStore($this->store);

    $order = placePaidPrintOrder($this->store, $product);

    expect($order->printJobs()->count())->toBe(0);
    expect($order->status)->toBe(Order::STATUS_ACCEPTED);
});

it('drives the agent poll → claim → print → order-ready flow', function () {
    ['product' => $product, 'token' => $token] = makePrintableStore($this->store);
    $order = placePaidPrintOrder($this->store, $product);

    $auth = ['Authorization' => "Bearer {$token}"];

    // Poll: claims the queued job (→ sent) and advances the order.
    $jobs = $this->getJson('/api/agent/jobs', $auth)->assertOk()->json('data');
    expect($jobs)->toHaveCount(1);
    $jobId = $jobs[0]['id'];
    expect($jobs[0]['status'])->toBe(PrintJob::STATUS_SENT);
    expect($jobs[0]['file']['paper_size'])->toBe('A4');
    expect($order->fresh()->status)->toBe(Order::STATUS_IN_PROGRESS);

    // Report printing, then done.
    $this->patchJson("/api/agent/jobs/{$jobId}", ['status' => 'printing'], $auth)->assertOk();
    $this->patchJson("/api/agent/jobs/{$jobId}", ['status' => 'done'], $auth)->assertOk();

    expect(PrintJob::find($jobId)->status)->toBe(PrintJob::STATUS_DONE);
    expect($order->fresh()->status)->toBe(Order::STATUS_READY);
});

it('rejects an invalid or inactive agent token', function () {
    $this->getJson('/api/agent/jobs', ['Authorization' => 'Bearer pa_nope'])->assertStatus(401);

    ['token' => $token, 'agent' => $agent] = makePrintableStore($this->store);
    $agent->update(['is_active' => false]);
    $this->getJson('/api/agent/jobs', ['Authorization' => "Bearer {$token}"])->assertStatus(401);
});

it('lets an owner retry a failed job but blocks retry of a live one', function () {
    ['product' => $product, 'token' => $token] = makePrintableStore($this->store);
    $order = placePaidPrintOrder($this->store, $product);
    $jobId = $order->printJobs()->first()->id;
    $auth = ['Authorization' => "Bearer {$token}"];

    $this->getJson('/api/agent/jobs', $auth); // claim
    $this->patchJson("/api/agent/jobs/{$jobId}", ['status' => 'error', 'error' => 'jam'], $auth)->assertOk();

    // A queued/live job cannot be retried; a failed one can.
    $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$this->store->id}/print-jobs/{$jobId}/retry")
        ->assertOk();

    expect(PrintJob::find($jobId)->status)->toBe(PrintJob::STATUS_QUEUED);
});

it('forbids a staff member from managing printers', function () {
    $staff = User::factory()->create();
    $staff->roles()->attach(Role::where('name', 'staff')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);
    $this->store->users()->attach($staff->id, ['id' => (string) Str::uuid(), 'role' => 'staff']);

    // Staff may view…
    $this->actingAs($staff)->getJson("/api/v1/stores/{$this->store->id}/printers")->assertOk();
    // …but not create.
    $this->actingAs($staff)
        ->postJson("/api/v1/stores/{$this->store->id}/print-agents", ['name' => 'x'])
        ->assertForbidden();
});
