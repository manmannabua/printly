<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
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

// ── Uploads (images / documents) ────────────────────────────────────────
Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
Route::delete('uploads', [UploadController::class, 'destroy'])->name('uploads.destroy');

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
