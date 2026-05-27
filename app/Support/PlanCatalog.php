<?php

namespace App\Support;

class PlanCatalog
{
    /** @return list<string> */
    public static function ids(): array
    {
        return array_keys(config('plans', []));
    }

    /** @return array<string, mixed>|null */
    public static function get(string $planId): ?array
    {
        $plan = config("plans.{$planId}");

        return is_array($plan) ? $plan : null;
    }

    public static function isValid(string $planId): bool
    {
        return self::get($planId) !== null;
    }

    public static function formatPrice(string $planId, string $locale = 'pl'): string
    {
        $plan = self::get($planId) ?? self::get('free');
        $pln = (int) ($plan['price_pln'] ?? 0);
        $usd = (int) ($plan['price_usd'] ?? 0);

        if (str_starts_with(strtolower($locale), 'pl')) {
            return $pln.' zł';
        }

        return '$'.$usd;
    }
}
