<?php

namespace App\Support\Reports;

abstract class AbstractReportTemplate implements ReportTemplateInterface
{
    public function sectionFlags(): array
    {
        $enabled = array_fill_keys(
            array_map(fn (ReportSection $s) => $s->value, $this->sections()),
            true,
        );

        $flags = [];
        foreach (ReportSection::cases() as $section) {
            $flags[$section->value] = isset($enabled[$section->value]);
        }

        return $flags;
    }
}
