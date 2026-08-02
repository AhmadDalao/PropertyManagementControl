<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OpeningDataModuleArchitectureTest extends TestCase
{
    #[Test]
    public function controller_is_a_thin_request_adapter(): void
    {
        $source = $this->source('app/Http/Controllers/OpeningDataController.php');

        $this->assertLessThanOrEqual(100, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('OpeningDataPagePresenter', $source);
        $this->assertStringContainsString('PreviewOpeningData', $source);
        $this->assertStringContainsString('CommitOpeningData', $source);
        $this->assertStringNotContainsString('::query()', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }

    #[Test]
    public function import_actions_and_support_units_remain_bounded(): void
    {
        foreach (glob($this->path('app/Modules/OpeningData/Actions/*.php')) ?: [] as $file) {
            $this->assertLineLimit($file, 110);
        }

        foreach ([
            'OpeningDataLeaseReferenceValidator.php' => 250,
            'OpeningDataRowValidator.php' => 200,
            'OpeningDataPreviewStore.php' => 180,
            'XlsxArchiveReader.php' => 230,
            'XlsxReader.php' => 280,
        ] as $file => $limit) {
            $this->assertLineLimit(
                $this->path("app/Modules/OpeningData/Support/{$file}"),
                $limit,
            );
        }

        foreach (glob($this->path('app/Modules/OpeningData/Support/*.php')) ?: [] as $file) {
            $this->assertLessThanOrEqual(
                1,
                substr_count((string) file_get_contents($file), 'final class '),
                "{$file} owns more than one support class.",
            );
        }
    }

    #[Test]
    public function frontend_and_styles_are_composed_from_small_units(): void
    {
        foreach (glob($this->path('resources/js/modules/opening-data/*.{ts,tsx}'), GLOB_BRACE) ?: [] as $file) {
            $this->assertLineLimit($file, 180);
        }

        $entry = $this->source('resources/css/styles/opening-data.css');
        $this->assertLessThanOrEqual(10, substr_count($entry, "\n") + 1);
        $layers = glob($this->path('resources/css/styles/opening-data/*.css')) ?: [];
        $this->assertCount(7, $layers);

        foreach ($layers as $file) {
            $this->assertLineLimit($file, 180);
        }
    }

    private function assertLineLimit(string $file, int $limit): void
    {
        $this->assertLessThanOrEqual(
            $limit,
            substr_count((string) file_get_contents($file), "\n") + 1,
            "{$file} is becoming a monolith.",
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
