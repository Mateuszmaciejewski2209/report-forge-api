<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Support\PlanCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BillingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $locale = $request->query('locale', 'pl');
        $planId = PlanCatalog::isValid($user->plan ?? 'free') ? $user->plan : 'free';
        $planConfig = PlanCatalog::get($planId) ?? PlanCatalog::get('free');

        $reportsCount = Report::query()->where('user_id', $user->id)->count();
        $reportsThisMonth = Report::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $reportsLimit = $planConfig['reports_limit'] ?? null;
        $storageMb = (int) ($planConfig['storage_mb'] ?? 10);
        $aiLimit = (int) ($planConfig['ai_credits'] ?? 100);

        $usedStorageMb = $this->estimateStorageMb($user->id);

        $renewsAt = $user->plan_renews_at;

        return response()->json([
            'subscription' => [
                'plan' => $planId,
                'status' => 'active',
                'renewsAt' => $renewsAt?->toIso8601String(),
                'priceDisplay' => PlanCatalog::formatPrice($planId, $locale),
                'currency' => str_starts_with(strtolower($locale), 'pl') ? 'PLN' : 'USD',
            ],
            'usage' => [
                [
                    'key' => 'reportsUsed',
                    'used' => (string) $reportsThisMonth,
                    'max' => $reportsLimit === null ? null : (string) $reportsLimit,
                    'percent' => $reportsLimit === null
                        ? min(100, (int) round(($reportsCount > 0 ? 12 : 0)))
                        : min(100, (int) round(($reportsThisMonth / max($reportsLimit, 1)) * 100)),
                ],
                [
                    'key' => 'storage',
                    'used' => $this->formatStorage($usedStorageMb),
                    'max' => $this->formatStorage($storageMb),
                    'percent' => min(100, (int) round(($usedStorageMb / max($storageMb, 1)) * 100)),
                ],
                [
                    'key' => 'aiCredits',
                    'used' => '0',
                    'max' => (string) $aiLimit,
                    'percent' => 0,
                ],
            ],
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

    private function estimateStorageMb(int $userId): float
    {
        $bytes = Report::query()
            ->where('user_id', $userId)
            ->get()
            ->sum(function ($report) {
                $size = $report->size ?? '0 KB';
                if (preg_match('/([\d.]+)\s*(KB|MB|GB)/i', $size, $m)) {
                    $value = (float) $m[1];
                    $unit = strtoupper($m[2]);

                    return match ($unit) {
                        'GB' => $value * 1024 * 1024 * 1024,
                        'MB' => $value * 1024 * 1024,
                        default => $value * 1024,
                    };
                }

                return 0;
            });

        return round($bytes / (1024 * 1024), 1);
    }

    private function formatStorage(float $mb): string
    {
        if ($mb >= 1024) {
            return round($mb / 1024, 1).' GB';
        }

        return round($mb, 1).' MB';
    }
}
