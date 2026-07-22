<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DocumentationModuleArchitectureTest extends TestCase
{
    #[Test]
    public function documentation_controller_only_adapts_routes(): void
    {
        $source = $this->source('app/Http/Controllers/DocumentationController.php');

        $this->assertLessThanOrEqual(40, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('DocumentationIndexPresenter', $source);
        $this->assertStringContainsString('DocumentationGuidePresenter', $source);
        $this->assertStringNotContainsString('config(', $source);
        $this->assertStringNotContainsString('PortfolioModules', $source);
        $this->assertStringNotContainsString('UiTranslationCatalog', $source);
        $this->assertStringNotContainsString('Collection', $source);
    }

    #[Test]
    public function documentation_access_catalog_and_presenters_stay_focused(): void
    {
        foreach ([
            'Presenters/DocumentationGuidePresenter.php',
            'Presenters/DocumentationIndexPresenter.php',
            'Support/DocumentationAccess.php',
            'Support/DocumentationCatalog.php',
            'Support/DocumentationConfiguration.php',
            'Support/DocumentationLocalizer.php',
            'Support/DocumentationScope.php',
        ] as $file) {
            $path = "app/Modules/Documentation/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                130,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $catalog = $this->source('app/Modules/Documentation/Support/DocumentationCatalog.php');
        $scope = $this->source('app/Modules/Documentation/Support/DocumentationScope.php');
        $access = $this->source('app/Modules/Documentation/Support/DocumentationAccess.php');

        $this->assertStringContainsString('DocumentationConfiguration', $catalog);
        $this->assertStringContainsString('DocumentationLocalizer', $catalog);
        $this->assertStringContainsString('DocumentationScope', $catalog);
        $this->assertStringContainsString('stripPolicyMetadata', $scope);
        $this->assertStringContainsString('workflowSteps', $scope);
        $this->assertStringContainsString('routes', $scope);
        $this->assertStringContainsString('moduleForRoute', $access);
    }

    #[Test]
    public function documentation_pages_and_styles_are_composed_from_bounded_units(): void
    {
        foreach (['index.tsx', 'show.tsx'] as $file) {
            $source = $this->source("resources/js/pages/admin/documentation/{$file}");
            $this->assertLessThanOrEqual(3, substr_count($source, "\n") + 1);
            $this->assertStringContainsString('@/modules/documentation/', $source);
        }

        foreach ([
            'documentation-command.tsx',
            'documentation-control-checks.tsx',
            'documentation-empty.tsx',
            'documentation-guide-content.tsx',
            'documentation-guide-header.tsx',
            'documentation-guide-navigation.tsx',
            'documentation-guide-page.tsx',
            'documentation-header.tsx',
            'documentation-index-page.tsx',
            'documentation-library.tsx',
            'documentation-related-guides.tsx',
            'documentation-workflows.tsx',
            'types.ts',
            'use-documentation-search.ts',
        ] as $file) {
            $path = "resources/js/modules/documentation/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                180,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $stylesheet = $this->source('resources/css/styles/documentation.css');
        $this->assertLessThanOrEqual(10, substr_count($stylesheet, "\n") + 1);

        foreach ([
            'base.css',
            'workflows.css',
            'library.css',
            'controls.css',
            'detail.css',
            'related.css',
            'responsive.css',
        ] as $file) {
            $this->assertStringContainsString("./documentation/{$file}", $stylesheet);
            $source = $this->source("resources/css/styles/documentation/{$file}");
            $this->assertLessThanOrEqual(
                180,
                substr_count($source, "\n") + 1,
                "documentation/{$file} is becoming a stylesheet monolith.",
            );
        }

        $appStyles = $this->source('resources/css/app.css');
        $this->assertStringNotContainsString('styles/documentation.css', $appStyles);
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
