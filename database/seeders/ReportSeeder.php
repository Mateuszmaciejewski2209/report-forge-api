<?php

namespace Database\Seeders;

use App\Models\Report;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $reports = [
            ['code' => 'rpt_001', 'name' => 'Q3 Quality Inspection — Line 4', 'source' => 'line4_qa_oct.csv', 'rows' => 12480, 'status' => 'completed', 'size' => '1.4 MB', 'author' => 'M. Chen', 'created_at' => '2026-05-24T10:22:00Z'],
            ['code' => 'rpt_002', 'name' => 'Vendor Defect Summary', 'source' => 'vendor_defects.csv', 'rows' => 842, 'status' => 'completed', 'size' => '320 KB', 'author' => 'A. Patel', 'created_at' => '2026-05-23T15:08:00Z'],
            ['code' => 'rpt_003', 'name' => 'Daily Throughput — May 22', 'source' => 'throughput_0522.csv', 'rows' => 3120, 'status' => 'processing', 'size' => '612 KB', 'author' => 'System', 'created_at' => '2026-05-22T09:00:00Z'],
            ['code' => 'rpt_004', 'name' => 'Calibration Audit', 'source' => 'calibration.csv', 'rows' => 215, 'status' => 'completed', 'size' => '88 KB', 'author' => 'J. Romero', 'created_at' => '2026-05-21T11:45:00Z'],
            ['code' => 'rpt_005', 'name' => 'Shift Yield — Night', 'source' => 'shift_night.csv', 'rows' => 5402, 'status' => 'failed', 'size' => '740 KB', 'author' => 'System', 'created_at' => '2026-05-20T20:14:00Z'],
            ['code' => 'rpt_006', 'name' => 'Customer Returns April', 'source' => 'returns_apr.csv', 'rows' => 1893, 'status' => 'completed', 'size' => '410 KB', 'author' => 'S. Okafor', 'created_at' => '2026-05-18T08:32:00Z'],
            ['code' => 'rpt_007', 'name' => 'Sensor Drift Analysis', 'source' => 'sensors_week20.csv', 'rows' => 27300, 'status' => 'draft', 'size' => '3.2 MB', 'author' => 'M. Chen', 'created_at' => '2026-05-17T13:11:00Z'],
        ];

        foreach ($reports as $data) {
            Report::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'source' => $data['source'],
                    'rows' => $data['rows'],
                    'status' => $data['status'],
                    'size' => $data['size'],
                    'author' => $data['author'],
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['created_at'],
                ],
            );
        }
    }
}
