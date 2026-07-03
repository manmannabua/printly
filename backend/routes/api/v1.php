<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Catalog\PriceRuleController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Catalog\ProductTypeController;
use App\Http\Controllers\Api\V1\Catalog\QuoteController;
use App\Http\Controllers\Api\V1\Chat\ChatController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\Order\OrderFileController;
use App\Http\Controllers\Api\V1\PaymentSettingsController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\Printing\PrintAgentController;
use App\Http\Controllers\Api\V1\Printing\PrinterController;
use App\Http\Controllers\Api\V1\Printing\PrintJobController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StoreController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated API (v1) — auth:sanctum + active
|--------------------------------------------------------------------------
| Core skeleton only. Printly domain (stores, catalog, orders, queue,
| payments) is added per planning/01-data-model-and-architecture.md.
*/

// ── Auth / session ────────────────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('me', [AuthController::class, 'me'])->name('me');
    Route::post('change-password', [AuthController::class, 'changePassword'])
        ->middleware('throttle:auth-change-password')->name('change-password');
    Route::post('security-pin', [AuthController::class, 'setSecurityPin'])
        ->middleware('throttle:auth-pin')->name('security-pin');
    Route::post('verify-pin', [AuthController::class, 'verifyPin'])
        ->middleware('throttle:auth-pin')->name('verify-pin');
    Route::post('lock', [AuthController::class, 'lock'])->name('lock');
    Route::get('sessions', [AuthController::class, 'sessions'])->name('sessions');
    Route::delete('sessions/{id}', [AuthController::class, 'destroySession'])->name('sessions.destroy');
    Route::post('logout-other-devices', [AuthController::class, 'logoutOtherDevices'])->name('logout-other-devices');
    Route::post('mobile-logout', [AuthController::class, 'mobileLogout'])->name('mobile-logout');
});

Route::post('broadcasting/auth', [AuthController::class, 'mobileBroadcastAuth'])->name('broadcasting.auth');

// ── Dashboard ───────────────────────────────────────────────────────────
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ── Notifications (per-user) ──────────────────────────────────────────────
Route::prefix('my/notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('read', [NotificationController::class, 'destroyRead'])->name('destroy-read');
    Route::post('{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
});

// ── Chat (internal Admin↔Store) ───────────────────────────────────────────
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('conversations', [ChatController::class, 'index'])->name('index');
    Route::post('conversations', [ChatController::class, 'startInternal'])->name('start');
    Route::get('conversations/by-order/{orderId}', [ChatController::class, 'byOrder'])->name('by-order');
    Route::get('conversations/{id}/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('conversations/{id}/messages', [ChatController::class, 'send'])->name('send');
    Route::post('conversations/{id}/read', [ChatController::class, 'markRead'])->name('read');
    Route::post('conversations/{id}/typing', [ChatController::class, 'typing'])->name('typing');
    Route::post('conversations/{id}/attachments', [ChatController::class, 'uploadAttachment'])->name('attachments');
    Route::patch('messages/{id}', [ChatController::class, 'edit'])->name('edit');
    Route::delete('messages/{id}', [ChatController::class, 'destroy'])->name('destroy');
    Route::post('messages/{id}/reactions', [ChatController::class, 'react'])->name('react');
});

// ── Uploads (images / documents) ────────────────────────────────────────
Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
Route::delete('uploads', [UploadController::class, 'destroy'])->name('uploads.destroy');

// ── Stores ──────────────────────────────────────────────────────────────
Route::middleware('permission:stores.view')->group(function () {
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('stores/{store}', [StoreController::class, 'show'])->name('stores.show');
});
Route::post('stores', [StoreController::class, 'store'])->middleware('permission:stores.create')->name('stores.store');
Route::put('stores/{store}', [StoreController::class, 'update'])->middleware('permission:stores.update')->name('stores.update');
Route::patch('stores/{store}', [StoreController::class, 'update'])->middleware('permission:stores.update');
Route::delete('stores/{store}', [StoreController::class, 'destroy'])->middleware('permission:stores.delete')->name('stores.destroy');

