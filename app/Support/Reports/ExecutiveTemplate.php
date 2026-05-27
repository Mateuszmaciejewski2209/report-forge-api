<?php

namespace App\Support\Reports;

/** Decision-making report — insights, risk, actions. */
class ExecutiveTemplate extends AbstractReportTemplate
{
    public function id(): string
    {
        return 'executive';
    }

    public function label(): string
    {
        return 'Executive';
    }

    public function sections(): array
    {
        return [
            ReportSection::Cover,
            ReportSection::ExecutiveSummary,
            ReportSection::ActionRequired,
            ReportSection::KpiOverview,
            ReportSection::Anomalies,
            ReportSection::RiskAnalysis,
            ReportSection::ChartsRefundVsTopups,
            ReportSection::Recommendations,
            ReportSection::Rankings,
        ];
    }

    public function density(): string
    {
        return 'balanced';
    }

    public function maxKpis(): int
    {
        return 5;
    }

    public function maxRankings(): int
    {
        return 15;
    }

    public function maxRecommendations(): int
    {
        return 6;
    }
}
