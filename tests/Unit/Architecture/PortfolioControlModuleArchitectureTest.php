<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PortfolioControlModuleArchitectureTest extends TestCase
{
    #[Test]
    public function portfolio_control_keeps_controllers_and_entries_thin(): void
    {
        $controller = $this->source(
            'app/Http/Controllers/PortfolioControlController.php',
        );
        $entry = $this->source(
            'resources/js/pages/admin/portfolio-control/index.tsx',
        );

        $this->assertLessThanOrEqual(35, substr_count($controller, "\n") + 1);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringNotContainsString('DB::', $controller);
        $this->assertLessThanOrEqual(3, substr_count($entry, "\n") + 1);
        $this->assertStringContainsString(
            '@/modules/portfolio-control/index-page',
            $entry,
        );
    }

    #[Test]
    public function portfolio_control_is_split_into_bounded_feature_units(): void
    {
        foreach ([
            'index-page.tsx' => 90,
            'portfolio-control-filters.tsx' => 200,
            'portfolio-control-metrics.tsx' => 100,
            'property-control-card.tsx' => 190,
            'property-control-grid.tsx' => 70,
            'types.ts' => 90,
        ] as $file => $limit) {
            $path = "resources/js/modules/portfolio-control/{$file}";
            $this->assertLessThanOrEqual(
                $limit,
                substr_count($this->source($path), "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $query = $this->source(
            'app/Modules/PortfolioControl/Queries/PortfolioControlIndexQuery.php',
        );
        $this->assertLessThanOrEqual(230, substr_count($query, "\n") + 1);
        $this->assertStringContainsString(
            'PropertyPerformanceDatasetQuery',
            $query,
        );
        $this->assertStringContainsString('LengthAwarePaginator', $query);
    }

    #[Test]
    public function portfolio_control_styles_are_layered_and_route_is_registered(): void
    {
        $facade = $this->source(
            'resources/css/styles/portfolio-control.css',
        );

        foreach (['base.css', 'filters.css', 'cards.css', 'responsive.css'] as $file) {
            $this->assertStringContainsString(
                "./portfolio-control/{$file}",
                $facade,
            );
            $this->assertLessThanOrEqual(
                210,
                substr_count(
                    $this->source("resources/css/styles/portfolio-control/{$file}"),
                    "\n",
                ) + 1,
            );
        }

        $routes = $this->source('routes/web.php');
        $registry = $this->source('resources/js/modules/registry.ts');
        $this->assertStringContainsString(
            "name('portfolio-control.index')",
            $routes,
        );
        $this->assertStringContainsString(
            "href: '/portfolio-control'",
            $registry,
        );
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
