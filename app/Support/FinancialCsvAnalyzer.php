<?php

namespace App\Support;

/**
 * Analiza CSV finansowo-operacyjnego (klienci, wydatki, zwroty, cashback).
 */
class FinancialCsvAnalyzer
{
    /** @param list<array{index: int, label: string, key: string}> $columns */
    public function matches(array $columns): bool
    {
        $keys = array_column($columns, 'key');
        $hasClient = $this->findColumnIndex($columns, ['client', 'customer', 'user']) !== null;
        $hasSpend = $this->findColumnIndex($columns, ['spent', 'spending', 'revenue', 'total_spent']) !== null;

        return $hasClient && $hasSpend;
    }

    /**
     * @param list<array{index: int, label: string, key: string}> $columns
     * @param list<list<string|null>> $rows
     * @return array<string, mixed>
     */
    public function analyze(array $columns, array $rows): array
    {
        $map = [
            'client' => $this->findColumnIndex($columns, ['client_id', 'client', 'customer', 'user_id', 'customer_id']),
            'topups' => $this->findColumnIndex($columns, ['topups_real', 'topups', 'topup']),
            'refunds' => $this->findColumnIndex($columns, ['refunds_from_cancellations', 'refunds', 'refund_amount']),
            'cancelCount' => $this->findColumnIndex($columns, ['cancellation_refunds_count', 'cancellations', 'cancel_count']),
            'cashback' => $this->findColumnIndex($columns, ['cashback_credited_total', 'cashback']),
            'spent' => $this->findColumnIndex($columns, ['total_spent', 'spent', 'spending', 'revenue']),
            'ratio' => $this->findColumnIndex($columns, ['refund_to_topup_ratio', 'refund_ratio', 'ratio']),
        ];

        $clients = [];
        foreach ($rows as $row) {
            if ($map['client'] === null) {
                break;
            }

            $id = trim((string) ($row[$map['client']] ?? ''));
            if ($id === '') {
                continue;
            }

            $topups = $map['topups'] !== null ? ($this->toFloat($row[$map['topups']] ?? '') ?? 0.0) : 0.0;
            $refunds = $map['refunds'] !== null ? ($this->toFloat($row[$map['refunds']] ?? '') ?? 0.0) : 0.0;
            $spent = $map['spent'] !== null ? ($this->toFloat($row[$map['spent']] ?? '') ?? 0.0) : 0.0;
            $cashback = $map['cashback'] !== null ? ($this->toFloat($row[$map['cashback']] ?? '') ?? 0.0) : 0.0;
            $ratioRaw = $map['ratio'] !== null ? $this->toFloat($row[$map['ratio']] ?? '') : null;

            $ratio = $ratioRaw;
            if ($ratio === null && $topups > 0) {
                $ratio = ($refunds / $topups) * 100;
            } elseif ($ratio !== null && $ratio <= 1.5) {
                $ratio *= 100;
            }

            $clients[] = [
                'id' => $id,
                'topups' => $topups,
                'refunds' => $refunds,
                'spent' => $spent,
                'cashback' => $cashback,
                'ratio' => $ratio ?? 0.0,
            ];
        }

        if ($clients === []) {
            return array_merge(['reportType' => 'generic'], (new CsvAnalyzer())->analyzeGenericRows($columns, $rows));
        }

        $totalClients = count($clients);
        $totalSpent = array_sum(array_column($clients, 'spent'));
        $totalCashback = array_sum(array_column($clients, 'cashback'));
        $totalRefunds = array_sum(array_column($clients, 'refunds'));
        $totalTopups = array_sum(array_column($clients, 'topups'));
        $ratios = array_column($clients, 'ratio');
        $avgRatio = array_sum($ratios) / max(count($ratios), 1);

        $sortedRatio = $ratios;
        sort($sortedRatio);
        $p95Index = (int) floor(count($sortedRatio) * 0.95);
        $p95Ratio = $sortedRatio[min($p95Index, count($sortedRatio) - 1)] ?? $avgRatio;

        $rankings = $this->buildRankings($clients, $avgRatio, $p95Ratio);
        $highRisk = count(array_filter($rankings, fn ($r) => $r['risk'] === 'High'));

        $topSpender = collect($clients)->sortByDesc('spent')->first();
        $anomalies = $this->buildAlerts($clients, $avgRatio, $p95Ratio, $highRisk);
        $recommendations = $this->buildRecommendations($highRisk, $avgRatio, $p95Ratio);

        $executive = $this->buildExecutiveSummary(
            $totalClients,
            $totalSpent,
            $avgRatio,
            $highRisk,
            $topSpender,
            $anomalies,
        );

        return [
            'reportType' => 'financial',
            'executiveSummary' => $executive,
            'metrics' => [
                ['label' => 'Total Clients', 'value' => number_format($totalClients), 'delta' => 'Unique accounts'],
                ['label' => 'Total Spent', 'value' => $this->formatCompact($totalSpent), 'delta' => 'Sum of total_spent'],
                ['label' => 'Avg Refund Ratio', 'value' => number_format($avgRatio, 1).'%', 'delta' => 'Refunds vs topups'],
                ['label' => 'Total Cashback', 'value' => $this->formatCompact($totalCashback), 'delta' => 'Credited cashback'],
                ['label' => 'High Risk Clients', 'value' => (string) $highRisk, 'delta' => 'Elevated refund ratio'],
            ],
            'charts' => [
                'topClients' => $this->topClientsChart($clients, 10),
                'refundHistogram' => $this->refundHistogram($ratios),
                'cashbackScatter' => $this->cashbackScatter($clients, 24),
                'refundVsTopups' => $this->refundVsTopupsChart($clients, 12),
            ],
            'rankings' => array_slice($rankings, 0, 25),
            'recommendations' => $recommendations,
            'statistics' => $this->buildStatistics($ratios, $clients),
            'trend' => $this->refundVsTopupsChart($clients, 7),
            'categories' => [
                ['name' => 'Low', 'value' => count(array_filter($rankings, fn ($r) => $r['risk'] === 'Low'))],
                ['name' => 'Medium', 'value' => count(array_filter($rankings, fn ($r) => $r['risk'] === 'Medium'))],
                ['name' => 'High', 'value' => $highRisk],
            ],
            'anomalies' => $anomalies,
            'insights' => $executive['intro'].' '.implode(' ', $executive['findings']),
            'insightTags' => ['Financial analysis', 'Risk scoring', sprintf('%d alerts', count($anomalies))],
            'totals' => [
                'refunds' => $totalRefunds,
                'topups' => $totalTopups,
            ],
        ];
    }

