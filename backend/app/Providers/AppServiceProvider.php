<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Point password-reset emails at the Vue frontend, not the Laravel backend
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $email = urlencode($notifiable->getEmailForPasswordReset());
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email={$email}";
        });

        // Rate limiter for login (SEC-01)
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email', '').'|'.$request->ip());
        });

        // Rate limiter for forgot/reset password (SEC-01)
        RateLimiter::for('auth-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('auth-change-password', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth-pin', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('permission-override-sync', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Public buyer inquiry submissions.
        RateLimiter::for('inquiry-submit', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Public storefront reads (catalog, quote, status polling).
        RateLimiter::for('storefront', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Public storefront writes (guest uploads + order placement) — tighter.
        RateLimiter::for('storefront-write', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
