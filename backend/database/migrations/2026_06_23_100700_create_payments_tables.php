<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer → store payment, per order (planning §4.4).
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('provider')->default('paymongo');
            $table->string('provider_ref')->nullable();          // checkout session id (webhook lookup)
            $table->string('provider_payment_ref')->nullable();  // captured payment id (refunds)
            $table->text('checkout_url')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('status')->default('pending');     // pending|paid|failed|refunded
            $table->json('raw_payload')->nullable();          // webhook audit
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('provider_ref');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');     // pending|succeeded|failed
            $table->string('provider_ref')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
