<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MediaModuleArchitectureTest extends TestCase
{
    #[Test]
    public function media_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/MediaFileController.php'));

        $this->assertLessThanOrEqual(110, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('MediaFileIndexQuery', $source);
        $this->assertStringContainsString('MediaFileFormPresenter', $source);
        $this->assertStringContainsString('MediaFileDetailPresenter', $source);
        $this->assertStringContainsString('ManageMediaFiles', $source);
        $this->assertStringContainsString('MediaFileResponse', $source);
        $this->assertStringNotContainsString('MediaFile::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
        $this->assertStringNotContainsString('Storage::', $source);
    }

    #[Test]
    public function media_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/media/index-page.tsx'));

        $this->assertLessThanOrEqual(55, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './media-metrics'", $source);
        $this->assertStringContainsString("from './media-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function media_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            'app/Modules/Media/Actions/ManageMediaFiles.php',
            'app/Modules/Media/Actions/MediaFileResponse.php',
            'app/Modules/Media/Presenters/MediaFileDetailPresenter.php',
            'app/Modules/Media/Presenters/MediaFileFormPresenter.php',
            'app/Modules/Media/Queries/MediaFileIndexQuery.php',
            'app/Modules/Media/Queries/CmsMediaPickerQuery.php',
            'app/Modules/Media/Requests/StoreMediaFileRequest.php',
            'app/Modules/Media/Requests/UpdateMediaFileRequest.php',
            'app/Modules/Media/Support/MediaAccess.php',
            'app/Modules/Media/Support/MediaOptions.php',
            'app/Modules/Media/Support/MediaUsage.php',
            'resources/js/modules/media/media-filters.ts',
            'resources/js/modules/media/media-format.ts',
            'resources/js/modules/media/media-metrics.tsx',
            'resources/js/modules/media/media-picker.tsx',
            'resources/js/modules/media/media-table.tsx',
            'resources/js/modules/media/types.ts',
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
