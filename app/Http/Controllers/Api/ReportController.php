<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Support\AnalyticsData;
use App\Support\CsvAnalyzer;
use App\Support\CsvStorage;
use App\Support\PlanUsage;
use App\Support\Reports\ReportTemplatePresenter;
use App\Support\Reports\ReportTemplateRegistry;
use App\Support\ReportPdfGenerator;
use App\Support\ReportProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            $needle = '%'.addcslashes(mb_strtolower($search), '%_\\').'%';
            $query->where(function ($builder) use ($needle) {
                $builder
                    ->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(source) LIKE ?', [$needle]);
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

        $analytics = $this->resolveAnalytics($report);
        $template = (new ReportTemplateRegistry())->resolve($report->template ?? 'modern');
        $presented = (new ReportTemplatePresenter($template))->prepare($analytics);

        return response()->json(array_merge(
            ['report' => new ReportResource($report)],
            $presented,
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:255'],
            'rows' => ['nullable', 'integer', 'min:0'],
            'size' => ['nullable', 'string', 'max:32'],
            'author' => ['nullable', 'string', 'max:128'],
            'csvToken' => ['required', 'string', 'uuid'],
            'template' => ['nullable', 'string', Rule::in(ReportTemplateRegistry::allowedIds())],
        ]);

        $user = $request->user();
        $size = $validated['size'] ?? '0 KB';
        $planUsage = new PlanUsage($user);
        $planUsage->assertCanCreateReport($size);
        $planUsage->assertCanConsumeAiCredits();

        $code = 'rpt_'.str_pad((string) (Report::query()->count() + 1), 3, '0', STR_PAD_LEFT);
        $csvStorage = new CsvStorage();
        $csvPath = $csvStorage->moveToReport($user, $validated['csvToken'], $code);

        if ($csvPath === null) {
            return response()->json([
                'message' => 'Upload session expired. Please upload the CSV again.',
                'error' => 'csv_token_invalid',
            ], 422);
        }

        $absoluteCsv = Storage::disk('local')->path($csvPath);
        $analytics = (new CsvAnalyzer())->analyze($absoluteCsv);
        $templateId = $validated['template'] ?? $user->pdf_template ?? 'modern';
        if (! in_array($templateId, ReportTemplateRegistry::allowedIds(), true)) {
            $templateId = 'modern';
        }

        $report = Report::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'name' => $validated['name'],
            'source' => $validated['source'],
            'rows' => $validated['rows'] ?? 0,
            'status' => 'processing',
            'template' => $templateId,
            'size' => $size,
            'author' => $validated['author'] ?? $user->name,
            'csv_path' => $csvPath,
            'analytics' => $analytics,
        ]);

        $report = (new ReportProcessor())->finalize($report);

        try {
            (new ReportPdfGenerator())->generate($report->fresh(), $user);
        } catch (\Throwable) {
            // Raport pozostaje ukończony nawet gdy PDF się nie wygeneruje
        }

        return response()->json([
            'report' => new ReportResource($report->fresh()),
        ], 201);
    }

    public function pdf(Request $request, string $code): StreamedResponse|JsonResponse
    {
        $report = Report::query()
            ->where('user_id', $request->user()->id)
            ->where('code', $code)
            ->firstOrFail();

        if (! $report->hasPdf() || ! Storage::disk('local')->exists($report->pdf_path)) {
            return response()->json(['message' => 'PDF not available for this report.'], 404);
        }

        $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $report->name).'.pdf';

        return Storage::disk('local')->download($report->pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** @return array<string, mixed> */
    private function resolveAnalytics(Report $report): array
    {
        if (is_array($report->analytics) && $report->analytics !== []) {
            return $report->analytics;
        }

        return [
            'reportType' => 'generic',
            'metrics' => [
                ['label' => 'Total samples', 'value' => number_format($report->rows)],
                ['label' => 'Pass rate', 'value' => '—', 'delta' => 'Legacy report'],
                ['label' => 'Anomalies', 'value' => '—', 'delta' => 'Re-upload CSV'],
                ['label' => 'Avg processing', 'value' => '—', 'delta' => '—'],
            ],
            'insights' => AnalyticsData::insights(),
            'insightTags' => ['Legacy data'],
            'trend' => AnalyticsData::trend(),
            'categories' => AnalyticsData::categories(),
            'anomalies' => AnalyticsData::anomalies(),
        ];
    }
}
