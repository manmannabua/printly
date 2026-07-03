<?php

namespace App\Http\Middleware;

use App\Models\PrintAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a local print agent by its bearer token (sha256-hashed at rest),
 * binds the resolved PrintAgent onto the request, and records a heartbeat. Used
 * only by the /api/agent/* routes — no session, no CSRF, no Sanctum user.
 */
class AuthenticatePrintAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'Missing agent token.'], 401);
        }

        $agent = PrintAgent::where('token_hash', PrintAgent::hashToken($token))
            ->where('is_active', true)
            ->first();

        if (! $agent) {
            return response()->json(['success' => false, 'message' => 'Invalid or inactive agent token.'], 401);
        }

        // Heartbeat — cheap, avoids touching updated_at churn elsewhere.
        $agent->forceFill(['last_seen_at' => now()])->saveQuietly();

        $request->attributes->set('print_agent', $agent);

        return $next($request);
    }
}
