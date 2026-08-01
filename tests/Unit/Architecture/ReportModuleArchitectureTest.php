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
        $this->assertStringContainsString('ReportWorkbookExport', $source);
        $this->assertStringNotContainsString('Payment::query()', $source);
        $this->assertStringNotContainsString('ReportPreset::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);

        $presets = $this->source($this->path('app/Http/Controllers/ReportPresetController.php'));
        $this->assertLessThanOrEqual(80, substr_count($presets, "\n") + 1);
        $this->assertStringContainsString('ReportPresetPagePresenter', $presets);
        $this->assertStringContainsString('ManageReportPresets', $presets);
        $this->assertStringNotContainsString('ReportPreset::query()', $presets);
        $this->assertStringNotContainsString('->validate([', $presets);

        $statement = $this->source($this->path('app/Http/Controllers/ReportStatementController.php'));
        $this->assertLessThanOrEqual(60, substr_count($statement, "\n") + 1);
        $this->assertStringContainsString('OwnerStatementPresenter', $statement);
        $this->assertStringContainsString('OwnerStatementPdfExport', $statement);
        $this->assertStringContainsString('OwnerStatementWordExport', $statement);
        $this->assertStringNotContainsString('Payment::query()', $statement);

        $property = $this->source($this->path('app/Http/Controllers/PropertyReportController.php'));
        $this->assertLessThanOrEqual(40, substr_count($property, "\n") + 1);
        $this->assertStringContainsString('PropertyReportPresenter', $property);
        $this->assertStringContainsString('PropertyReportRequest', $property);
        $this->assertStringNotContainsString('Asset::query()', $property);
    }

    #[Test]
    public function report_frontend_entry_only_composes_module_sections(): void
    {
        $source = $this->source($this->path('resources/js/modules/reports/index-page.tsx'));

        $this->assertLessThanOrEqual(160, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './report-filters'", $source);
        $this->assertStringContainsString("from './report-overview'", $source);
        $this->assertStringNotContainsString("from './report-presets'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString('function BreakdownBars', $source);

        $savedForm = $this->source($this->path('resources/js/modules/reports/saved-report-form-page.tsx'));
        $this->assertLessThanOrEqual(135, substr_count($savedForm, "\n") + 1);
        $this->assertStringContainsString("from './saved-report-identity-section'", $savedForm);
        $this->assertStringContainsString("from './saved-report-scope-section'", $savedForm);
        $this->assertStringContainsString("from './saved-report-form-actions'", $savedForm);
    }

    #[Test]
    public function report_query_and_saved_view_shell_delegate_bounded_responsibilities(): void
    {
        $query = $this->source($this->path('app/Modules/Reports/Queries/PortfolioReportQuery.php'));
        $presets = $this->source($this->path('resources/js/modules/reports/saved-reports-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($query, "\n") + 1);
        $this->assertStringContainsString('PortfolioReportDatasetQuery', $query);
        $this->assertStringContainsString('LeaseReportSnapshotFactory', $query);
        $this->assertStringContainsString('ReportSummaryPresenter', $query);
        $this->assertStringContainsString('ReportJournalPresenter', $query);
        $this->assertStringNotContainsString('Payment::query()', $query);
        $this->assertStringNotContainsString('->groupBy(', $query);

        $this->assertLessThanOrEqual(85, substr_count($presets, "\n") + 1);
        $this->assertStringContainsString("from './report-preset-list'", $presets);
        $this->assertStringNotContainsString('useForm(', $presets);
        $this->assertStringNotContainsString('router.delete(', $presets);
    }

    #[Test]
    public function report_styles_are_split_by_concern(): void
    {
        $facade = $this->source($this->path('resources/css/styles/reports.css'));
        $global = $this->source($this->path('resources/css/app.css'));
        $index = $this->source($this->path('resources/js/modules/reports/index-page.tsx'));
        $statement = $this->source($this->path('resources/js/modules/reports/owner-statement-page.tsx'));
        $property = $this->source($this->path('resources/js/modules/reports/property-report-page.tsx'));

        $this->assertLessThanOrEqual(12, substr_count($facade, "\n") + 1);
        $this->assertStringNotContainsString("styles/reports.css';", $global);
        $this->assertStringContainsString("css/styles/reports.css';", $index);
        $this->assertStringContainsString("css/styles/reports.css';", $statement);
        $this->assertStringContainsString("css/styles/reports.css';", $property);

        foreach (['filters', 'library', 'metrics', 'comparison', 'journal', 'records', 'presets', 'statement', 'property', 'responsive'] as $layer) {
            $this->assertStringContainsString("@import './reports/{$layer}.css';", $facade);
            $this->assertFileExists($this->path("resources/css/styles/reports/{$layer}.css"));
        }
    }

    #[Test]
    public function report_module_owns_each_operational_responsibility(): void
    {
        foreach ([
            'app/Modules/Reports/Actions/ManageReportPresets.php',
            'app/Modules/Reports/Actions/OwnerStatementPdfExport.php',
            'app/Modules/Reports/Actions/OwnerStatementWordExport.php',
            'app/Modules/Reports/Actions/ReportWorkbookExport.php',
            'app/Modules/Reports/Data/LeaseReportSnapshot.php',
            'app/Modules/Reports/Data/PortfolioReportData.php',
            'app/Modules/Reports/Presenters/ReportPagePresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetPagePresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetPresenter.php',
            'app/Modules/Reports/Presenters/OwnerStatementPresenter.php',
            'app/Modules/Reports/Presenters/ReportChartsPresenter.php',
            'app/Modules/Reports/Presenters/ReportComparisonPresenter.php',
            'app/Modules/Reports/Presenters/ReportExpenseRowsPresenter.php',
            'app/Modules/Reports/Presenters/ReportLeaseRowsPresenter.php',
            'app/Modules/Reports/Presenters/ReportLibraryPresenter.php',
            'app/Modules/Reports/Presenters/ReportJournalPresenter.php',
            'app/Modules/Reports/Presenters/ReportMaintenanceRowsPresenter.php',
            'app/Modules/Reports/Presenters/ReportPaymentRowsPresenter.php',
            'app/Modules/Reports/Presenters/PropertyReportPresenter.php',
            'app/Modules/Reports/Presenters/ReportSummaryPresenter.php',
            'app/Modules/Reports/Queries/PortfolioReportDatasetQuery.php',
            'app/Modules/Reports/Queries/PortfolioReportQuery.php',
            'app/Modules/Reports/Queries/PropertyReportContextQuery.php',
            'app/Modules/Reports/Queries/ReportComparisonQuery.php',
            'app/Modules/Reports/Queries/ReportPresetQuery.php',
            'app/Modules/Reports/Requests/ReportIndexRequest.php',
            'app/Modules/Reports/Requests/PropertyReportRequest.php',
            'app/Modules/Reports/Requests/StoreReportPresetRequest.php',
            'app/Modules/Reports/Requests/UpdateReportPresetRequest.php',
            'app/Modules/Reports/Support/ReportAccess.php',
            'app/Modules/Reports/Support/ReportFilterSet.php',
            'app/Modules/Reports/Support/ReportPeriod.php',
            'app/Modules/Reports/Support/ReportComparisonPeriod.php',
            'app/Modules/Reports/Support/ReportPropertyScope.php',
            'app/Modules/Reports/Support/LeaseReportSnapshotFactory.php',
            'app/Modules/Reports/Support/ReportQueryScope.php',
            'resources/js/modules/reports/report-collections.tsx',
            'resources/js/modules/reports/report-comparison.tsx',
            'resources/js/modules/reports/owner-statement-page.tsx',
            'resources/js/modules/reports/owner-statement-records.tsx',
            'resources/js/modules/reports/owner-statement-summary.tsx',
            'resources/js/modules/reports/report-costs.tsx',
            'resources/js/modules/reports/report-filters.tsx',
            'resources/js/modules/reports/report-library.tsx',
            'resources/js/modules/reports/report-journal.tsx',
            'resources/js/modules/reports/report-operations.tsx',
            'resources/js/modules/reports/report-overview.tsx',
            'resources/js/modules/reports/report-preset-list.tsx',
            'resources/js/modules/reports/saved-report-form-actions.tsx',
            'resources/js/modules/reports/saved-report-form-page.tsx',
            'resources/js/modules/reports/saved-report-identity-section.tsx',
            'resources/js/modules/reports/saved-report-period-fields.tsx',
            'resources/js/modules/reports/saved-report-property-fields.tsx',
            'resources/js/modules/reports/saved-report-scope-section.tsx',
            'resources/js/modules/reports/saved-reports-page.tsx',
            'resources/js/modules/reports/report-tabs.tsx',
            'resources/js/modules/reports/report-visuals.tsx',
            'resources/js/modules/reports/property-report-context.tsx',
            'resources/js/modules/reports/property-report-page.tsx',
            'resources/js/modules/reports/property-report-period.tsx',
            'resources/js/modules/reports/property-report-tabs.tsx',
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
