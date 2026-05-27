<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CsvParser;
use App\Support\CsvStorage;
use App\Support\PlanUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $user = $request->user();

        (new PlanUsage($user))->assertCanUploadFile($file->getSize());

        $csvToken = (new CsvStorage())->storeUpload($user, $file);
        $path = (new CsvStorage())->absolutePath($user, $csvToken);
        $parsed = $path ? (new CsvParser())->parse($path) : [
            'columns' => [],
            'rows' => [],
            'totalRows' => 0,
            'previewCount' => 0,
        ];

        $sizeKb = (int) ceil($file->getSize() / 1024);

        return response()->json([
            'file' => [
                'name' => $file->getClientOriginalName(),
                'size' => $sizeKb >= 1024
                    ? round($sizeKb / 1024, 1).' MB'
                    : $sizeKb.' KB',
            ],
            'preview' => $parsed,
            'csvToken' => $csvToken,
            'suggestedReport' => [
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'source' => $file->getClientOriginalName(),
                'rows' => max($parsed['totalRows'], 0),
                'size' => $sizeKb >= 1024
                    ? round($sizeKb / 1024, 1).' MB'
                    : $sizeKb.' KB',
            ],
        ]);
    }
}
