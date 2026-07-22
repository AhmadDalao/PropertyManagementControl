<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShowcaseDataModuleArchitectureTest extends TestCase
{
    #[Test]
    public function showcase_controller_and_job_only_adapt_requests(): void
    {
        $controller = $this->source('app/Http/Controllers/ShowcaseDataController.php');
        $job = $this->source('app/Jobs/GenerateShowcaseBuilding.php');

        $this->assertLessThanOrEqual(70, substr_count($controller, "\n") + 1);
        $this->assertStringContainsString('ShowcaseDataPagePresenter', $controller);
        $this->assertStringContainsString('StartShowcaseDataset', $controller);
        $this->assertStringContainsString('RetryShowcaseDataset', $controller);
        $this->assertStringContainsString('PurgeShowcaseDataset', $controller);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringNotContainsString('tagLegacyData', $controller);
        $this->assertStringContainsString('BuildShowcaseProperty', $job);
        $this->assertStringContainsString('RecordShowcaseFailure', $job);
        $this->assertStringNotContainsString('Asset::', $job);
    }

    #[Test]
    public function showcase_backend_units_remain_bounded(): void
    {
        $files = glob($this->path('app/Modules/ShowcaseData/**/*.php')) ?: [];

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            $this->assertLessThanOrEqual(
                180,
                substr_count($source, "\n") + 1,
                "{$file} is becoming a monolith.",
            );
        }

        $this->assertFileDoesNotExist(
            $this->path('app/Services/ShowcaseDatasetService.php'),
        );
    }

    #[Test]
    public function showcase_frontend_and_styles_are_composed_from_small_units(): void
    {
        $composer = $this->source(
            'resources/js/modules/showcase-data/index-page.tsx',
        );

        $this->assertLessThanOrEqual(70, substr_count($composer, "\n") + 1);
        $this->assertStringNotContainsString('useState', $composer);
        $this->assertStringNotContainsString('useForm', $composer);
        $this->assertStringNotContainsString('router.', $composer);

        $moduleFiles = glob(
            $this->path('resources/js/modules/showcase-data/*.{ts,tsx}'),
            GLOB_BRACE,
        ) ?: [];

        foreach ($moduleFiles as $file) {
            $source = (string) file_get_contents($file);
            $this->assertLessThanOrEqual(
                180,
                substr_count($source, "\n") + 1,
                "{$file} is becoming a monolith.",
            );
        }

        $stylesheet = $this->source('resources/css/styles/showcase-data.css');
        $this->assertLessThanOrEqual(10, substr_count($stylesheet, "\n") + 1);

        $layers = glob(
            $this->path('resources/css/styles/showcase-data/*.css'),
        ) ?: [];
        $this->assertCount(7, $layers);

        foreach ($layers as $file) {
            $source = (string) file_get_contents($file);
            $this->assertLessThanOrEqual(
                180,
                substr_count($source, "\n") + 1,
                "{$file} is becoming a stylesheet monolith.",
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
