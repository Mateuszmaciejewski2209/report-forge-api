<?php

namespace App\Support;

use App\Models\Report;
use App\Models\User;
use App\Support\Reports\ReportTemplatePresenter;
use App\Support\Reports\ReportTemplateRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportPdfGenerator
{
    public function generate(Report $report, User $user): string
    {
        $raw = is_array($report->analytics) ? $report->analytics : [];
        $template = (new ReportTemplateRegistry())->resolve($report->template ?? 'modern');
        $analytics = (new ReportTemplatePresenter($template))->prepare($raw);
        $brandColor = $user->brand_color ?? '#3b5bdb';

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'user' => $user,
            'analytics' => $analytics,
            'brandColor' => $brandColor,
            'layout' => $analytics['layout'] ?? ['density' => 'spacious', 'sections' => []],
            'template' => $template,
        ])->setPaper('a4');

        $relativePath = 'reports/'.$report->code.'.pdf';
        Storage::disk('local')->put($relativePath, $pdf->output());

        $report->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }
}
