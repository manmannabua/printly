<?php

namespace App\Services;

/**
 * Read-only accessor over config/plans.php — the plan catalogue. Keeps every
 * "what does this plan include?" question in one place so gating (middleware,
 * Store::allows) and the UI (plan comparison) never drift apart.
 */
class PlanCatalog
{
    /**
     * All plan codes in upgrade order (cheapest → richest).
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_values(config('plans.order', array_keys(config('plans.plans', []))));
    }

    public static function default(): string
    {
        return (string) config('plans.default', 'starter');
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && array_key_exists($code, config('plans.plans', []));
    }

    /**
     * A plan's full definition, or the default plan's if the code is unknown.
     *
     * @return array<string, mixed>
     */
    public static function get(?string $code): array
    {
        $plans = config('plans.plans', []);

        return $plans[$code] ?? $plans[self::default()] ?? [];
    }

    /**
     * Every plan keyed by code, each augmented with its own `code` (handy for
     * the frontend comparison grid).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return array_map(
            fn (string $code) => ['code' => $code] + self::get($code),
            self::codes(),
        );
    }

    /**
     * Feature keys a plan unlocks.
     *
     * @return array<int, string>
     */
    public static function features(?string $code): array
    {
        return array_values(self::get($code)['features'] ?? []);
    }

    /**
     * Whether a plan includes a feature (e.g. 'auto_print', 'online_payments').
     */
    public static function has(?string $code, string $feature): bool
    {
        return in_array($feature, self::features($code), true);
    }

    /**
     * A numeric limit for a plan, or null for "unlimited"/unknown.
     */
    public static function limit(?string $code, string $key): ?int
    {
        $value = self::get($code)['limits'][$key] ?? null;

        return $value === null ? null : (int) $value;
    }

    public static function priceCents(?string $code): int
    {
        return (int) (self::get($code)['price_cents'] ?? 0);
    }

    public static function name(?string $code): string
    {
        return (string) (self::get($code)['name'] ?? ucfirst((string) $code));
    }

    /**
     * Rank in the upgrade ladder (0 = cheapest). Unknown plans sort first.
     */
    public static function rank(?string $code): int
    {
        $index = array_search($code, self::codes(), true);

        return $index === false ? 0 : (int) $index;
    }
}
