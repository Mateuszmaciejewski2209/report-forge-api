<?php

namespace App\Support\Reports;

/**
 * Filtruje pełną analitykę pod wybrany szablon (jeden engine, różne layouty).
 */
class ReportTemplatePresenter
{
    public function __construct(
        private readonly ReportTemplateInterface $template,
    ) {}

    /** @param array<string, mixed> $analytics */
    public function prepare(array $analytics): array
    {
        $metrics = array_slice($analytics['metrics'] ?? [], 0, $this->template->maxKpis());
        $rankings = array_slice($analytics['rankings'] ?? [], 0, $this->template->maxRankings());
        $recommendations = array_slice($analytics['recommendations'] ?? [], 0, $this->template->maxRecommendations());

        $charts = $analytics['charts'] ?? [];
        $filteredCharts = [];
        $flags = $this->template->sectionFlags();

        if ($flags[ReportSection::ChartsTopClients->value] ?? false) {
            $filteredCharts['topClients'] = $charts['topClients'] ?? [];
        }
        if ($flags[ReportSection::ChartsRefundHistogram->value] ?? false) {
            $filteredCharts['refundHistogram'] = $charts['refundHistogram'] ?? [];
        }
        if ($flags[ReportSection::ChartsRefundVsTopups->value] ?? false) {
            $filteredCharts['refundVsTopups'] = $charts['refundVsTopups'] ?? [];
        }
        if ($flags[ReportSection::ChartsCashbackScatter->value] ?? false) {
            $filteredCharts['cashbackScatter'] = $charts['cashbackScatter'] ?? [];
        }

        $heroChart = [];
        if ($flags[ReportSection::HeroChart->value] ?? false) {
            $heroChart = array_slice($charts['topClients'] ?? [], 0, 10);
        }

        return array_merge($analytics, [
            'metrics' => $metrics,
            'rankings' => $rankings,
            'recommendations' => $recommendations,
            'charts' => $filteredCharts,
            'heroChart' => $heroChart,
            'template' => $this->template->id(),
            'templateLabel' => $this->template->label(),
            'layout' => [
                'density' => $this->template->density(),
                'sections' => $this->template->sectionFlags(),
            ],
        ]);
    }
}
