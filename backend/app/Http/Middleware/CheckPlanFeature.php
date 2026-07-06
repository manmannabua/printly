<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Services\PlanCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level plan gate: `->middleware('plan:auto_print')`. Resolves the bound
 * {store} route param and blocks the request (402 Payment Required) unless the
 * store's plan unlocks the feature. This is the server-side half of turning a
 * feature into revenue — the UI shows an upsell, this makes it real.
 */
class CheckPlanFeature
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $store = $request->route('store');

        if ($store instanceof Store && ! $store->allows($feature)) {
            return response()->json([
                'success' => false,
                'message' => 'Your plan does not include this feature. Upgrade to unlock it.',
                'errors' => [
                    'plan' => [
                        'feature' => $feature,
                        'current_plan' => $store->effectivePlan(),
                        'required_plans' => $this->plansWith($feature),
                    ],
                ],
            ], 402);
        }

        return $next($request);
    }

    /**
     * Plans that include the feature (the cheapest is the one to upsell to).
     *
     * @return array<int, string>
     */
    private function plansWith(string $feature): array
    {
        return array_values(array_filter(
            PlanCatalog::codes(),
            fn (string $code) => PlanCatalog::has($code, $feature),
        ));
    }
}
