<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CsvParser;
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
        (new PlanUsage($request->user()))->assertCanUploadFile($file->getSize());
        $name = $file->getClientOriginalName();
        $sizeKb = (int) ceil($file->getSize() / 1024);
        $parsed = (new CsvParser())->parse($file->getRealPath());

        return response()->json([
            'file' => [
                'name' => $name,
                'size' => $sizeKb >= 1024
                    ? round($sizeKb / 1024, 1).' MB'
                    : $sizeKb.' KB',
            ],
            'preview' => $parsed,
            'suggestedReport' => [
                'name' => pathinfo($name, PATHINFO_FILENAME),
                'source' => $name,
                'rows' => max($parsed['totalRows'], 0),
                'size' => $sizeKb >= 1024
                    ? round($sizeKb / 1024, 1).' MB'
                    : $sizeKb.' KB',
            ],
        ]);
    }
}
