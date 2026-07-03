<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Public, unauthenticated endpoints (login, password reset).
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api/v1-public.php'));

            // Authenticated application API.
            Route::middleware(['api', 'auth:sanctum', 'active'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api/v1.php'));

            // Local print agents — token auth, no session/CSRF (planning §5.3).
            Route::middleware(['api', 'agent.auth'])
                ->prefix('api/agent')
                ->name('agent.')
                ->group(base_path('routes/api/agent.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // API-only backend: return 401 JSON instead of redirecting to a non-existent login route
        $middleware->redirectGuestsTo(fn () => null);

        // Trust the upstream proxy (Cloudflare/nginx) and read forwarded headers.
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Run before SecurityHeaders so REMOTE_ADDR is the real IP downstream.
        $middleware->prepend(\App\Http\Middleware\UseCloudflareIp::class);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'agent.auth' => \App\Http\Middleware\AuthenticatePrintAgent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Sentry\Laravel\Integration::handles($exceptions);

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });

        // Illegal order state-machine transitions are a client error, not a 500.
        $exceptions->render(function (\App\Exceptions\OrderTransitionException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        });

        // Convert PHP-level coercion failures into 400 instead of leaking 500.
        $exceptions->render(function (\TypeError|\ValueError $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bad request.',
                ], 400);
            }
        });
    })->create();
