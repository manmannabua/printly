<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The "kind" of thing printed — defines the pricing & fulfillment flow.
        Schema::create('product_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');                        // "Document printing", "Tarpaulin"
            $table->string('pricing_mode')->default('file_based'); // file_based | spec_based
            $table->string('fulfillment')->default('manual');      // manual | auto (phase 2)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        // A sellable variant under a product type.
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_type_id')->constrained('product_types')->cascadeOnDelete();
            $table->string('name');                         // "Short bond B&W", "2x3 Tarp"
            // file_based: default per-page price; spec_based: flat base price. Centavos.
            $table->unsignedBigInteger('base_price_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'product_type_id', 'is_active']);
        });

        // file_based pricing modifiers (paper size, color, duplex, copies, ...).
        Schema::create('price_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('attribute');                    // paper_size | color | duplex | copies
            $table->string('match_value');                  // A4 | color | bw | ...
            $table->string('modifier_type');                // per_page | per_job | multiplier
            $table->bigInteger('amount_cents')->nullable(); // for per_page / per_job
            $table->decimal('multiplier', 8, 4)->nullable();// for multiplier
            $table->timestamps();

            $table->index(['product_id', 'attribute']);
        });

        // spec_based choices (size/material/...). Surfaced in phase 2.
        Schema::create('product_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');                         // "Size", "Material"
            // [{ "label": "2x3 ft", "price_delta_cents": 5000 }, ...]
            $table->json('choices')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('price_rules');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_types');
    }
};
