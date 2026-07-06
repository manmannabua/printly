<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Store;
use App\Models\Subscription;
use App\Services\PlanCatalog;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A store's Printly subscription (the revenue relationship, planning §4.4/§6).
 * Owners can view their plan, entitlements, and the upgrade ladder; changing
 * the plan needs `subscriptions.manage` (admin-managed billing for now).
 */
class SubscriptionController extends BaseController
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function show(Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $subscription = $this->subscriptions->ensureFor($store);

        return $this->success($this->payload($store, $subscription));
    }

    /**
     * Change the store's plan. Gated on subscriptions.manage (see route). The
     * request only needs the target plan; billing periods are set server-side.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $data = $request->validate([
            'plan' => ['required', 'string', 'in:'.implode(',', PlanCatalog::codes())],
        ]);

        $actor = $request->user()?->is_admin ? 'admin' : 'owner';
        $subscription = $this->subscriptions->changePlan($store, $data['plan'], $actor);

        return $this->success(
            $this->payload($store->fresh(), $subscription),
            "Plan changed to {$subscription->planName()}.",
        );
    }

    /**
     * Current subscription + live entitlements + the full plan catalogue so the
     * UI can render a comparison grid and highlight the active plan.
     *
     * @return array<string, mixed>
     */
    private function payload(Store $store, Subscription $subscription): array
    {
        $user = request()->user();

        return [
            'plan' => $subscription->plan,
            'plan_name' => $subscription->planName(),
            'status' => $subscription->status,
            'price_cents' => $subscription->price_cents,
            'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
            'current_period_end' => $subscription->current_period_end?->toISOString(),
            'canceled_at' => $subscription->canceled_at?->toISOString(),
            // What the store can actually do right now (respects a lapsed sub).
            'features' => $store->planFeatures(),
            'limits' => PlanCatalog::get($store->effectivePlan())['limits'] ?? [],
            'plans' => PlanCatalog::all(),
            'can_manage' => (bool) ($user?->hasPermission('subscriptions.manage')),
        ];
    }
}
