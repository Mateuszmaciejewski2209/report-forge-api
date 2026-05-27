<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Support\AnalyticsData;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $recentReports = Report::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                ['label' => 'Reports this month', 'value' => '148', 'delta' => '+12% vs last month'],
                ['label' => 'CSV uploads', 'value' => '312', 'delta' => '+8% vs last month'],
                ['label' => 'Anomalies detected', 'value' => '42', 'delta' => '-3% vs last month', 'trend' => 'down'],
                ['label' => 'AI insights', 'value' => '2,184', 'delta' => '+24% vs last month'],
            ],
            'trend' => AnalyticsData::trend(),
            'categories' => AnalyticsData::categories(),
            'recentReports' => ReportResource::collection($recentReports),
        ]);
    }
}
