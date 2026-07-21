<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReportModuleArchitectureTest extends TestCase
{
    #[Test]
    public function report_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/ReportController.php'));

        $this->assertLessThanOrEqual(90, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('ReportPagePresenter', $source);
        $this->assertStringContainsString('PortfolioReportQuery', $source);
        $this->assertStringContainsString('ManageReportPresets', $source);
        $this->assertStringContainsString('ReportWorkbookExport', $source);
        $this->assertStringNotContainsString('Payment::query()', $source);
        $this->assertStringNotContainsString('ReportPreset::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
    }

    #[Test]
    public function report_frontend_entry_only_composes_module_sections(): void
    {
        $source = $this->source($this->path('resources/js/modules/reports/index-page.tsx'));

        $this->assertLessThanOrEqual(160, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './report-filters'", $source);
        $this->assertStringContainsString("from './report-overview'", $source);
        $this->assertStringContainsString("from './report-presets'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString('function BreakdownBars', $source);
    }

    #[Test]
    public function report_module_owns_each_operational_responsibility(): void
    {
        foreach ([
            'app/Modules/Reports/Actions/ManageReportPresets.php',
            'app/Modules/Reports/Actions/ReportWorkbookExport.php',
            'app/Modules/Reports/Presenters/ReportPagePresenter.php',
            'app/Modules/Reports/Queries/PortfolioReportQuery.php',
            'app/Modules/Reports/Queries/ReportPresetQuery.php',
            'app/Modules/Reports/Requests/ReportIndexRequest.php',
            'app/Modules/Reports/Requests/StoreReportPresetRequest.php',
            'app/Modules/Reports/Support/ReportAccess.php',
            'app/Modules/Reports/Support/ReportFilterSet.php',
            'resources/js/modules/reports/report-collections.tsx',
            'resources/js/modules/reports/report-costs.tsx',
            'resources/js/modules/reports/report-filters.tsx',
            'resources/js/modules/reports/report-operations.tsx',
            'resources/js/modules/reports/report-overview.tsx',
            'resources/js/modules/reports/report-presets.tsx',
            'resources/js/modules/reports/report-tabs.tsx',
            'resources/js/modules/reports/report-visuals.tsx',
            'resources/js/modules/reports/types.ts',
        ] as $relativePath) {
            $this->assertFileExists($this->path($relativePath));
        }
    }

    private function source(string $path): string
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source);

        return $source;
    }

    private function path(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
