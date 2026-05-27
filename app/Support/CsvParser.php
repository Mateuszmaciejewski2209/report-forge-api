<?php

namespace App\Support;

class CsvParser
{
    /**
     * @return array{
     *     columns: list<array{key: string, label: string}>,
     *     rows: list<array<string, string>>,
     *     totalRows: int,
     *     previewCount: int
     * }
     */
    public function parse(string $path, int $previewLimit = 8): array
    {
        $delimiter = $this->detectDelimiter($path);
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $this->emptyResult();
        }

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if ($headerRow === false || $headerRow === [null]) {
            fclose($handle);

            return $this->emptyResult();
        }

        $labels = array_map(fn ($cell) => trim((string) $cell), $headerRow);
        $usedKeys = [];
        $columns = [];

        foreach ($labels as $index => $label) {
            if ($label === '') {
                $label = 'Kolumna '.($index + 1);
            }
            $key = $this->columnKey($label, $index, $usedKeys);
            $columns[] = ['key' => $key, 'label' => $label];
        }

        if ($columns === []) {
            fclose($handle);

            return $this->emptyResult();
        }

        $rows = [];
        $totalRows = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $totalRows++;
            if (count($rows) >= $previewLimit) {
                continue;
            }

            $row = [];
            foreach ($columns as $i => $column) {
                $raw = $data[$i] ?? '';
                $row[$column['key']] = $this->formatCell((string) $raw);
            }
            $rows[] = $row;
        }

        fclose($handle);

        return [
            'columns' => $columns,
            'rows' => $rows,
            'totalRows' => $totalRows,
            'previewCount' => count($rows),
        ];
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ',';
        }

        $line = (string) fgets($handle);
        fclose($handle);

        $commas = substr_count($line, ',');
        $semicolons = substr_count($line, ';');

        return $semicolons > $commas ? ';' : ',';
    }

    /** @param list<string> $used */
    private function columnKey(string $label, int $index, array &$used): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label) ?? '');
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'column_'.($index + 1);
        }

        $key = $base;
        $suffix = 1;
        while (in_array($key, $used, true)) {
            $key = $base.'_'.$suffix;
            $suffix++;
        }

        $used[] = $key;

        return $key;
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

    private function formatCell(string $value): string
    {
        $trimmed = trim($value);
        if (strlen($trimmed) > 120) {
            return substr($trimmed, 0, 117).'…';
        }

        return $trimmed;
    }

    /** @return array{columns: list<array{key: string, label: string}>, rows: list<array<string, string>>, totalRows: int, previewCount: int} */
    private function emptyResult(): array
    {
        return [
            'columns' => [],
            'rows' => [],
            'totalRows' => 0,
            'previewCount' => 0,
        ];
    }
}
