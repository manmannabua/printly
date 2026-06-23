<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a per-request CSP nonce up front so blade templates rendered
        // by the controller can reference it via csp_nonce(). The same value is
        // emitted in the Content-Security-Policy header below.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp.nonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()');

        // HSTS — defense-in-depth alongside the Cloudflare edge HSTS toggle.
        // Skipped in local so http://localhost dev still works.
        if (!app()->environment('local', 'testing')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
        }

        // Strict CSP — script-src/script-src-elem use a per-request nonce
        // instead of 'unsafe-inline' so any reflected/stored XSS that lands
        // markup in the DOM cannot execute via inline handlers.
        $isLocal = app()->environment('local');
        $viteOrigin = 'http://127.0.0.1:5173';

        // Origin where /storage/* assets are served (Laravel backend).
        // On the careers subdomain this is cross-origin, so img-src must
        // include it explicitly — 'self' does not cover it.
        $appUrl = parse_url((string) config('app.url'));
        $appOrigin = isset($appUrl['scheme'], $appUrl['host'])
            ? $appUrl['scheme'] . '://' . $appUrl['host'] . (isset($appUrl['port']) ? ':' . $appUrl['port'] : '')
            : '';

        $csp = implode('; ', [
            "script-src 'self' 'nonce-{$nonce}'" . ($isLocal ? " $viteOrigin" : ''),
            "script-src-elem 'self' 'nonce-{$nonce}'" . ($isLocal ? " $viteOrigin" : ''),
            "style-src 'self' 'unsafe-inline'",
            "connect-src 'self'" . ($isLocal ? " $viteOrigin ws://127.0.0.1:5173" : ''),
            "img-src 'self' data: blob:" . ($appOrigin !== '' ? " $appOrigin" : ''),
            "font-src 'self' data:",
            "worker-src 'self' blob:",
            "frame-ancestors 'none'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
