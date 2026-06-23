<?php

use App\Http\Controllers\Api\V1\AuthController;
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