// ── Catalog (nested under a store): product types / products / price rules ─
Route::prefix('stores/{store}')->name('stores.')->group(function () {
    // Product types
    Route::middleware('permission:catalog.view')->group(function () {
        Route::get('product-types', [ProductTypeController::class, 'index'])->name('product-types.index');
        Route::get('product-types/{productType}', [ProductTypeController::class, 'show'])->name('product-types.show');
    });
    Route::post('product-types', [ProductTypeController::class, 'store'])->middleware('permission:catalog.create')->name('product-types.store');
    Route::put('product-types/{productType}', [ProductTypeController::class, 'update'])->middleware('permission:catalog.update')->name('product-types.update');
    Route::patch('product-types/{productType}', [ProductTypeController::class, 'update'])->middleware('permission:catalog.update');
    Route::delete('product-types/{productType}', [ProductTypeController::class, 'destroy'])->middleware('permission:catalog.delete')->name('product-types.destroy');

    // Products
    Route::middleware('permission:catalog.view')->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::post('products/{product}/quote', QuoteController::class)->name('products.quote');
        Route::get('products/{product}/price-rules', [PriceRuleController::class, 'index'])->name('products.price-rules.index');
    });
    Route::post('products', [ProductController::class, 'store'])->middleware('permission:catalog.create')->name('products.store');
    Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:catalog.update')->name('products.update');
    Route::patch('products/{product}', [ProductController::class, 'update'])->middleware('permission:catalog.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:catalog.delete')->name('products.destroy');

    // Price rules
    Route::middleware('permission:catalog.update')->group(function () {
        Route::post('products/{product}/price-rules', [PriceRuleController::class, 'store'])->name('products.price-rules.store');
        Route::put('products/{product}/price-rules/{priceRule}', [PriceRuleController::class, 'update'])->name('products.price-rules.update');
        Route::delete('products/{product}/price-rules/{priceRule}', [PriceRuleController::class, 'destroy'])->name('products.price-rules.destroy');
    });

    // ── Orders: queue board, creation, file uploads, state transitions ──────
    Route::middleware('permission:orders.view')->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('files/{orderFile}', [OrderFileController::class, 'show'])->name('files.show');
        Route::get('files/{orderFile}/download', [OrderFileController::class, 'download'])->name('files.download');
    });
    Route::middleware('permission:orders.process')->group(function () {
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::post('files', [OrderFileController::class, 'store'])->name('files.store');
    });

    // ── Auto-print: agents, printers, and print jobs (owner management) ──────
    Route::middleware('permission:printers.view')->group(function () {
        Route::get('print-agents', [PrintAgentController::class, 'index'])->name('print-agents.index');
        Route::get('print-agents/{printAgent}', [PrintAgentController::class, 'show'])->name('print-agents.show');
        Route::get('printers', [PrinterController::class, 'index'])->name('printers.index');
        Route::get('printers/{printer}', [PrinterController::class, 'show'])->name('printers.show');
        Route::get('print-jobs', [PrintJobController::class, 'index'])->name('print-jobs.index');
    });
    Route::middleware('permission:printers.manage')->group(function () {
        Route::post('print-agents', [PrintAgentController::class, 'store'])->name('print-agents.store');
        Route::put('print-agents/{printAgent}', [PrintAgentController::class, 'update'])->name('print-agents.update');
        Route::patch('print-agents/{printAgent}', [PrintAgentController::class, 'update']);
        Route::post('print-agents/{printAgent}/regenerate-token', [PrintAgentController::class, 'regenerateToken'])->name('print-agents.regenerate');
        Route::delete('print-agents/{printAgent}', [PrintAgentController::class, 'destroy'])->name('print-agents.destroy');

        Route::post('printers', [PrinterController::class, 'store'])->name('printers.store');
        Route::put('printers/{printer}', [PrinterController::class, 'update'])->name('printers.update');
        Route::patch('printers/{printer}', [PrinterController::class, 'update']);
        Route::delete('printers/{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');

        Route::post('print-jobs/{printJob}/retry', [PrintJobController::class, 'retry'])->name('print-jobs.retry');
    });

    // ── Payment settings: the store's own PayMongo connection (owner-only) ───
    Route::middleware('permission:stores.update')->group(function () {
        Route::get('payment-settings', [PaymentSettingsController::class, 'show'])->name('payment-settings.show');
        Route::put('payment-settings', [PaymentSettingsController::class, 'update'])->name('payment-settings.update');
        Route::delete('payment-settings', [PaymentSettingsController::class, 'destroy'])->name('payment-settings.destroy');
    });
});

// ── Administration: users / roles / permissions / audit ─────────────────
Route::middleware('permission:users.view')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
});
Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
Route::post('users/{user}/activate', [UserController::class, 'activate'])->middleware('permission:users.activate')->name('users.activate');
Route::post('users/{user}/roles', [UserController::class, 'assignRoles'])->middleware('permission:users.update')->name('users.roles');

Route::middleware('permission:roles.view')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
});
Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
Route::post('roles/{role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:roles.update')->name('roles.permissions');
Route::post('roles/{role}/users', [RoleController::class, 'addUser'])->middleware('permission:roles.update')->name('roles.users.add');
Route::delete('roles/{role}/users/{user}', [RoleController::class, 'removeUser'])->middleware('permission:roles.update')->name('roles.users.remove');

Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:roles.view')->name('permissions.index');

Route::middleware('permission:audit-logs.view')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
});
