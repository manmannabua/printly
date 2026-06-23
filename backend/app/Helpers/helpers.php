<?php

if (!function_exists('media_url')) {
    /**
     * Resolve a stored (public-disk) media path to a fully-qualified URL.
     * Returns null for empty paths and passes through absolute URLs as-is.
     */
    function media_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as Philippine Peso currency.
     */
    function format_currency(float|int $amount, int $decimals = 2): string
    {
        return '₱' . number_format($amount, $decimals);
    }
}

if (!function_exists('get_initials')) {
    /**
     * Get initials from a name.
     */
    function get_initials(string $name, int $length = 2): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, $length);
    }
}

if (!function_exists('csp_nonce')) {
    /**
     * Per-request CSP nonce for inline <script> tags that must pass the
     * strict script-src directive emitted by SecurityHeaders middleware.
     *
     * Returns an empty string outside the request lifecycle (e.g., artisan).
     */
    function csp_nonce(): string
    {
        return app()->bound('csp.nonce') ? (string) app('csp.nonce') : '';
    }
}

if (!function_exists('mask_string')) {
    /**
     * Mask a string, showing only first and last characters.
     */
    function mask_string(string $string, int $showFirst = 2, int $showLast = 2, string $maskChar = '*'): string
    {
        $length = strlen($string);

        if ($length <= $showFirst + $showLast) {
            return $string;
        }

        $first = substr($string, 0, $showFirst);
        $last = substr($string, -$showLast);
        $masked = str_repeat($maskChar, $length - $showFirst - $showLast);

        return $first . $masked . $last;
    }
}

if (!function_exists('haversine_distance_km')) {
    /**
     * Great-circle distance between two lat/lng points in kilometres.
     *
     * Used to match buyers to nearby agents (geo agent-routing, Phase 2).
     */
    function haversine_distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
