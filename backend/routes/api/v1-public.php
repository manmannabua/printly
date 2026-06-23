<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Order\PublicOrderStatusController;
use App\Http\Controllers\Api\V1\Storefront\StorefrontController;
use App\Http\Controllers\Api\V1\Storefront\StorefrontFileController;
use App\Http\Controllers\Api\V1\Storefront\StorefrontOrderController;
use App\Http\Controllers\Api\V1\Storefront\StorefrontQuoteController;
use App\Http\Controllers\Api\V1\Webhooks\PaymongoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (v1) — no authentication
|--------------------------------------------------------------------------
| Printly customer-facing storefront endpoints (/s/{slug}, file upload,
| quote, orders, guest checkout) are added per the data-model doc.
*/

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:auth-login')
    ->name('login');

Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:auth-password')
    ->name('forgot-password');

Route::post('reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:auth-password')
    ->name('reset-password');

Route::post('mobile-login', [AuthController::class, 'mobileLogin'])
    ->middleware('throttle:auth-login')
    ->name('mobile-login');

// Customer order status by QR/order code (the code is the bearer).
Route::get('orders/{code}', [PublicOrderStatusController::class, 'show'])->name('orders.status');

// ── Storefront (guest-friendly, slug-scoped) — planning §7 ──────────────────
Route::middleware('throttle:storefront')->group(function () {
    Route::get('s/{slug}', [StorefrontController::class, 'show'])->name('storefront.show');
    Route::get('s/{slug}/files/{orderFile}', [StorefrontFileController::class, 'show'])->name('storefront.files.show');
    Route::post('s/{slug}/quote', StorefrontQuoteController::class)->name('storefront.quote');
});

Route::middleware('throttle:storefront-write')->group(function () {
    Route::post('s/{slug}/files', [StorefrontFileController::class, 'store'])->name('storefront.files.store');
    Route::post('s/{slug}/orders', [StorefrontOrderController::class, 'store'])->name('storefront.orders.store');
});

// PayMongo webhook (per store — each store uses its own account). Signature-verified.
Route::post('webhooks/paymongo/{store}', PaymongoWebhookController::class)->name('webhooks.paymongo');
