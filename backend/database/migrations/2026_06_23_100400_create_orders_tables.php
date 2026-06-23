<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An order placed at a store. Drives the queue board + state machine (§3).
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('code')->unique();             // short human/QR code, e.g. PRT-7Q3K
            $table->string('status')->default('pending_payment');
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('fee_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid|authorized|paid|refunded
            $table->string('pay_method')->nullable();            // gcash|card|maya|cash_on_pickup
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        // A line in an order. Snapshots pricing so a later catalog change never
        // rewrites a placed order's price.
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');               // snapshot of the product name
            $table->string('pricing_mode');               // file_based|spec_based (snapshot)
            $table->unsignedInteger('quantity')->default(1);
            $table->json('unit_breakdown')->nullable();    // itemized pricing snapshot
            $table->json('spec_selections')->nullable();   // spec_based chosen options
            $table->unsignedBigInteger('line_total_cents')->default(0);
            $table->timestamps();

            $table->index('order_id');
        });

        // Append-only state-change log — drives both audit and Reverb broadcasts (§4.5).
        Schema::create('order_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type')->default('system'); // system|staff|customer
            $table->uuid('actor_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();      // append-only: no updated_at

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
