<?php

namespace App\Support;

use App\Exceptions\PlanLimitExceeded;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;

class PlanUsage
{
    public function __construct(private readonly User $user) {}

    /** @return array<string, mixed> */
    public function planConfig(): array
    {
        $planId = PlanCatalog::isValid($this->user->plan ?? 'free') ? $this->user->plan : 'free';

        return PlanCatalog::get($planId) ?? PlanCatalog::get('free');
    }

    public function reportsThisMonth(): int
    {
        return Report::query()
            ->where('user_id', $this->user->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    public function reportsLimit(): ?int
    {
        $limit = $this->planConfig()['reports_limit'] ?? null;

        return $limit === null ? null : (int) $limit;
    }

    public function usedStorageMb(): float
    {
        $bytes = Report::query()
            ->where('user_id', $this->user->id)
            ->get()
            ->sum(fn (Report $report) => StorageSize::parseToBytes($report->size ?? '0 KB'));

        return StorageSize::bytesToMb((int) $bytes);
    }

    public function storageLimitMb(): int
    {
        return (int) ($this->planConfig()['storage_mb'] ?? 10);
    }

    public function maxUploadBytes(): int
    {
        $mb = (int) ($this->planConfig()['max_upload_mb'] ?? 10);

        return $mb * 1024 * 1024;
    }

    public function aiCreditsLimit(): int
    {
        return (int) ($this->planConfig()['ai_credits'] ?? 100);
    }

    public function aiCreditsUsed(): int
    {
        $this->syncUsagePeriod();

        return (int) $this->user->ai_credits_used;
    }

    public function assertCanUploadFile(int $fileSizeBytes): void
    {
        if ($fileSizeBytes > $this->maxUploadBytes()) {
            throw new PlanLimitExceeded(
                'upload_size',
                'File exceeds the maximum size allowed for your plan.',
            );
        }

        $addedMb = StorageSize::bytesToMb($fileSizeBytes);
        $projected = $this->usedStorageMb() + $addedMb;

        if ($projected > $this->storageLimitMb()) {
            throw new PlanLimitExceeded(
                'storage',
                'Total storage limit for your plan would be exceeded.',
            );
        }
    }

    public function assertCanCreateReport(string $sizeLabel): void
    {
        $limit = $this->reportsLimit();

        if ($limit !== null && $this->reportsThisMonth() >= $limit) {
            throw new PlanLimitExceeded(
                'reports',
                'Monthly report limit for your plan has been reached.',
            );
        }

        $addedMb = StorageSize::bytesToMb(StorageSize::parseToBytes($sizeLabel));
        $projected = $this->usedStorageMb() + $addedMb;

        if ($projected > $this->storageLimitMb()) {
            throw new PlanLimitExceeded(
                'storage',
                'Total storage limit for your plan would be exceeded.',
            );
        }
    }

    public function assertCanConsumeAiCredits(int $amount = 1): void
    {
        $this->syncUsagePeriod();

        if ($this->aiCreditsUsed() + $amount > $this->aiCreditsLimit()) {
            throw new PlanLimitExceeded(
                'ai_credits',
                'AI credits limit for your plan has been reached.',
            );
        }
    }

    public function consumeAiCredits(int $amount = 1): void
    {
        $this->syncUsagePeriod();
        $this->user->increment('ai_credits_used', $amount);
        $this->user->refresh();
    }

    private function syncUsagePeriod(): void
    {
        $current = Carbon::now()->format('Y-m');

        if ($this->user->usage_period !== $current) {
            $this->user->forceFill([
                'usage_period' => $current,
                'ai_credits_used' => 0,
            ])->save();
        }
    }

    /** @return array{reports: array{key: string, used: string, max: string|null, percent: int|null}, storage: array{key: string, used: string, max: string|null, percent: int}, ai: array{key: string, used: string, max: string|null, percent: int}} */
    public function usageSnapshot(): array
    {
        $reportsLimit = $this->reportsLimit();
        $reportsUsed = $this->reportsThisMonth();
        $storageUsed = $this->usedStorageMb();
        $storageLimit = $this->storageLimitMb();
        $aiUsed = $this->aiCreditsUsed();
        $aiLimit = $this->aiCreditsLimit();

        return [
            'reports' => [
                'key' => 'reportsUsed',
                'used' => (string) $reportsUsed,
                'max' => $reportsLimit === null ? null : (string) $reportsLimit,
                'percent' => $reportsLimit === null
                    ? null
                    : $this->percent($reportsUsed, $reportsLimit),
            ],
            'storage' => [
                'key' => 'storage',
                'used' => StorageSize::formatMb($storageUsed),
                'max' => StorageSize::formatMb((float) $storageLimit),
                'percent' => $this->percent($storageUsed, $storageLimit),
            ],
            'ai' => [
                'key' => 'aiCredits',
                'used' => (string) $aiUsed,
                'max' => (string) $aiLimit,
                'percent' => $this->percent($aiUsed, $aiLimit),
            ],
        ];
    }

    private function percent(float|int $used, float|int $max): int
    {
        if ($max <= 0) {
            return 0;
        }

        return min(100, (int) round(($used / $max) * 100));
    }
}
