<?php

namespace App\Support\Reports;

/** Detailed raw-data report — tables, stats, appendix. */
class TechnicalTemplate extends AbstractReportTemplate
{
    public function id(): string
    {
        return 'technical';
    }

    public function label(): string
    {
        return 'Technical';
    }

    public function sections(): array
    {
        return [
            ReportSection::Cover,
            ReportSection::KpiOverview,
            ReportSection::Statistics,
            ReportSection::ChartsTopClients,
            ReportSection::ChartsRefundHistogram,
            ReportSection::ChartsRefundVsTopups,
            ReportSection::ChartsCashbackScatter,
            ReportSection::Anomalies,
            ReportSection::Rankings,
            ReportSection::Appendix,
        ];
    }

    public function density(): string
    {
        return 'dense';
    }

    public function maxKpis(): int
    {
        return 5;
    }

    public function maxRankings(): int
    {
        return 25;
    }

    public function maxRecommendations(): int
    {
        return 0;
    }
}
