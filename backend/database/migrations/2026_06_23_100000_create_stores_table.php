<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();          // public storefront URL: /s/{slug}
            $table->string('plan')->default('starter'); // starter | pro | auto
            $table->string('status')->default('trial'); // active | suspended | trial
            $table->string('timezone')->default('Asia/Manila');
            $table->string('currency', 3)->default('PHP');
            $table->decimal('lat', 10, 7)->nullable();  // marketplace (phase 2)
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->json('settings')->nullable();       // accepts_guest, pay_on_pickup_allowed, auto_print, ...
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
