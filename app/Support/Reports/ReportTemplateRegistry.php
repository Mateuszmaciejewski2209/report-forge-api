<?php

namespace App\Support\Reports;

class ReportTemplateRegistry
{
    /** @var array<string, ReportTemplateInterface> */
    private array $templates;

    public function __construct()
    {
        $this->templates = [
            'modern' => new ModernTemplate(),
            'executive' => new ExecutiveTemplate(),
            'technical' => new TechnicalTemplate(),
        ];
    }

    public function resolve(string $id): ReportTemplateInterface
    {
        return $this->templates[$id] ?? $this->templates['modern'];
    }

    /** @return list<string> */
    public static function allowedIds(): array
    {
        return ['modern', 'executive', 'technical'];
    }
}
