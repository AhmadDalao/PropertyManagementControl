<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PortfolioModuleArchitectureTest extends TestCase
{
    #[Test]
    public function portfolio_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/PortfolioController.php'));

        $this->assertLessThanOrEqual(110, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('PortfolioIndexQuery', $source);
        $this->assertStringContainsString('PortfolioFormPresenter', $source);
        $this->assertStringContainsString('PortfolioDetailPresenter', $source);
        $this->assertStringContainsString('ManagePortfolios', $source);
        $this->assertStringNotContainsString('Portfolio::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('->loadMissing(', $source);
    }

    #[Test]
    public function portfolio_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/portfolios/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './portfolio-metrics'", $source);
        $this->assertStringContainsString("from './portfolio-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function portfolio_detail_and_index_are_composers_not_query_monoliths(): void
    {
        $detail = $this->source($this->path('app/Modules/Portfolios/Presenters/PortfolioDetailPresenter.php'));
        $index = $this->source($this->path('app/Modules/Portfolios/Queries/PortfolioIndexQuery.php'));

        $this->assertLessThanOrEqual(45, substr_count($detail, "\n") + 1);
        $this->assertStringContainsString('PortfolioDetailQuery', $detail);
        $this->assertStringContainsString('PortfolioOverviewPresenter', $detail);
        $this->assertStringContainsString('PortfolioRelatedPresenter', $detail);
        $this->assertStringNotContainsString('->assets()', $detail);
        $this->assertStringNotContainsString('selectRaw(', $detail);

        $this->assertLessThanOrEqual(90, substr_count($index, "\n") + 1);
        $this->assertStringContainsString('PortfolioDirectoryQuery', $index);
        $this->assertStringContainsString('PortfolioInsightsQuery', $index);
        $this->assertStringNotContainsString('Asset::query()', $index);
        $this->assertStringNotContainsString('selectRaw(', $index);
    }

    #[Test]
    public function portfolio_frontend_table_is_split_into_small_responsibilities(): void
    {
        $table = $this->source($this->path('resources/js/modules/portfolios/portfolio-table.tsx'));
        $config = $this->source($this->path('resources/js/modules/portfolios/portfolio-table-config.tsx'));
        $cells = $this->source($this->path('resources/js/modules/portfolios/portfolio-table-cells.tsx'));

        $this->assertLessThanOrEqual(50, substr_count($table, "\n") + 1);
        $this->assertLessThanOrEqual(120, substr_count($config, "\n") + 1);
        $this->assertLessThanOrEqual(170, substr_count($cells, "\n") + 1);
        $this->assertStringContainsString("from './portfolio-table-config'", $table);
        $this->assertStringContainsString("from './portfolio-table-cells'", $config);
        $this->assertStringNotContainsString('<RecordActions', $table);
        $this->assertStringNotContainsString('columns={[', $table);
    }

    #[Test]
    public function portfolio_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Portfolios/Actions/ManagePortfolios.php'),
            $this->path('app/Modules/Portfolios/Data/PortfolioDetailData.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioActionPresenter.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioDetailPresenter.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioFormPresenter.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioOverviewPresenter.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioRelatedPresenter.php'),
            $this->path('app/Modules/Portfolios/Queries/PortfolioDetailQuery.php'),
            $this->path('app/Modules/Portfolios/Queries/PortfolioDirectoryQuery.php'),
            $this->path('app/Modules/Portfolios/Queries/PortfolioIndexQuery.php'),
            $this->path('app/Modules/Portfolios/Queries/PortfolioInsightsQuery.php'),
            $this->path('app/Modules/Portfolios/Requests/HasPortfolioValidationAttributes.php'),
            $this->path('app/Modules/Portfolios/Requests/StorePortfolioRequest.php'),
            $this->path('app/Modules/Portfolios/Requests/UpdatePortfolioRequest.php'),
            $this->path('app/Modules/Portfolios/Support/PortfolioAccess.php'),
            $this->path('app/Modules/Portfolios/Support/PortfolioOptions.php'),
            $this->path('resources/js/modules/portfolios/portfolio-filters.ts'),
            $this->path('resources/js/modules/portfolios/portfolio-metrics.tsx'),
            $this->path('resources/js/modules/portfolios/portfolio-table-cells.tsx'),
            $this->path('resources/js/modules/portfolios/portfolio-table-config.tsx'),
            $this->path('resources/js/modules/portfolios/portfolio-table.tsx'),
            $this->path('resources/js/modules/portfolios/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
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
