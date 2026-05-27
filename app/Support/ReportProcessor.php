<?php

namespace App\Support;

use App\Models\Report;
use App\Models\User;

/**
 * Prosta analiza po utworzeniu raportu z CSV — na razie synchroniczna finalizacja statusu.
 */
class ReportProcessor
{
    public function finalize(Report $report): Report
    {
        if ($report->rows < 1) {
            $report->update(['status' => 'failed']);

            return $report->fresh();
        }

        $user = User::query()->find($report->user_id);

        if ($user) {
            (new PlanUsage($user))->consumeAiCredits();
        }

        $report->update(['status' => 'completed']);

        return $report->fresh();
    }
}
