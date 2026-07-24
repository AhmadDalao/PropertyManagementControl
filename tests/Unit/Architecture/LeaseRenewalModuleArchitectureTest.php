<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeaseRenewalModuleArchitectureTest extends TestCase
{
    #[Test]
    public function renewal_controller_and_frontend_entry_stay_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/LeaseRenewalController.php');
        $page = $this->source('resources/js/modules/lease-renewals/index-page.tsx');
        $wrapper = $this->source('resources/js/pages/admin/lease-renewals/index.tsx');

        $this->assertLessThanOrEqual(30, substr_count($controller, "\n") + 1);
        $this->assertLessThanOrEqual(55, substr_count($page, "\n") + 1);
        $this->assertLessThanOrEqual(3, substr_count($wrapper, "\n") + 1);
        $this->assertStringContainsString('LeaseRenewalIndexQuery', $controller);
        $this->assertStringNotContainsString('Lease::query()', $controller);
        $this->assertStringContainsString("from './lease-renewal-table'", $page);
        $this->assertStringNotContainsString("from '@/components/data-table'", $page);
    }

    #[Test]
    public function renewal_module_owns_query_presentation_export_and_table_responsibilities(): void
    {
        foreach ([
            'app/Modules/LeaseRenewals/Actions/LeaseRenewalWorkbookExport.php',
            'app/Modules/LeaseRenewals/Presenters/LeaseRenewalRowPresenter.php',
            'app/Modules/LeaseRenewals/Queries/LeaseRenewalDirectoryQuery.php',
            'app/Modules/LeaseRenewals/Queries/LeaseRenewalIndexQuery.php',
            'app/Modules/LeaseRenewals/Queries/LeaseRenewalInsightsQuery.php',
            'app/Modules/LeaseRenewals/Queries/LeaseRenewalSearch.php',
            'app/Modules/LeaseRenewals/Support/LeaseRenewalNoticeScope.php',
            'app/Modules/LeaseRenewals/Support/LeaseRenewalOptions.php',
            'resources/js/modules/lease-renewals/lease-renewal-cells.tsx',
            'resources/js/modules/lease-renewals/lease-renewal-filters.ts',
            'resources/js/modules/lease-renewals/lease-renewal-labels.ts',
            'resources/js/modules/lease-renewals/lease-renewal-metrics.tsx',
            'resources/js/modules/lease-renewals/lease-renewal-table-config.tsx',
            'resources/js/modules/lease-renewals/lease-renewal-table.tsx',
            'resources/js/modules/lease-renewals/types.ts',
        ] as $path) {
            $this->assertFileExists($this->path($path));
        }
    }

    #[Test]
    public function renewal_units_stay_within_reviewable_size_limits(): void
    {
        foreach ([
            'app/Modules/LeaseRenewals/Actions/LeaseRenewalWorkbookExport.php' => 100,
            'app/Modules/LeaseRenewals/Presenters/LeaseRenewalRowPresenter.php' => 90,
            'app/Modules/LeaseRenewals/Queries/LeaseRenewalDirectoryQuery.php' => 175,
            'app/Modules/LeaseRenewals/Queries/LeaseRenewalIndexQuery.php' => 125,
            'resources/js/modules/lease-renewals/lease-renewal-table-config.tsx' => 155,
            'resources/js/modules/lease-renewals/lease-renewal-table.tsx' => 70,
        ] as $path => $maximum) {
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                $maximum,
                substr_count($source, "\n") + 1,
                $path,
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
