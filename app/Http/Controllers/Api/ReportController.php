<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Support\AnalyticsData;
use App\Support\PlanUsage;
use App\Support\ReportProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'completed', 'processing', 'failed', 'draft'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Report::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        $status = $validated['status'] ?? 'all';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $search = trim($validated['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        return ReportResource::collection($query->get());
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $report = Report::query()
            ->where('user_id', $request->user()->id)
            ->where('code', $code)
            ->firstOrFail();

        return response()->json([
            'report' => new ReportResource($report),
            'metrics' => [
                ['label' => 'Total samples', 'value' => number_format($report->rows)],
                ['label' => 'Pass rate', 'value' => '94.2%', 'delta' => '+1.4% vs prior'],
                ['label' => 'Anomalies', 'value' => '38', 'delta' => '-12 vs prior', 'trend' => 'down'],
                ['label' => 'Avg processing', 'value' => '2.4s'],
            ],
            'insights' => AnalyticsData::insights(),
            'insightTags' => [
                'Correlation: TMP-01 ↔ PRS-01',
                'Confidence 0.92',
                'Suggested action',
            ],
            'trend' => AnalyticsData::trend(),
            'categories' => AnalyticsData::categories(),
            'anomalies' => AnalyticsData::anomalies(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:255'],
            'rows' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['completed', 'processing', 'failed', 'draft'])],
            'size' => ['nullable', 'string', 'max:32'],
            'author' => ['nullable', 'string', 'max:128'],
        ]);

        $size = $validated['size'] ?? '0 KB';
        $planUsage = new PlanUsage($request->user());
        $planUsage->assertCanCreateReport($size);
        $planUsage->assertCanConsumeAiCredits();

        $code = 'rpt_'.str_pad((string) (Report::query()->count() + 1), 3, '0', STR_PAD_LEFT);

        $report = Report::query()->create([
            'user_id' => $request->user()->id,
            'code' => $code,
            'name' => $validated['name'],
            'source' => $validated['source'],
            'rows' => $validated['rows'] ?? 0,
            'status' => 'processing',
            'size' => $size,
            'author' => $validated['author'] ?? $request->user()->name,
        ]);

        $report = (new ReportProcessor())->finalize($report);

        return response()->json([
            'report' => new ReportResource($report),
        ], 201);
    }
}
