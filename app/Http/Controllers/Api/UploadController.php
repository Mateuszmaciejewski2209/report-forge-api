<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AnalyticsData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ]);

        $file = $request->file('file');
        $name = $file->getClientOriginalName();
        $sizeKb = (int) ceil($file->getSize() / 1024);
        $rows = $this->countCsvRows($file->getRealPath());
        $preview = $this->parseCsvPreview($file->getRealPath());

        return response()->json([
            'file' => [
                'name' => $name,
                'size' => $sizeKb.' KB',
            ],
            'preview' => [
                'rows' => $preview,
                'totalRows' => $rows,
                'previewCount' => count($preview),
                'columns' => count($preview) > 0 ? count(array_keys($preview[0])) : 0,
            ],
            'suggestedReport' => [
                'name' => pathinfo($name, PATHINFO_FILENAME),
                'source' => $name,
                'rows' => $rows,
                'size' => $sizeKb >= 1024
                    ? round($sizeKb / 1024, 1).' MB'
                    : $sizeKb.' KB',
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseCsvPreview(string $path, int $limit = 8): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return AnalyticsData::csvPreviewRows();
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return AnalyticsData::csvPreviewRows();
        }

        $normalizedHeaders = array_map(
            fn (string $header) => strtolower(trim($header)),
            $headers,
        );

        $rows = [];
        $id = 1;

        while (($data = fgetcsv($handle)) !== false && count($rows) < $limit) {
            if (count($data) === 1 && ($data[0] === null || $data[0] === '')) {
                continue;
            }

            $mapped = array_combine($normalizedHeaders, $data);
            if ($mapped === false) {
                continue;
            }

            $rows[] = [
                'id' => $id++,
                'batch' => $mapped['batch'] ?? $mapped['id'] ?? '—',
                'sensor' => $mapped['sensor'] ?? '—',
                'value' => isset($mapped['value']) ? (float) $mapped['value'] : 0,
                'unit' => $mapped['unit'] ?? '',
                'status' => $mapped['status'] ?? 'ok',
                'time' => $mapped['time'] ?? '',
            ];
        }

        fclose($handle);

        return $rows !== [] ? $rows : AnalyticsData::csvPreviewRows();
    }

    private function countCsvRows(string $path): int
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }

        $count = 0;
        $isHeader = true;

        while (fgetcsv($handle) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }
            $count++;
        }

        fclose($handle);

        return max($count, 1);
    }
}
