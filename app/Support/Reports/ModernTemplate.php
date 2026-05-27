<?php

namespace App\Support\Reports;

/** Quick business overview — client-ready, chart-forward. */
class ModernTemplate extends AbstractReportTemplate
{
    public function id(): string
    {
        return 'modern';
    }

    public function label(): string
    {
        return 'Modern';
    }

    public function sections(): array
    {
        return [
            ReportSection::Cover,
            ReportSection::HeroKpi,
            ReportSection::HeroChart,
            ReportSection::ExecutiveSummary,
            ReportSection::KpiOverview,
            ReportSection::ChartsTopClients,
            ReportSection::ChartsRefundHistogram,
            ReportSection::Recommendations,
        ];
    }

    public function density(): string
    {
        return 'spacious';
    }

    public function maxKpis(): int
    {
        return 4;
    }

    public function maxRankings(): int
    {
        return 0;
    }

    public function maxRecommendations(): int
    {
        return 3;
    }
}
