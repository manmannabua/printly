<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Printly → store subscription (planning §4.4). This is the platform's revenue
 * ledger, kept deliberately separate from the customer→store payments table so
 * an offline/cash-only store still carries a subscription. Flat monthly model;
 * billing is admin-managed for now (schema is ready for automated PSP billing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // One live subscription per store — the store's current plan.
            $table->foreignUuid('store_id')->unique()->constrained('stores')->cascadeOnDelete();
            $table->string('plan')->default('starter');     // starter | pro | auto
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->string('status')->default('trialing');   // trialing | active | past_due | canceled
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
