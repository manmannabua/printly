<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Owns the Printly → store subscription: creating one for a new store, and
 * changing plans. Billing is admin-managed for now (no PSP charge), so a plan
 * change here simply records the new plan and mirrors it onto stores.plan; the
 * status/period fields are shaped so automated billing can slot in later.
 */
class SubscriptionService
{
    /** Trial length for a brand-new store on the default (free) plan. */
    private const TRIAL_DAYS = 14;

    /**
     * Make sure a store has a subscription. New stores start on the default
     * plan with a trial window. Idempotent — returns the existing one if any.
     */
    public function ensureFor(Store $store): Subscription
    {
        if ($existing = $store->subscription()->first()) {
            return $existing;
        }

        $plan = PlanCatalog::isValid($store->plan) ? $store->plan : PlanCatalog::default();

        return DB::transaction(function () use ($store, $plan) {
            // Keep the denormalised column in step from the very first write.
            if ($store->plan !== $plan) {
                $store->update(['plan' => $plan]);
            }

            $isFree = PlanCatalog::priceCents($plan) === 0;

            return $store->subscription()->create([
                'plan' => $plan,
                'price_cents' => PlanCatalog::priceCents($plan),
                // A free default plan is simply "active"; paid plans open on trial.
                'status' => $isFree ? Subscription::STATUS_ACTIVE : Subscription::STATUS_TRIALING,
                'trial_ends_at' => $isFree ? null : now()->addDays(self::TRIAL_DAYS),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        });
    }

    /**
     * Move a store onto a different plan. Mirrors the plan onto stores.plan,
     * (re)opens the billing period, and audits the change.
     */
    public function changePlan(Store $store, string $plan, string $actor = 'admin'): Subscription
    {
        if (! PlanCatalog::isValid($plan)) {
            throw new InvalidArgumentException("Unknown plan: {$plan}");
        }

        $subscription = $this->ensureFor($store);
        $from = $subscription->plan;

        if ($from === $plan) {
            return $subscription;
        }

        return DB::transaction(function () use ($store, $subscription, $from, $plan, $actor) {
            $isFree = PlanCatalog::priceCents($plan) === 0;

            $subscription->update([
                'plan' => $plan,
                'price_cents' => PlanCatalog::priceCents($plan),
                // Downgrades to a free plan settle immediately; paid plans are
                // "active" here because billing is manual (no charge to await).
                'status' => Subscription::STATUS_ACTIVE,
                'canceled_at' => null,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);

            $store->update(['plan' => $plan]);

            AuditLog::log($store, 'subscription_plan_changed', ['plan' => $from], [
                'plan' => $plan,
                'price_cents' => PlanCatalog::priceCents($plan),
                'actor' => $actor,
                'free' => $isFree,
            ]);

            return $subscription->fresh();
        });
    }
}
