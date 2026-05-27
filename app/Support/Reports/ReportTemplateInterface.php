<?php

namespace App\Support\Reports;

interface ReportTemplateInterface
{
    public function id(): string;

    public function label(): string;

    /** @return list<ReportSection> */
    public function sections(): array;

    /** spacious | balanced | dense */
    public function density(): string;

    public function maxKpis(): int;

    public function maxRankings(): int;

    public function maxRecommendations(): int;

    /** @return array<string, bool> */
    public function sectionFlags(): array;
}
