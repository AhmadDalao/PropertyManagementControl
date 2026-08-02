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
        $this->assertStringContainsString('OwnerStatementWorkbookExport', $statement);
        $this->assertStringNotContainsString('Payment::query()', $statement);

        $property = $this->source($this->path('app/Http/Controllers/PropertyReportController.php'));
        $this->assertLessThanOrEqual(40, substr_count($property, "\n") + 1);
        $this->assertStringContainsString('PropertyReportPresenter', $property);
        $this->assertStringContainsString('PropertyReportRequest', $property);
        $this->assertStringNotContainsString('Asset::query()', $property);

        $propertyExports = $this->source($this->path('app/Http/Controllers/PropertyReportExportController.php'));
        $this->assertLessThanOrEqual(55, substr_count($propertyExports, "\n") + 1);
        $this->assertStringContainsString('PropertyOperatingReportPdfExport', $propertyExports);
        $this->assertStringContainsString('PropertyOperatingReportWordExport', $propertyExports);
        $this->assertStringContainsString('PropertyOperatingReportWorkbookExport', $propertyExports);
        $this->assertStringNotContainsString('Payment::query()', $propertyExports);

        $presetDetail = $this->source($this->path('app/Http/Controllers/ReportPresetDetailController.php'));
        $this->assertLessThanOrEqual(35, substr_count($presetDetail, "\n") + 1);
        $this->assertStringContainsString('ReportPresetDetailPresenter', $presetDetail);
        $this->assertStringNotContainsString('ReportPreset::query()', $presetDetail);

        $aging = $this->source($this->path('app/Http/Controllers/ArrearsAgingController.php'));
        $this->assertLessThanOrEqual(60, substr_count($aging, "\n") + 1);
        $this->assertStringContainsString('ArrearsAgingQuery', $aging);
        $this->assertStringContainsString('ArrearsAgingWorkbookExport', $aging);
        $this->assertStringNotContainsString('LeaseInstallment::query()', $aging);
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
        $rentRoll = $this->source($this->path('app/Modules/Reports/Queries/RentRollQuery.php'));
        $aging = $this->source($this->path('app/Modules/Reports/Queries/ArrearsAgingQuery.php'));
        $agingMetrics = $this->source($this->path('app/Modules/Reports/Queries/ArrearsAgingMetricsQuery.php'));
        $presets = $this->source($this->path('resources/js/modules/reports/saved-reports-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($query, "\n") + 1);
        $this->assertStringContainsString('PortfolioReportDatasetQuery', $query);
        $this->assertStringContainsString('LeaseReportSnapshotFactory', $query);
        $this->assertStringContainsString('ReportSummaryPresenter', $query);
        $this->assertStringContainsString('ReportJournalPresenter', $query);
        $this->assertStringNotContainsString('Payment::query()', $query);
        $this->assertStringNotContainsString('->groupBy(', $query);
        $this->assertStringNotContainsString("'is_showcase',", $rentRoll);
        $this->assertLessThanOrEqual(150, substr_count($aging, "\n") + 1);
        $this->assertLessThanOrEqual(130, substr_count($agingMetrics, "\n") + 1);
        $this->assertStringContainsString('ArrearsAgingMetricsQuery', $aging);
        $this->assertStringContainsString('ArrearsAgingScope', $aging);
        $this->assertStringNotContainsString('->join(', $aging);

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
        $statementFilters = $this->source($this->path('resources/js/modules/reports/owner-statement-filters.tsx'));
        $property = $this->source($this->path('resources/js/modules/reports/property-report-page.tsx'));

        $this->assertLessThanOrEqual(13, substr_count($facade, "\n") + 1);
        $this->assertStringNotContainsString("styles/reports.css';", $global);
        $this->assertStringContainsString("css/styles/reports.css';", $index);
        $this->assertStringContainsString("css/styles/reports.css';", $statement);
        $this->assertStringContainsString("from './report-filters'", $statementFilters);
        $this->assertStringContainsString("css/styles/reports.css';", $property);

        foreach (['filters', 'library', 'metrics', 'comparison', 'journal', 'records', 'presets', 'statement', 'property', 'rent-roll', 'arrears-aging', 'responsive'] as $layer) {
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
            'app/Modules/Reports/Actions/OwnerStatementWorkbookExport.php',
            'app/Modules/Reports/Actions/OwnerStatementWordExport.php',
            'app/Modules/Reports/Actions/PropertyOperatingReportPdfExport.php',
            'app/Modules/Reports/Actions/PropertyOperatingReportWordExport.php',
            'app/Modules/Reports/Actions/PropertyOperatingReportWorkbookExport.php',
            'app/Modules/Reports/Actions/RentRollPdfExport.php',
            'app/Modules/Reports/Actions/RentRollWordExport.php',
            'app/Modules/Reports/Actions/RentRollWorkbookExport.php',
            'app/Modules/Reports/Actions/ArrearsAgingPdfExport.php',
            'app/Modules/Reports/Actions/ArrearsAgingWordExport.php',
            'app/Modules/Reports/Actions/ArrearsAgingWorkbookExport.php',
            'app/Modules/Reports/Actions/ReportWorkbookExport.php',
            'app/Modules/Reports/Data/LeaseReportSnapshot.php',
            'app/Modules/Reports/Data/PortfolioReportData.php',
            'app/Modules/Reports/Presenters/ReportPagePresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetDetailActionPresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetDetailPresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetOutputPresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetPagePresenter.php',
            'app/Modules/Reports/Presenters/ReportPresetPresenter.php',
            'app/Modules/Reports/Presenters/OwnerStatementPresenter.php',
            'app/Modules/Reports/Presenters/ReportChartsPresenter.php',
            'app/Modules/Reports/Presenters/ReportComparisonPresenter.php',
            'app/Modules/Reports/Presenters/ReportExpenseRowsPresenter.php',
            'app/Modules/Reports/Presenters/ReportLeaseRowsPresenter.php',
            'app/Modules/Reports/Presenters/ReportLibraryPresenter.php',
            'app/Modules/Reports/Presenters/ReportLibraryScopePresenter.php',
            'app/Modules/Reports/Presenters/RentRollFinancialPresenter.php',
            'app/Modules/Reports/Presenters/RentRollRowPresenter.php',
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
            'app/Modules/Reports/Queries/RentRollQuery.php',
            'app/Modules/Reports/Requests/ReportIndexRequest.php',
            'app/Modules/Reports/Requests/RentRollRequest.php',
            'app/Modules/Reports/Requests/PropertyReportRequest.php',
            'app/Modules/Reports/Requests/StoreReportPresetRequest.php',
            'app/Modules/Reports/Requests/UpdateReportPresetRequest.php',
            'app/Modules/Reports/Support/ReportAccess.php',
            'app/Modules/Reports/Support/ReportFilterSet.php',
            'app/Modules/Reports/Support/ReportPeriod.php',
            'app/Modules/Reports/Support/ReportPagePayloadCache.php',
            'app/Modules/Reports/Support/ReportComparisonPeriod.php',
            'app/Modules/Reports/Support/ReportPropertyScope.php',
            'app/Modules/Reports/Support/PropertyOperatingReportFilename.php',
            'app/Modules/Reports/Support/LeaseReportSnapshotFactory.php',
            'app/Modules/Reports/Support/ReportQueryScope.php',
            'app/Modules/Reports/Support/RentRollOptions.php',
            'resources/js/modules/reports/report-collections.tsx',
            'resources/js/modules/reports/report-card-scope.tsx',
            'resources/js/modules/reports/report-comparison.tsx',
            'resources/js/modules/reports/report-comparison-links.ts',
            'resources/js/modules/reports/owner-statement-page.tsx',
            'resources/js/modules/reports/owner-statement-records.tsx',
            'resources/js/modules/reports/owner-statement-summary.tsx',
            'resources/js/modules/reports/owner-statement-tabs.tsx',
            'resources/js/modules/reports/report-costs.tsx',
            'resources/js/modules/reports/report-filters.tsx',
            'resources/js/modules/reports/report-library.tsx',
            'resources/js/modules/reports/report-library-tabs.tsx',
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
            'resources/js/modules/reports/rent-roll-financials.tsx',
            'resources/js/modules/reports/rent-roll-page.tsx',
            'resources/js/modules/reports/rent-roll-records.tsx',
            'resources/js/modules/reports/rent-roll-scope.tsx',
            'resources/js/modules/reports/rent-roll-state.tsx',
            'resources/js/modules/reports/rent-roll-types.ts',
            'resources/js/modules/reports/arrears-aging-cells.tsx',
            'resources/js/modules/reports/arrears-aging-page.tsx',
            'resources/js/modules/reports/arrears-aging-records.tsx',
            'resources/js/modules/reports/arrears-aging-scope.tsx',
            'resources/js/modules/reports/arrears-aging-summary.tsx',
            'resources/js/modules/reports/arrears-aging-types.ts',
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
