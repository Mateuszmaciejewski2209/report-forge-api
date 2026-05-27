<?php

namespace App\Support;

/**
 * Dane analityczne współdzielone z frontendem (mock-data).
 */
class AnalyticsData
{
    /** @return list<array{name: string, value: int, anomalies: int}> */
    public static function trend(): array
    {
        return [
            ['name' => 'Mon', 'value' => 24, 'anomalies' => 2],
            ['name' => 'Tue', 'value' => 38, 'anomalies' => 1],
            ['name' => 'Wed', 'value' => 31, 'anomalies' => 4],
            ['name' => 'Thu', 'value' => 52, 'anomalies' => 2],
            ['name' => 'Fri', 'value' => 44, 'anomalies' => 6],
            ['name' => 'Sat', 'value' => 28, 'anomalies' => 1],
            ['name' => 'Sun', 'value' => 35, 'anomalies' => 0],
        ];
    }

    /** @return list<array{name: string, value: int}> */
    public static function categories(): array
    {
        return [
            ['name' => 'Pass', 'value' => 842],
            ['name' => 'Warn', 'value' => 124],
            ['name' => 'Fail', 'value' => 38],
            ['name' => 'Review', 'value' => 17],
        ];
    }

    /** @return list<array{id: int, batch: string, sensor: string, value: float, unit: string, status: string, time: string}> */
    public static function csvPreviewRows(): array
    {
        return [
            ['id' => 1, 'batch' => 'B-1042', 'sensor' => 'TMP-01', 'value' => 72.4, 'unit' => '°C', 'status' => 'ok', 'time' => '08:01'],
            ['id' => 2, 'batch' => 'B-1042', 'sensor' => 'TMP-02', 'value' => 73.1, 'unit' => '°C', 'status' => 'ok', 'time' => '08:02'],
            ['id' => 3, 'batch' => 'B-1043', 'sensor' => 'PRS-01', 'value' => 4.82, 'unit' => 'bar', 'status' => 'warn', 'time' => '08:04'],
            ['id' => 4, 'batch' => 'B-1043', 'sensor' => 'TMP-01', 'value' => 81.7, 'unit' => '°C', 'status' => 'fail', 'time' => '08:05'],
            ['id' => 5, 'batch' => 'B-1044', 'sensor' => 'VIB-03', 'value' => 0.21, 'unit' => 'mm/s', 'status' => 'ok', 'time' => '08:07'],
            ['id' => 6, 'batch' => 'B-1044', 'sensor' => 'TMP-02', 'value' => 74.0, 'unit' => '°C', 'status' => 'ok', 'time' => '08:08'],
            ['id' => 7, 'batch' => 'B-1045', 'sensor' => 'PRS-01', 'value' => 4.91, 'unit' => 'bar', 'status' => 'warn', 'time' => '08:10'],
            ['id' => 8, 'batch' => 'B-1045', 'sensor' => 'TMP-01', 'value' => 72.9, 'unit' => '°C', 'status' => 'ok', 'time' => '08:11'],
        ];
    }

    /** @return list<array{t: string, m: string, s: string}> */
    public static function anomalies(): array
    {
        return [
            ['t' => '08:05', 'm' => 'TMP-01 reading 81.7°C (>80 threshold)', 's' => 'fail'],
            ['t' => '08:10', 'm' => 'PRS-01 reading 4.91 bar (>4.85 threshold)', 's' => 'warn'],
            ['t' => '08:24', 'm' => 'VIB-03 sustained 0.34 mm/s for 4 min', 's' => 'warn'],
        ];
    }

    public static function insights(): string
    {
        return 'Sensor TMP-01 exceeded threshold (80°C) 12 times on batch B-1043, concentrated between 08:00–10:30. Pressure readings (PRS-01) trended +6% above baseline over the same window, suggesting a correlated cooling-loop event. Recommend inspecting heat exchanger E-04.';
    }
}
