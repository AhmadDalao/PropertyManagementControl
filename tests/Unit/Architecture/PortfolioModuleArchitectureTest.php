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
    public function portfolio_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Portfolios/Actions/ManagePortfolios.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioDetailPresenter.php'),
            $this->path('app/Modules/Portfolios/Presenters/PortfolioFormPresenter.php'),
            $this->path('app/Modules/Portfolios/Queries/PortfolioIndexQuery.php'),
            $this->path('app/Modules/Portfolios/Requests/HasPortfolioValidationAttributes.php'),
            $this->path('app/Modules/Portfolios/Requests/StorePortfolioRequest.php'),
            $this->path('app/Modules/Portfolios/Requests/UpdatePortfolioRequest.php'),
            $this->path('app/Modules/Portfolios/Support/PortfolioAccess.php'),
            $this->path('app/Modules/Portfolios/Support/PortfolioOptions.php'),
            $this->path('resources/js/modules/portfolios/portfolio-filters.ts'),
            $this->path('resources/js/modules/portfolios/portfolio-metrics.tsx'),
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
