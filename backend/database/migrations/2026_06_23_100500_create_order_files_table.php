<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uploaded files for printing. Created at upload time (before an order
        // exists) then linked to an order_item at checkout — so order_item_id is
        // nullable. Analysis (page count / paper size) runs async on a queue.
        Schema::create('order_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained('order_items')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('storage_path');                 // on the private disk
            // analysis results (nullable until analysed):
            $table->unsignedInteger('page_count')->nullable();
            $table->string('paper_size')->nullable();
            $table->boolean('is_color')->nullable();
            $table->unsignedInteger('color_pages')->nullable();
            $table->string('preview_path')->nullable();
            $table->string('analysis_status')->default('pending'); // pending|done|failed
            $table->text('analysis_error')->nullable();
            $table->timestamps();

            $table->index('order_item_id');
            $table->index('analysis_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_files');
    }
};