    /**
     * @param list<array{index: int, label: string, key: string}> $columns
     * @param list<string> $needles
     */
    private function findColumnIndex(array $columns, array $needles): ?int
    {
        foreach ($columns as $col) {
            $hay = $col['key'].' '.$col['label'];
            foreach ($needles as $needle) {
                if (str_contains(strtolower($hay), strtolower($needle))) {
                    return $col['index'];
                }
            }
        }

        return null;
    }

    /** @param list<array{id: string, topups: float, refunds: float, spent: float, cashback: float, ratio: float}> $clients */
    /** @return list<array{clientId: string, spending: string, refundRatio: string, risk: string}> */
    private function buildRankings(array $clients, float $avgRatio, float $p95Ratio): array
    {
        $ranked = collect($clients)
            ->sortByDesc('spent')
            ->values()
            ->map(function (array $c) use ($avgRatio, $p95Ratio) {
                $risk = 'Low';
                if ($c['ratio'] >= max(50, $p95Ratio) || $c['ratio'] >= $avgRatio * 2) {
                    $risk = 'High';
                } elseif ($c['ratio'] >= 25 || $c['ratio'] >= $p95Ratio * 0.85) {
                    $risk = 'Medium';
                }

                return [
                    'clientId' => $c['id'],
                    'spending' => $this->formatCompact($c['spent']),
                    'refundRatio' => number_format($c['ratio'], 1).'%',
                    'risk' => $risk,
                ];
            })
            ->all();

        return $ranked;
    }

