<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DailyOperationsReportModuleArchitectureTest extends TestCase
{
    #[Test]
    public function archive_http_and_page_entries_stay_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/DailyOperationsReportController.php');
        $index = $this->source('resources/js/modules/daily-operations-reports/index-page.tsx');
        $show = $this->source('resources/js/modules/daily-operations-reports/show-page.tsx');

        $this->assertLessThanOrEqual(100, substr_count($controller, "\n") + 1);
        $this->assertLessThanOrEqual(80, substr_count($index, "\n") + 1);
        $this->assertLessThanOrEqual(125, substr_count($show, "\n") + 1);
        $this->assertStringContainsString('DailyOperationsReportQuery', $controller);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringContainsString("from './report-generator'", $index);
        $this->assertStringContainsString("from './report-history'", $index);
        $this->assertStringContainsString("from './report-detail-panels'", $show);
    }

    #[Test]
    public function archive_module_units_and_styles_remain_bounded(): void
    {
        foreach ([
            'app/Modules/DailyOperationsReports/Actions/CreateDailyOperationsReport.php' => 150,
            'app/Modules/DailyOperationsReports/Queries/DailyOperationsReportQuery.php' => 180,
            'resources/js/modules/daily-operations-reports/report-card.tsx' => 150,
            'resources/js/modules/daily-operations-reports/report-filters.tsx' => 135,
            'resources/js/modules/daily-operations-reports/report-detail-panels.tsx' => 125,
            'resources/css/styles/daily-operations-reports/base.css' => 120,
            'resources/css/styles/daily-operations-reports/filters.css' => 100,
            'resources/css/styles/daily-operations-reports/cards.css' => 200,
            'resources/css/styles/daily-operations-reports/detail.css' => 120,
            'resources/css/styles/daily-operations-reports/responsive.css' => 90,
        ] as $path => $maximum) {
            $this->assertLessThanOrEqual(
                $maximum,
                substr_count($this->source($path), "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $facade = $this->source('resources/css/styles/daily-operations-reports.css');

        foreach (['base', 'filters', 'cards', 'detail', 'responsive'] as $layer) {
            $this->assertStringContainsString(
                "@import './daily-operations-reports/{$layer}.css';",
                $facade,
            );
        }
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->path($relativePath));
        $this->assertNotFalse($source);

        return $source;
    }

    private function path(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
