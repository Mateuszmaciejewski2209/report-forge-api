<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->name }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; line-height: 1.45; }
        .cover { page-break-after: always; padding-top: 120px; text-align: center; }
        .cover h1 { font-size: 26px; margin: 0 0 8px; color: {{ $brandColor }}; font-weight: bold; }
        .cover .subtitle { font-size: 14px; color: #64748b; margin-bottom: 48px; }
        .cover .logo { font-size: 12px; font-weight: bold; color: {{ $brandColor }}; margin-bottom: 24px; }
        .cover .meta { color: #94a3b8; font-size: 10px; }
        h2 { font-size: 14px; color: #0f172a; margin: 0 0 10px; padding-bottom: 6px; border-bottom: 2px solid {{ $brandColor }}; }
        .section { margin-bottom: 22px; page-break-inside: avoid; }
        .exec { background: #f8fafc; padding: 14px 16px; border-radius: 4px; }
        .exec p { margin: 0 0 8px; }
        .exec ul { margin: 8px 0 0; padding-left: 18px; }
        .exec .issue { margin-top: 10px; padding: 10px; background: #fff7ed; border-left: 3px solid #f59e0b; }
        .kpi-row { width: 100%; margin: 10px 0; }
        .kpi { display: inline-block; width: 18%; vertical-align: top; padding: 10px 8px; margin-right: 1%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; }
        .kpi-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .kpi-value { font-size: 16px; font-weight: bold; color: {{ $brandColor }}; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #0f172a; color: #fff; font-weight: bold; }
        .risk-low { color: #15803d; font-weight: bold; }
        .risk-medium { color: #b45309; font-weight: bold; }
        .risk-high { color: #b91c1c; font-weight: bold; }
        .alert { margin-bottom: 8px; padding: 8px 10px; background: #fef2f2; border-left: 3px solid #ef4444; }
        .alert.warn { background: #fffbeb; border-color: #f59e0b; }
        .chart-table td { text-align: center; }
        .bar { background: {{ $brandColor }}; height: 8px; }
        .recommendations li { margin-bottom: 6px; }
        .appendix { page-break-before: always; font-size: 8px; }
        .footer { margin-top: 24px; color: #94a3b8; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
@php
    $isFinancial = ($analytics['reportType'] ?? '') === 'financial';
    $exec = $analytics['executiveSummary'] ?? null;
    $charts = $analytics['charts'] ?? [];
    $rankings = $analytics['rankings'] ?? [];
    $recommendations = $analytics['recommendations'] ?? [];
@endphp

<div class="cover">
    <div class="logo">{{ $user->company ?: 'ReportForge' }}</div>
    <h1>{{ $isFinancial ? 'Customer Financial Activity Report' : $report->name }}</h1>
    <p class="subtitle">{{ $report->name }}</p>
    <p class="meta">
        Generated {{ $report->created_at?->format('F j, Y H:i') }}<br>
        Source: {{ $report->source }} • {{ number_format($report->rows) }} records
    </p>
</div>

@if($exec)
<div class="section">
    <h2>Executive Summary</h2>
    <div class="exec">
        <p>{{ $exec['intro'] ?? '' }}</p>
        <p><strong>Key findings:</strong></p>
        <ul>
            @foreach(($exec['findings'] ?? []) as $finding)
                <li>{{ $finding }}</li>
            @endforeach
        </ul>
        @if(!empty($exec['issue']))
            <div class="issue"><strong>Potential issue detected:</strong> {{ $exec['issue'] }}</div>
        @endif
    </div>
</div>
@endif

<div class="section">
    <h2>KPI Overview</h2>
    <div class="kpi-row">
        @foreach(($analytics['metrics'] ?? []) as $metric)
            <div class="kpi">
                <div class="kpi-label">{{ $metric['label'] ?? '' }}</div>
                <div class="kpi-value">{{ $metric['value'] ?? '—' }}</div>
            </div>
        @endforeach
    </div>
</div>

@if(!empty($charts['topClients']))
<div class="section">
    <h2>Top 10 clients by spending</h2>
    <table class="chart-table">
        <thead><tr><th>Client</th><th>Spending (index)</th></tr></thead>
        <tbody>
            @foreach($charts['topClients'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format($row['value']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($charts['refundHistogram']))
<div class="section">
    <h2>Refund ratio distribution</h2>
    <table class="chart-table">
        <thead><tr><th>Bucket</th><th>Clients</th></tr></thead>
        <tbody>
            @foreach($charts['refundHistogram'] as $row)
                <tr><td>{{ $row['name'] }}</td><td>{{ $row['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($charts['refundVsTopups']))
<div class="section">
    <h2>Refunds vs topups (top accounts)</h2>
    <table>
        <thead><tr><th>Client</th><th>Refunds</th><th>Topups</th></tr></thead>
        <tbody>
            @foreach($charts['refundVsTopups'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format($row['refunds']) }}</td>
                    <td>{{ number_format($row['topups']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($analytics['anomalies']))
<div class="section">
    <h2>Alerts &amp; anomaly detection</h2>
    @foreach($analytics['anomalies'] as $alert)
        <div class="alert {{ ($alert['s'] ?? '') === 'warn' ? 'warn' : '' }}">
            <strong>{{ $alert['t'] }}</strong> — {{ $alert['m'] }}
        </div>
    @endforeach
</div>
@endif

@if(!empty($rankings))
<div class="section">
    <h2>Client ranking</h2>
    <table>
        <thead>
            <tr><th>Client</th><th>Spending</th><th>Refund ratio</th><th>Risk</th></tr>
        </thead>
        <tbody>
            @foreach($rankings as $row)
                <tr>
                    <td>{{ $row['clientId'] }}</td>
                    <td>{{ $row['spending'] }}</td>
                    <td>{{ $row['refundRatio'] }}</td>
                    <td class="risk-{{ strtolower($row['risk']) }}">{{ $row['risk'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($recommendations))
<div class="section">
    <h2>Recommendations</h2>
    <ul class="recommendations">
        @foreach($recommendations as $rec)
            <li>{{ $rec }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(!empty($analytics['insights']) && !$exec)
<div class="section">
    <h2>Insights</h2>
    <p>{{ $analytics['insights'] }}</p>
</div>
@endif

<div class="appendix section">
    <h2>Appendix — data summary</h2>
    <p>Full dataset: {{ $report->source }} ({{ number_format($report->rows) }} rows). Export the original CSV for complete raw records.</p>
    @if(!empty($analytics['categories']))
        <table>
            <thead><tr><th>Risk tier</th><th>Count</th></tr></thead>
            <tbody>
                @foreach($analytics['categories'] as $cat)
                    <tr><td>{{ $cat['name'] }}</td><td>{{ $cat['value'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<p class="footer">Generated by ReportForge — AI-powered business insights from CSV exports</p>
</body>
</html>
