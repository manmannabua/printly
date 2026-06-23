<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores connect their OWN PayMongo account (platform bills subscription
        // only — planning §10). Secrets are encrypted at rest.
        Schema::table('stores', function (Blueprint $table) {
            $table->text('paymongo_secret_key')->nullable();
            $table->text('paymongo_webhook_secret')->nullable();
            $table->boolean('payments_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['paymongo_secret_key', 'paymongo_webhook_secret', 'payments_enabled']);
        });
    }
};
