<?php

use App\Models\Report;
use App\Support\ReportProcessor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:finalize-stuck', function (ReportProcessor $processor) {
    $reports = Report::query()->where('status', 'processing')->get();

    if ($reports->isEmpty()) {
        $this->info('Brak raportów w statusie processing.');

        return;
    }

    foreach ($reports as $report) {
        $processor->finalize($report);
        $this->line("Zaktualizowano: {$report->code} → {$report->fresh()->status}");
    }

    $this->info("Zaktualizowano {$reports->count()} raport(ów).");
})->purpose('Finalizuje raporty ze statusem processing');
