<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $reports = Report::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        $all = (clone $reports)->get();
        $monthStart = Carbon::now()->startOfMonth();

        $reportsThisMonth = $all->filter(
            fn (Report $r) => $r->created_at !== null && $r->created_at->gte($monthStart),
        )->count();

        $completed = $all->where('status', 'completed')->count();
        $failed = $all->where('status', 'failed')->count();
        $processing = $all->where('status', 'processing')->count();
        $draft = $all->where('status', 'draft')->count();
        $totalRows = $all->sum('rows');

        $recentReports = (clone $reports)->limit(5)->get();

        return response()->json([
            'stats' => [
                ['label' => 'Reports this month', 'value' => (string) $reportsThisMonth, 'delta' => '—'],
                ['label' => 'CSV uploads', 'value' => (string) $all->count(), 'delta' => '—'],
                ['label' => 'Anomalies detected', 'value' => '0', 'delta' => '—'],
                ['label' => 'AI insights', 'value' => '0', 'delta' => '—'],
            ],
            'trend' => $this->trendForUser($userId),
            'categories' => [
                ['name' => 'completed', 'value' => $completed],
                ['name' => 'processing', 'value' => $processing],
                ['name' => 'failed', 'value' => $failed],
                ['name' => 'draft', 'value' => $draft],
            ],
            'recentReports' => ReportResource::collection($recentReports),
            'meta' => [
                'totalReports' => $all->count(),
                'totalRows' => $totalRows,
            ],
        ]);
    }

    /** @return list<array{name: string, value: int, anomalies: int}> */
    private function trendForUser(int $userId): array
    {
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $result = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $count = Report::query()
                ->where('user_id', $userId)
                ->whereDate('created_at', $day->toDateString())
                ->count();

            $result[] = [
                'name' => $labels[$i],
                'value' => $count,
                'anomalies' => 0,
            ];
        }

        return $result;
    }
}
