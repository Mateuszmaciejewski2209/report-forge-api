<?php

namespace App\Support;

class CsvAnalyzer
{
    private const WEEKDAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    /**
     * @return array{
     *     metrics: list<array{label: string, value: string, delta: string, trend?: string}>,
     *     trend: list<array{name: string, value: int, anomalies: int}>,
     *     categories: list<array{name: string, value: int}>,
     *     anomalies: list<array{t: string, m: string, s: string}>,
     *     insights: string,
     *     insightTags: list<string>
     * }
     */
    public function analyze(string $path): array
    {
        $delimiter = $this->detectDelimiter($path);
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $this->emptyAnalytics();
        }

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if ($headerRow === false) {
            fclose($handle);

            return $this->emptyAnalytics();
        }

        $columns = [];
        foreach ($headerRow as $index => $label) {
            $label = trim((string) $label) ?: 'Column '.($index + 1);
            $columns[] = [
                'index' => $index,
                'label' => $label,
                'key' => strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label) ?? 'col_'.$index),
            ];
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isEmptyRow($data)) {
                continue;
            }
            $rows[] = $data;
        }

        fclose($handle);

        $totalRows = count($rows);
        if ($totalRows === 0) {
            return $this->emptyAnalytics();
        }

        $financial = new FinancialCsvAnalyzer();
        if ($financial->matches($columns)) {
            return $financial->analyze($columns, $rows);
        }

        $statusColumn = $this->findStatusColumn($columns);
        $numericColumn = $this->findPrimaryNumericColumn($columns, $rows);

        $categories = $statusColumn !== null
            ? $this->categoriesFromStatus($columns, $rows, $statusColumn)
            : $this->categoriesFromNumeric($rows, $numericColumn);

        $trend = $this->buildTrend($rows, $numericColumn);
        $anomalies = $this->detectAnomalies($columns, $rows, $numericColumn);
        $passRate = $this->passRate($categories);

        return [
            'reportType' => 'generic',
            'metrics' => [
                ['label' => 'Total samples', 'value' => number_format($totalRows), 'delta' => 'Full dataset'],
                ['label' => 'Pass rate', 'value' => $passRate.'%', 'delta' => 'From status column'],
                ['label' => 'Anomalies', 'value' => (string) count($anomalies), 'delta' => count($anomalies) > 0 ? 'Review flagged rows' : 'None detected'],
                ['label' => 'Numeric columns', 'value' => (string) $this->countNumericColumns($columns, $rows), 'delta' => $numericColumn !== null ? $columns[$numericColumn]['label'] : '—'],
            ],
            'trend' => $trend,
            'categories' => $categories,
            'anomalies' => array_slice($anomalies, 0, 12),
            'insights' => $this->buildInsights($totalRows, $categories, $anomalies, $numericColumn !== null ? $columns[$numericColumn]['label'] : null),
            'insightTags' => $this->buildInsightTags($categories, $anomalies),
        ];
    }

    /** @param list<array{index: int, label: string, key: string}> $columns */
    private function findStatusColumn(array $columns): ?int
    {
        foreach ($columns as $i => $col) {
            if (preg_match('/status|stan|state|result|wynik/i', $col['key'].$col['label'])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<array{index: int, label: string, key: string}> $columns
     * @param list<list<string|null>> $rows
     */
    private function findPrimaryNumericColumn(array $columns, array $rows): ?int
    {
        $best = null;
        $bestScore = 0.0;

        foreach ($columns as $i => $col) {
            $numeric = 0;
            foreach ($rows as $row) {
                if ($this->toFloat($row[$col['index']] ?? '') !== null) {
                    $numeric++;
                }
            }
            $score = $numeric / max(count($rows), 1);
            if ($score > $bestScore && $score >= 0.4) {
                $bestScore = $score;
                $best = $i;
            }
        }

        return $best;
    }

    /**
     * @param list<array{index: int, label: string, key: string}> $columns
     * @param list<list<string|null>> $rows
     * @return list<array{name: string, value: int}>
     */
    private function categoriesFromStatus(array $columns, array $rows, int $statusIndex): array
    {
        $buckets = [
            'Pass' => 0,
            'Warn' => 0,
            'Fail' => 0,
            'Review' => 0,
        ];

        foreach ($rows as $row) {
            $raw = strtolower(trim((string) ($row[$statusIndex] ?? '')));
            $bucket = match (true) {
                in_array($raw, ['ok', 'pass', 'passed', 'success', 'zaliczony', '1', 'true'], true) => 'Pass',
                in_array($raw, ['warn', 'warning', 'ostrzeżenie', 'ostrzezenie'], true) => 'Warn',
                in_array($raw, ['fail', 'failed', 'error', 'błąd', 'blad', '0', 'false'], true) => 'Fail',
                $raw !== '' => 'Review',
                default => 'Review',
            };
            $buckets[$bucket]++;
        }

        return collect($buckets)->map(fn ($value, $name) => ['name' => $name, 'value' => $value])->values()->all();
    }

    /** @param list<list<string|null>> $rows */
    private function categoriesFromNumeric(array $rows, ?int $numericIndex): array
    {
        if ($numericIndex === null) {
            return [
                ['name' => 'Pass', 'value' => count($rows)],
                ['name' => 'Warn', 'value' => 0],
                ['name' => 'Fail', 'value' => 0],
                ['name' => 'Review', 'value' => 0],
            ];
        }

        $values = [];
        foreach ($rows as $row) {
            $v = $this->toFloat($row[$numericIndex] ?? '');
            if ($v !== null) {
                $values[] = $v;
            }
        }

        if ($values === []) {
            return $this->categoriesFromNumeric($rows, null);
        }

        sort($values);
        $q1 = $values[(int) floor(count($values) * 0.25)] ?? 0;
        $q3 = $values[(int) floor(count($values) * 0.75)] ?? 0;
        $iqr = max($q3 - $q1, 0.0001);
        $low = $q1 - 1.5 * $iqr;
        $high = $q3 + 1.5 * $iqr;

        $pass = 0;
        $warn = 0;
        $fail = 0;

        foreach ($values as $v) {
            if ($v < $low || $v > $high) {
                $fail++;
            } elseif ($v < $q1 || $v > $q3) {
                $warn++;
            } else {
                $pass++;
            }
        }

        return [
            ['name' => 'Pass', 'value' => $pass],
            ['name' => 'Warn', 'value' => $warn],
            ['name' => 'Fail', 'value' => $fail],
            ['name' => 'Review', 'value' => max(0, count($rows) - $pass - $warn - $fail)],
        ];
    }

    /** @param list<list<string|null>> $rows */
    private function buildTrend(array $rows, ?int $numericIndex): array
    {
        $segments = 7;
        $chunkSize = max(1, (int) ceil(count($rows) / $segments));
        $trend = [];

        for ($i = 0; $i < $segments; $i++) {
            $slice = array_slice($rows, $i * $chunkSize, $chunkSize);
            $sum = 0;
            $count = 0;
            $anomalyCount = 0;

            foreach ($slice as $row) {
                if ($numericIndex !== null) {
                    $v = $this->toFloat($row[$numericIndex] ?? '');
                    if ($v !== null) {
                        $sum += $v;
                        $count++;
                    }
                } else {
                    $count++;
                    $sum += 1;
                }
            }

            $trend[] = [
                'name' => self::WEEKDAY_LABELS[$i],
                'value' => $count > 0 ? (int) round($sum / max($count, 1)) : 0,
                'anomalies' => $anomalyCount,
            ];
        }

        return $trend;
    }

    /**
     * @param list<array{index: int, label: string, key: string}> $columns
     * @param list<list<string|null>> $rows
     * @return list<array{t: string, m: string, s: string}>
     */
    private function detectAnomalies(array $columns, array $rows, ?int $numericIndex): array
    {
        if ($numericIndex === null) {
            return [];
        }

        $values = [];
        foreach ($rows as $idx => $row) {
            $v = $this->toFloat($row[$numericIndex] ?? '');
            if ($v !== null) {
                $values[$idx] = $v;
            }
        }

        if ($values === []) {
            return [];
        }

        $mean = array_sum($values) / count($values);
        $variance = 0.0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $std = sqrt($variance / count($values));
        $threshold = $mean + max($std * 2, abs($mean) * 0.25);

        $anomalies = [];
        $label = $columns[$numericIndex]['label'];

        foreach ($values as $idx => $v) {
            if ($v > $threshold || ($std > 0 && abs($v - $mean) > $std * 2.5)) {
                $anomalies[] = [
                    't' => 'Row '.($idx + 2),
                    'm' => sprintf('%s = %s (avg %s)', $label, $this->formatNumber($v), $this->formatNumber($mean)),
                    's' => 'warn',
                ];
            }
        }

        return $anomalies;
    }

    /** @param list<array{name: string, value: int}> $categories */
    private function passRate(array $categories): string
    {
        $total = array_sum(array_column($categories, 'value'));
        if ($total === 0) {
            return '0';
        }

        $pass = 0;
        foreach ($categories as $cat) {
            if (in_array($cat['name'], ['Pass', 'ok'], true)) {
                $pass += $cat['value'];
            }
        }

        return number_format(($pass / $total) * 100, 1);
    }

    /** @param list<array{name: string, value: int}> $categories */
    private function buildInsights(int $totalRows, array $categories, array $anomalies, ?string $numericLabel): string
    {
        $fail = collect($categories)->firstWhere('name', 'Fail')['value'] ?? 0;
        $warn = collect($categories)->firstWhere('name', 'Warn')['value'] ?? 0;

        $parts = [
            sprintf('Dataset contains %s rows.', number_format($totalRows)),
        ];

        if ($numericLabel) {
            $parts[] = sprintf('Primary numeric signal: %s.', $numericLabel);
        }

        if (count($anomalies) > 0) {
            $parts[] = sprintf('%d outlier readings were flagged for review.', count($anomalies));
        } else {
            $parts[] = 'No strong outliers detected in numeric columns.';
        }

        if ($fail > 0 || $warn > 0) {
            $parts[] = sprintf('Distribution summary: %d warnings, %d failures.', $warn, $fail);
        }

        return implode(' ', $parts);
    }

    /** @param list<array{name: string, value: int}> $categories */
    /** @param list<array{t: string, m: string, s: string}> $anomalies */
    /** @return list<string> */
    private function buildInsightTags(array $categories, array $anomalies): array
    {
        $tags = ['CSV analysis', 'Auto-generated'];

        if (count($anomalies) > 0) {
            $tags[] = sprintf('%d anomalies', count($anomalies));
        }

        $fail = collect($categories)->firstWhere('name', 'Fail')['value'] ?? 0;
        if ($fail > 0) {
            $tags[] = 'Failures detected';
        }

        return $tags;
    }

    /** @param list<array{index: int, label: string, key: string}> $columns */
    /** @param list<list<string|null>> $rows */
    private function countNumericColumns(array $columns, array $rows): int
    {
        $count = 0;
        foreach ($columns as $col) {
            $numeric = 0;
            foreach ($rows as $row) {
                if ($this->toFloat($row[$col['index']] ?? '') !== null) {
                    $numeric++;
                }
            }
            if ($numeric / max(count($rows), 1) >= 0.4) {
                $count++;
            }
        }

        return $count;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function toFloat(string $value): ?float
    {
        $normalized = str_replace([' ', ','], ['', '.'], trim($value));
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ',';
        }

        $line = (string) fgets($handle);
        fclose($handle);

        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    /** @param list<string|null> $data */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /** @return array{metrics: list<array<string, string>>, trend: list<array{name: string, value: int, anomalies: int}>, categories: list<array{name: string, value: int}>, anomalies: list<array{t: string, m: string, s: string}>, insights: string, insightTags: list<string>} */
    /**
     * @param list<array{index: int, label: string, key: string}> $columns
     * @param list<list<string|null>> $rows
     * @return array<string, mixed>
     */
    public function analyzeGenericRows(array $columns, array $rows): array
    {
        $totalRows = count($rows);
        $statusColumn = $this->findStatusColumn($columns);
        $numericColumn = $this->findPrimaryNumericColumn($columns, $rows);
        $categories = $statusColumn !== null
            ? $this->categoriesFromStatus($columns, $rows, $statusColumn)
            : $this->categoriesFromNumeric($rows, $numericColumn);
        $trend = $this->buildTrend($rows, $numericColumn);
        $anomalies = $this->detectAnomalies($columns, $rows, $numericColumn);
        $passRate = $this->passRate($categories);

        return [
            'metrics' => [
                ['label' => 'Total samples', 'value' => number_format($totalRows), 'delta' => 'Full dataset'],
                ['label' => 'Pass rate', 'value' => $passRate.'%', 'delta' => 'From status column'],
                ['label' => 'Anomalies', 'value' => (string) count($anomalies), 'delta' => count($anomalies) > 0 ? 'Review flagged rows' : 'None detected'],
                ['label' => 'Numeric columns', 'value' => (string) $this->countNumericColumns($columns, $rows), 'delta' => $numericColumn !== null ? $columns[$numericColumn]['label'] : '—'],
            ],
            'trend' => $trend,
            'categories' => $categories,
            'anomalies' => array_slice($anomalies, 0, 12),
            'insights' => $this->buildInsights($totalRows, $categories, $anomalies, $numericColumn !== null ? $columns[$numericColumn]['label'] : null),
            'insightTags' => $this->buildInsightTags($categories, $anomalies),
        ];
    }

    private function emptyAnalytics(): array
    {
        return [
            'reportType' => 'generic',
            'metrics' => [
                ['label' => 'Total samples', 'value' => '0', 'delta' => '—'],
                ['label' => 'Pass rate', 'value' => '—', 'delta' => '—'],
                ['label' => 'Anomalies', 'value' => '0', 'delta' => '—'],
                ['label' => 'Numeric columns', 'value' => '0', 'delta' => '—'],
            ],
            'trend' => array_map(fn ($name) => ['name' => $name, 'value' => 0, 'anomalies' => 0], self::WEEKDAY_LABELS),
            'categories' => [
                ['name' => 'Pass', 'value' => 0],
                ['name' => 'Warn', 'value' => 0],
                ['name' => 'Fail', 'value' => 0],
                ['name' => 'Review', 'value' => 0],
            ],
            'anomalies' => [],
            'insights' => 'No data rows found in the uploaded CSV file.',
            'insightTags' => ['Empty dataset'],
        ];
    }
}
