<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->name }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; line-height: 1.45; }
        body.density-spacious .section { margin-bottom: 28px; }
        body.density-balanced .section { margin-bottom: 20px; }
        body.density-dense { font-size: 9px; line-height: 1.35; }
        body.density-dense .section { margin-bottom: 14px; }
        .cover { page-break-after: always; padding-top: 100px; text-align: center; }
        body.density-spacious .cover { padding-top: 140px; }
        .cover h1 { font-size: 26px; margin: 0 0 8px; color: {{ $brandColor }}; font-weight: bold; }
        body.density-dense .cover h1 { font-size: 20px; }
        .cover .subtitle { font-size: 14px; color: #64748b; margin-bottom: 32px; }
        .cover .logo { font-size: 12px; font-weight: bold; color: {{ $brandColor }}; margin-bottom: 24px; }
        .cover .meta { color: #94a3b8; font-size: 10px; }
        .cover .tpl { margin-top: 16px; font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; }
        h2 { font-size: 14px; color: #0f172a; margin: 0 0 10px; padding-bottom: 6px; border-bottom: 2px solid {{ $brandColor }}; }
        body.density-dense h2 { font-size: 12px; }
        .section { page-break-inside: avoid; }
        .exec { background: #f8fafc; padding: 14px 16px; border-radius: 4px; }
        .exec p { margin: 0 0 8px; }
        .exec ul { margin: 8px 0 0; padding-left: 18px; }
        .exec .issue { margin-top: 10px; padding: 10px; background: #fff7ed; border-left: 3px solid #f59e0b; }
        .action { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 14px; margin-top: 8px; }
        .hero-kpi { text-align: center; margin: 24px 0; }
        .hero-kpi .big { font-size: 32px; font-weight: bold; color: {{ $brandColor }}; }
        .hero-kpi .lbl { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .kpi-row { width: 100%; margin: 10px 0; }
        .kpi { display: inline-block; vertical-align: top; padding: 10px 8px; margin-right: 1%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; }
        body.density-spacious .kpi { width: 23%; }
        body.density-balanced .kpi { width: 18%; }
        body.density-dense .kpi { width: 18%; padding: 6px; }
        .kpi-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .kpi-value { font-size: 16px; font-weight: bold; color: {{ $brandColor }}; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9px; }
        body.density-dense table { font-size: 8px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        body.density-dense th, body.density-dense td { padding: 4px 6px; }
        th { background: #0f172a; color: #fff; font-weight: bold; }
        .risk-low { color: #15803d; font-weight: bold; }
        .risk-medium { color: #b45309; font-weight: bold; }
        .risk-high { color: #b91c1c; font-weight: bold; }
        .alert { margin-bottom: 8px; padding: 8px 10px; background: #fef2f2; border-left: 3px solid #ef4444; }
        .alert.warn { background: #fffbeb; border-color: #f59e0b; }
        .recommendations li { margin-bottom: 6px; }
        .stats-grid td { width: 33%; }
        .appendix { page-break-before: always; font-size: 8px; }
        .footer { margin-top: 24px; color: #94a3b8; font-size: 8px; text-align: center; }
    </style>
</head>
@php
    $sections = $layout['sections'] ?? [];
    $show = fn (string $key) => !empty($sections[$key]);
    $density = $layout['density'] ?? 'spacious';
    $isFinancial = ($analytics['reportType'] ?? '') === 'financial';
    $exec = $analytics['executiveSummary'] ?? null;
    $charts = $analytics['charts'] ?? [];
    $heroChart = $analytics['heroChart'] ?? [];
    $rankings = $analytics['rankings'] ?? [];
    $recommendations = $analytics['recommendations'] ?? [];
    $statistics = $analytics['statistics'] ?? [];
    $heroMetric = ($analytics['metrics'] ?? [])[0] ?? null;
@endphp
<body class="density-{{ $density }}">
<div class="cover">
    <div class="logo">{{ $user->company ?: 'ReportForge' }}</div>
    <h1>{{ $isFinancial ? 'Customer Financial Activity Report' : $report->name }}</h1>
    <p class="subtitle">{{ $report->name }}</p>
    <p class="meta">
        Generated {{ $report->created_at?->format('F j, Y H:i') }}<br>
        Source: {{ $report->source }} • {{ number_format($report->rows) }} records
    </p>
    <p class="tpl">{{ $template->label() }} template</p>
</div>

@if($show('hero_kpi') && $heroMetric)
<div class="section hero-kpi">
    <div class="big">{{ $heroMetric['value'] ?? '—' }}</div>
    <div class="lbl">{{ $heroMetric['label'] ?? 'Primary KPI' }}</div>
</div>
@endif

@if($show('executive_summary') && $exec)
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

@if($show('action_required') && !empty($exec['issue']))
<div class="section">
    <h2>Action Required</h2>
    <div class="action">{{ $exec['issue'] }}</div>
    @if(!empty($analytics['anomalies']))
        <p style="margin-top:10px;"><strong>{{ count($analytics['anomalies']) }} alert(s)</strong> require management review.</p>
    @endif
</div>
@endif

@if($show('kpi_overview') && !empty($analytics['metrics']))
<div class="section">
    <h2>KPI Overview</h2>
    <div class="kpi-row">
        @foreach($analytics['metrics'] as $metric)
            <div class="kpi">
                <div class="kpi-label">{{ $metric['label'] ?? '' }}</div>
                <div class="kpi-value">{{ $metric['value'] ?? '—' }}</div>
            </div>
        @endforeach
    </div>
</div>
@endif

@if($show('statistics') && !empty($statistics))
<div class="section">
    <h2>Statistical Analysis</h2>
    <table class="stats-grid">
        <tbody>
            @foreach(array_chunk($statistics, 3) as $row)
                <tr>
                    @foreach($row as $stat)
                        <td><strong>{{ $stat['label'] }}</strong><br>{{ $stat['value'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($show('hero_chart') && !empty($heroChart))
<div class="section">
    <h2>Primary chart — top clients by spending</h2>
    <table class="chart-table">
        <thead><tr><th>Client</th><th>Spending (index)</th></tr></thead>
        <tbody>
            @foreach($heroChart as $row)
                <tr><td>{{ $row['name'] }}</td><td>{{ number_format($row['value']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($show('charts_top_clients') && !empty($charts['topClients']))
<div class="section">
    <h2>Top clients by spending</h2>
    <table>
        <thead><tr><th>Client</th><th>Spending (index)</th></tr></thead>
        <tbody>
            @foreach($charts['topClients'] as $row)
                <tr><td>{{ $row['name'] }}</td><td>{{ number_format($row['value']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($show('charts_refund_histogram') && !empty($charts['refundHistogram']))
<div class="section">
    <h2>Refund ratio distribution</h2>
    <table>
        <thead><tr><th>Bucket</th><th>Clients</th></tr></thead>
        <tbody>
            @foreach($charts['refundHistogram'] as $row)
                <tr><td>{{ $row['name'] }}</td><td>{{ $row['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($show('charts_refund_vs_topups') && !empty($charts['refundVsTopups']))
<div class="section">
    <h2>Refunds vs topups</h2>
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

@if($show('charts_cashback_scatter') && !empty($charts['cashbackScatter']))
<div class="section">
    <h2>Cashback vs spending</h2>
    <table>
        <thead><tr><th>Client</th><th>Spending</th><th>Cashback</th></tr></thead>
        <tbody>
            @foreach($charts['cashbackScatter'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format($row['x']) }}</td>
                    <td>{{ number_format($row['y']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($show('anomalies') && !empty($analytics['anomalies']))
<div class="section">
    <h2>{{ $show('action_required') ? 'Risk &amp; anomalies' : 'Alerts &amp; anomaly detection' }}</h2>
    @foreach($analytics['anomalies'] as $alert)
        <div class="alert {{ ($alert['s'] ?? '') === 'warn' ? 'warn' : '' }}">
            <strong>{{ $alert['t'] }}</strong> — {{ $alert['m'] }}
        </div>
    @endforeach
</div>
@endif

@if($show('risk_analysis') && !empty($analytics['categories']))
<div class="section">
    <h2>Risk distribution</h2>
    <table>
        <thead><tr><th>Tier</th><th>Clients</th></tr></thead>
        <tbody>
            @foreach($analytics['categories'] as $cat)
                <tr><td>{{ $cat['name'] }}</td><td>{{ $cat['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($show('rankings') && !empty($rankings))
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

@if($show('recommendations') && !empty($recommendations))
<div class="section">
    <h2>Recommendations</h2>
    <ul class="recommendations">
        @foreach($recommendations as $rec)
            <li>{{ $rec }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($show('insights') && !empty($analytics['insights']) && !$exec)
<div class="section">
    <h2>Insights</h2>
    <p>{{ $analytics['insights'] }}</p>
</div>
@endif

@if($show('appendix'))
<div class="appendix section">
    <h2>Appendix — full data summary</h2>
    <p>Dataset: {{ $report->source }} ({{ number_format($report->rows) }} rows). Export the original CSV for complete raw records.</p>
    @if(!empty($statistics))
        <h3 style="font-size:11px;margin-top:14px;">Statistical reference</h3>
        <table>
            <thead><tr><th>Metric</th><th>Value</th></tr></thead>
            <tbody>
                @foreach($statistics as $stat)
                    <tr><td>{{ $stat['label'] }}</td><td>{{ $stat['value'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @if(!empty($analytics['categories']))
        <h3 style="font-size:11px;margin-top:14px;">Risk tiers</h3>
        <table>
            <thead><tr><th>Tier</th><th>Count</th></tr></thead>
            <tbody>
                @foreach($analytics['categories'] as $cat)
                    <tr><td>{{ $cat['name'] }}</td><td>{{ $cat['value'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif

<p class="footer">Generated by ReportForge — {{ $template->label() }} • AI-powered business insights from CSV</p>
</body>
</html>
