<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->string('upload_token_hash')->nullable()->after('storage_path');
            $table->timestamp('upload_token_expires_at')->nullable()->after('upload_token_hash');

            $table->index('upload_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->dropIndex(['upload_token_expires_at']);
            $table->dropColumn(['upload_token_hash', 'upload_token_expires_at']);
        });
    }
};
