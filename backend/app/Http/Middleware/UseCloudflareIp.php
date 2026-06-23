<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replace REMOTE_ADDR with the real visitor IP from CF-Connecting-IP.
 *
 * Without this, every request looks like it comes from a Cloudflare egress IP
 * (e.g., 162.159.x.x) — auth/sessions, audit logs, and any IP-based logic
 * become useless for forensics. Pair with TrustProxies and Authenticated
 * Origin Pulls so the header can't be spoofed by a direct connection.
 */
class UseCloudflareIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $cf = $request->header('CF-Connecting-IP');
        if ($cf !== null && $cf !== '') {
            $request->server->set('REMOTE_ADDR', $cf);
            // X-Forwarded-For is what TrustProxies + request->ip() consult.
            // Prepend the real IP so it wins over any Cloudflare egress hop.
            $existing = $request->headers->get('X-Forwarded-For', '');
            $request->headers->set(
                'X-Forwarded-For',
                $existing === '' ? $cf : $cf . ', ' . $existing,
            );
        }

        return $next($request);
    }
}
