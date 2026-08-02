<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CompanyControlModuleArchitectureTest extends TestCase
{
    #[Test]
    public function company_control_keeps_route_adapters_and_entries_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/CompanyControlController.php');
        $entry = $this->source('resources/js/pages/admin/company-control/index.tsx');

        $this->assertLessThanOrEqual(50, substr_count($controller, "\n") + 1);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertLessThanOrEqual(3, substr_count($entry, "\n") + 1);
        $this->assertStringContainsString(
            '@/modules/company-control/index-page',
            $entry,
        );
    }

    #[Test]
    public function company_control_is_split_into_bounded_feature_units(): void
    {
        foreach ([
            'index-page.tsx' => 90,
            'company-control-filters.tsx' => 230,
            'company-control-metrics.tsx' => 120,
            'company-control-card.tsx' => 220,
            'company-control-grid.tsx' => 70,
            'types.ts' => 120,
        ] as $file => $limit) {
            $path = "resources/js/modules/company-control/{$file}";
            $this->assertLessThanOrEqual(
                $limit,
                substr_count($this->source($path), "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }
    }

    #[Test]
    public function company_control_styles_and_routes_are_registered(): void
    {
        $facade = $this->source('resources/css/styles/company-control.css');

        foreach (['base.css', 'filters.css', 'cards.css', 'responsive.css'] as $file) {
            $this->assertStringContainsString("./company-control/{$file}", $facade);
            $this->assertLessThanOrEqual(
                240,
                substr_count($this->source("resources/css/styles/company-control/{$file}"), "\n") + 1,
            );
        }

        $this->assertStringContainsString(
            "name('company-control.index')",
            $this->source('routes/web.php'),
        );
        $this->assertStringContainsString(
            "href: '/company-control'",
            $this->source('resources/js/modules/registry.ts'),
        );
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/'.$relativePath);
        $this->assertNotFalse($source);

        return $source;
    }
}
