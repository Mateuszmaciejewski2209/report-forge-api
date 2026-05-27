<?php

namespace App\Support\Reports;

enum ReportSection: string
{
    case Cover = 'cover';
    case HeroKpi = 'hero_kpi';
    case ExecutiveSummary = 'executive_summary';
    case ActionRequired = 'action_required';
    case KpiOverview = 'kpi_overview';
    case HeroChart = 'hero_chart';
    case ChartsTopClients = 'charts_top_clients';
    case ChartsRefundHistogram = 'charts_refund_histogram';
    case ChartsRefundVsTopups = 'charts_refund_vs_topups';
    case ChartsCashbackScatter = 'charts_cashback_scatter';
    case Anomalies = 'anomalies';
    case RiskAnalysis = 'risk_analysis';
    case Rankings = 'rankings';
    case Recommendations = 'recommendations';
    case Statistics = 'statistics';
    case Insights = 'insights';
    case Appendix = 'appendix';
}
