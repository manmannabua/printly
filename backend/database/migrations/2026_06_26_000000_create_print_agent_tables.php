<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase-2 auto-print bridge (planning §4.5, §5.3). A store installs a local
 * print agent (biometric-bridge pattern) that authenticates with a token,
 * polls for jobs queued when an order is accepted, prints to a mapped printer,
 * and reports status back into the order state machine.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A local service installed in the store. Authenticates with a bearer
        // token (stored hashed) and manages one or more physical printers.
        Schema::create('print_agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');                       // "Front counter NUC"
            $table->string('token_hash')->unique();       // sha256 of the bearer token
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        // A physical printer the agent drives. capabilities maps paper sizes /
        // colour support so a job can be routed to a printer that can print it.
        Schema::create('printers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('print_agent_id')->nullable()->constrained('print_agents')->nullOnDelete();
            $table->string('name');                       // "HP LaserJet (A4 mono)"
            $table->json('capabilities')->nullable();     // { "sizes": ["A4"], "color": false }
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });

        // One job per printable file. Created on order accept (when auto-print is
        // on); the agent claims, prints, and reports status — which flows back
        // into the order state machine (accepted → in_progress → ready).
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignUuid('order_file_id')->constrained('order_files')->cascadeOnDelete();
            $table->foreignUuid('printer_id')->nullable()->constrained('printers')->nullOnDelete();
            $table->string('status')->default('queued');  // queued|sent|printing|done|error
            $table->unsignedInteger('copies')->default(1);
            $table->text('error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('print_agents');
    }
};