    /** @param list<array{id: string, ratio: float, spent: float}> $clients */
    /** @return list<array{t: string, m: string, s: string}> */
    private function buildAlerts(array $clients, float $avgRatio, float $p95Ratio, int $highRisk): array
    {
        $alerts = [];

        $worst = collect($clients)->sortByDesc('ratio')->first();
        if ($worst && $worst['ratio'] > $avgRatio * 1.5) {
            $alerts[] = [
                't' => 'Alert 1',
                'm' => sprintf(
                    'Client %s has an unusually high refund-to-topup ratio of %s%%, significantly above the dataset average (%s%%).',
                    $worst['id'],
                    number_format($worst['ratio'], 1),
                    number_format($avgRatio, 1),
                ),
                's' => 'fail',
            ];
        }

        if ($highRisk > 0) {
            $alerts[] = [
                't' => 'Alert 2',
                'm' => sprintf('%d clients exceed elevated refund thresholds (95th percentile: %s%%).', $highRisk, number_format($p95Ratio, 1)),
                's' => 'warn',
            ];
        }

        $suspiciousCashback = collect($clients)
            ->filter(fn ($c) => $c['spent'] > 0 && $c['cashback'] > $c['spent'] * 0.5)
            ->count();

        if ($suspiciousCashback > 0) {
            $alerts[] = [
                't' => 'Alert 3',
                'm' => sprintf('Cashback appears disproportionately high for %d low-spending accounts — review engagement incentives.', $suspiciousCashback),
                's' => 'warn',
            ];
        }

        return $alerts;
    }

    /** @return list<string> */
    private function buildRecommendations(int $highRisk, float $avgRatio, float $p95Ratio): array
    {
        $items = [
            'Review customers with refund ratios above 50%.',
            'Audit cancellation-heavy accounts for operational inefficiencies.',
        ];

        if ($highRisk > 0) {
            $items[] = sprintf('Prioritize %d high-risk clients identified in the ranking table.', $highRisk);
        }

        if ($avgRatio > 20) {
            $items[] = 'Optimize cashback policies for accounts with low net spend.';
        }

        $items[] = sprintf('Monitor clients above the 95th percentile refund threshold (%s%%).', number_format($p95Ratio, 1));

        return $items;
    }

    /**
     * @param array{id: string, spent: float}|null $topSpender
     * @param list<array{t: string, m: string, s: string}> $anomalies
     * @return array{intro: string, findings: list<string>, issue: string|null}
     */
    private function buildExecutiveSummary(
        int $totalClients,
        float $totalSpent,
        float $avgRatio,
        int $highRisk,
        ?array $topSpender,
        array $anomalies,
    ): array {
        $findings = [
            sprintf('Total customer spending reached %s.', $this->formatCompact($totalSpent)),
            sprintf('Average refund-to-topup ratio was %s%%.', number_format($avgRatio, 1)),
            sprintf('%d clients exceeded the anomaly threshold for refunds.', $highRisk),
        ];

        if ($topSpender) {
            $findings[] = sprintf(
                'The highest spending customer (%s) generated %s in transactions.',
                $topSpender['id'],
                $this->formatCompact($topSpender['spent']),
            );
        }

        $findings[] = 'Cashback distribution indicates engagement concentration among top-tier users.';

        $issue = count($anomalies) > 0
            ? 'Several customers show unusually high cancellation refund ratios, which may indicate operational inefficiencies or abuse patterns.'
            : null;

        return [
            'intro' => sprintf('The dataset contains activity data for %s clients.', number_format($totalClients)),
            'findings' => $findings,
            'issue' => $issue,
        ];
    }

    /** @param list<array{id: string, spent: float}> $clients */
    /** @return list<array{name: string, value: int}> */
    private function topClientsChart(array $clients, int $limit): array
    {
        return collect($clients)
            ->sortByDesc('spent')
            ->take($limit)
            ->map(fn ($c) => ['name' => 'Client '.$c['id'], 'value' => (int) round($c['spent'])])
            ->values()
            ->all();
    }

