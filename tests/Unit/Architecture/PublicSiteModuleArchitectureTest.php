<?php

namespace Tests\Unit\Architecture;

use App\Modules\ModuleRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublicSiteModuleArchitectureTest extends TestCase
{
    #[Test]
    public function public_controller_and_shared_middleware_only_adapt_module_output(): void
    {
        $controller = $this->source('app/Http/Controllers/PublicSiteController.php');
        $middleware = $this->source('app/Http/Middleware/HandleInertiaRequests.php');
        $cms = $this->source('app/Http/Controllers/CmsPageController.php');

        $this->assertLessThanOrEqual(35, substr_count($controller, "\n") + 1);
        $this->assertStringContainsString('PublicPagePresenter', $controller);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringContainsString('PublicNavigationQuery', $middleware);
        $this->assertStringNotContainsString('NavigationItem::query()', $middleware);
        $this->assertStringNotContainsString('PublicPageQuery', $cms);
    }

    #[Test]
    public function public_backend_uses_one_bounded_content_pipeline(): void
    {
        $files = glob($this->path('app/Modules/PublicSite/**/*.php')) ?: [];

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertLessThanOrEqual(
                130,
                substr_count((string) file_get_contents($file), "\n") + 1,
                "{$file} is becoming a monolith.",
            );
        }

        $config = require $this->path('config/public-site.php');
        $this->assertCount(8, $config['sections']);
        $this->assertCount(4, $config['navigation']);
        $this->assertFileDoesNotExist($this->path('app/Services/LandingContentSeeder.php'));
        $this->assertFileDoesNotExist($this->path('app/Modules/Cms/Queries/PublicCmsPageQuery.php'));
        $this->assertArrayHasKey('public_site', ModuleRegistry::operationalModules());
    }

    #[Test]
    public function public_frontend_and_styles_are_composed_from_small_units(): void
    {
        foreach ([
            'resources/js/pages/public/home.tsx',
            'resources/js/pages/public/page.tsx',
            'resources/js/components/cms-renderer.tsx',
            'resources/js/layouts/public-layout.tsx',
        ] as $wrapper) {
            $this->assertLessThanOrEqual(
                3,
                substr_count($this->source($wrapper), "\n") + 1,
            );
        }

        $moduleFiles = $this->recursiveFiles('resources/js/modules/public-site', ['ts', 'tsx']);
        $this->assertNotEmpty($moduleFiles);

        foreach ($moduleFiles as $file) {
            $source = (string) file_get_contents($file);
            $this->assertLessThanOrEqual(
                180,
                substr_count($source, "\n") + 1,
                "{$file} is becoming a monolith.",
            );
            $this->assertStringNotContainsString('fallbackSections', $source);
        }

        $stylesheet = $this->source('resources/css/styles/public.css');
        $this->assertLessThanOrEqual(10, substr_count($stylesheet, "\n") + 1);
        $layers = glob($this->path('resources/css/styles/public/*.css')) ?: [];
        $this->assertCount(6, $layers);

        foreach ($layers as $file) {
            $this->assertLessThanOrEqual(
                200,
                substr_count((string) file_get_contents($file), "\n") + 1,
                "{$file} is becoming a stylesheet monolith.",
            );
        }

        $this->assertFileDoesNotExist($this->path('resources/js/pages/welcome.tsx'));
    }

    /** @param array<int, string> $extensions */
    private function recursiveFiles(string $directory, array $extensions): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path($directory)),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
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
