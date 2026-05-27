<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PlanCatalog;
use App\Support\PlanUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = $request->query('locale', 'pl');
        $planId = PlanCatalog::isValid($user->plan ?? 'free') ? $user->plan : 'free';
        $usage = new PlanUsage($user);
        $snapshot = $usage->usageSnapshot();

        $renewsAt = $user->plan_renews_at;

        return response()->json([
            'subscription' => [
                'plan' => $planId,
                'status' => 'active',
                'renewsAt' => $renewsAt?->toIso8601String(),
                'priceDisplay' => PlanCatalog::formatPrice($planId, $locale),
                'currency' => str_starts_with(strtolower($locale), 'pl') ? 'PLN' : 'USD',
            ],
            'usage' => array_values($snapshot),
            'plans' => collect(PlanCatalog::ids())->map(function (string $id) use ($planId, $locale) {
                $cfg = PlanCatalog::get($id);

                return [
                    'id' => $id,
                    'pricePln' => (int) ($cfg['price_pln'] ?? 0),
                    'priceUsd' => (int) ($cfg['price_usd'] ?? 0),
                    'priceDisplay' => PlanCatalog::formatPrice($id, $locale),
                    'current' => $id === $planId,
                ];
            })->values()->all(),
        ]);
    }
}