    /** @param list<float> $ratios */
    /** @return list<array{name: string, value: int}> */
    private function refundHistogram(array $ratios): array
    {
        $buckets = [
            '0–10%' => 0,
            '10–25%' => 0,
            '25–50%' => 0,
            '50–100%' => 0,
            '100%+' => 0,
        ];

        foreach ($ratios as $r) {
            if ($r < 10) {
                $buckets['0–10%']++;
            } elseif ($r < 25) {
                $buckets['10–25%']++;
            } elseif ($r < 50) {
                $buckets['25–50%']++;
            } elseif ($r < 100) {
                $buckets['50–100%']++;
            } else {
                $buckets['100%+']++;
            }
        }

        return collect($buckets)->map(fn ($v, $k) => ['name' => $k, 'value' => $v])->values()->all();
    }

    /** @param list<array{id: string, spent: float, cashback: float}> $clients */
    /** @return list<array{name: string, x: int, y: int}> */
    private function cashbackScatter(array $clients, int $limit): array
    {
        return collect($clients)
            ->sortByDesc('spent')
            ->take($limit)
            ->map(fn ($c) => [
                'name' => $c['id'],
                'x' => (int) round($c['spent']),
                'y' => (int) round($c['cashback']),
            ])
            ->values()
            ->all();
    }

    /** @param list<array{id: string, topups: float, refunds: float}> $clients */
    /** @return list<array{name: string, refunds: int, topups: int}> */
    private function refundVsTopupsChart(array $clients, int $limit): array
    {
        return collect($clients)
            ->sortByDesc('refunds')
            ->take($limit)
            ->map(fn ($c) => [
                'name' => $c['id'],
                'refunds' => (int) round($c['refunds']),
                'topups' => (int) round($c['topups']),
            ])
            ->values()
            ->all();
    }

    /**
     * @param list<float> $ratios
     * @param list<array{spent: float, topups: float, refunds: float, cashback: float}> $clients
     * @return list<array{label: string, value: string}>
     */
    private function buildStatistics(array $ratios, array $clients): array
    {
        if ($ratios === []) {
            return [];
        }

        $sorted = $ratios;
        sort($sorted);
        $count = count($sorted);
        $mean = array_sum($sorted) / $count;
        $mid = (int) floor($count / 2);
        $median = $count % 2 === 0
            ? ($sorted[$mid - 1] + $sorted[$mid]) / 2
            : $sorted[$mid];

        $variance = 0.0;
        foreach ($sorted as $r) {
            $variance += ($r - $mean) ** 2;
        }
        $std = sqrt($variance / max($count, 1));

        $p75Index = (int) floor($count * 0.75);
        $p95Index = (int) floor($count * 0.95);
        $spentValues = array_column($clients, 'spent');
        sort($spentValues);

        return [
            ['label' => 'Mean refund ratio', 'value' => number_format($mean, 2).'%'],
            ['label' => 'Median refund ratio', 'value' => number_format($median, 2).'%'],
            ['label' => 'Std deviation (ratio)', 'value' => number_format($std, 2).'%'],
            ['label' => '75th percentile', 'value' => number_format($sorted[min($p75Index, $count - 1)], 2).'%'],
            ['label' => '95th percentile', 'value' => number_format($sorted[min($p95Index, $count - 1)], 2).'%'],
            ['label' => 'Mean total spent', 'value' => $this->formatCompact(array_sum($spentValues) / max($count, 1))],
        ];
    }

    private function formatCompact(float $value): string
    {
        if ($value >= 1_000_000_000) {
            return round($value / 1_000_000_000, 1).'B';
        }
        if ($value >= 1_000_000) {
            return round($value / 1_000_000, 1).'M';
        }
        if ($value >= 1_000) {
            return round($value / 1_000, 1).'K';
        }

        return number_format($value, 0);
    }

    private function toFloat(string $value): ?float
    {
        $normalized = str_replace([' ', ','], ['', '.'], trim($value));
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
