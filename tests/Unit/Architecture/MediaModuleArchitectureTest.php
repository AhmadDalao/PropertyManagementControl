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
    public function media_actions_and_queries_have_explicit_single_responsibilities(): void
    {
        $facade = $this->source($this->path('app/Modules/Media/Actions/ManageMediaFiles.php'));
        $index = $this->source($this->path('app/Modules/Media/Queries/MediaFileIndexQuery.php'));
        $table = $this->source($this->path('resources/js/modules/media/media-table.tsx'));

        $this->assertLessThanOrEqual(40, substr_count($facade, "\n") + 1);
        $this->assertStringContainsString('CreateMediaFile', $facade);
        $this->assertStringContainsString('UpdateMediaFile', $facade);
        $this->assertStringContainsString('DeleteMediaFile', $facade);
        $this->assertStringNotContainsString('DB::', $facade);
        $this->assertStringNotContainsString('Storage::', $facade);

        $this->assertLessThanOrEqual(80, substr_count($index, "\n") + 1);
        $this->assertStringContainsString('MediaFileDirectoryQuery', $index);
        $this->assertStringContainsString('MediaFileFilters', $index);
        $this->assertStringContainsString('MediaFileInsightsQuery', $index);
        $this->assertStringContainsString('MediaFileTableRowPresenter', $index);

        $this->assertLessThanOrEqual(55, substr_count($table, "\n") + 1);
        $this->assertStringContainsString("from './media-table-config'", $table);
    }

    #[Test]
    public function request_and_domain_reuse_one_media_validation_contract(): void
    {
        foreach ([
            'app/Modules/Media/Requests/StoreMediaFileRequest.php',
            'app/Modules/Media/Requests/UpdateMediaFileRequest.php',
            'app/Modules/Media/Support/MediaInputGuard.php',
        ] as $relativePath) {
            $source = $this->source($this->path($relativePath));
            $this->assertStringContainsString('MediaRules', $source);
        }
    }

    #[Test]
    public function media_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            'app/Modules/Media/Actions/ManageMediaFiles.php',
            'app/Modules/Media/Actions/CreateMediaFile.php',
            'app/Modules/Media/Actions/UpdateMediaFile.php',
            'app/Modules/Media/Actions/DeleteMediaFile.php',
            'app/Modules/Media/Actions/MediaFileResponse.php',
            'app/Modules/Media/Data/MediaFileDetailData.php',
            'app/Modules/Media/Data/MediaFileFormData.php',
            'app/Modules/Media/Data/MediaRelocation.php',
            'app/Modules/Media/Data/StoredMediaImage.php',
            'app/Modules/Media/Presenters/MediaFileDetailHeaderPresenter.php',
            'app/Modules/Media/Presenters/MediaFileDetailOverviewPresenter.php',
            'app/Modules/Media/Presenters/MediaFileDetailPresenter.php',
            'app/Modules/Media/Presenters/MediaFileFormFieldsPresenter.php',
            'app/Modules/Media/Presenters/MediaFileFormPresenter.php',
            'app/Modules/Media/Presenters/MediaFileFormValuesPresenter.php',
            'app/Modules/Media/Presenters/MediaFileTableRowPresenter.php',
            'app/Modules/Media/Queries/MediaFileDetailQuery.php',
            'app/Modules/Media/Queries/MediaFileDirectoryQuery.php',
            'app/Modules/Media/Queries/MediaFileFilters.php',
            'app/Modules/Media/Queries/MediaFileFormDataQuery.php',
            'app/Modules/Media/Queries/MediaFileIndexQuery.php',
            'app/Modules/Media/Queries/MediaFileInsightsQuery.php',
            'app/Modules/Media/Queries/CmsMediaPickerQuery.php',
            'app/Modules/Media/Requests/StoreMediaFileRequest.php',
            'app/Modules/Media/Requests/UpdateMediaFileRequest.php',
            'app/Modules/Media/Support/MediaAccess.php',
            'app/Modules/Media/Support/MediaAttributes.php',
            'app/Modules/Media/Support/MediaFileStorage.php',
            'app/Modules/Media/Support/MediaInputGuard.php',
            'app/Modules/Media/Support/MediaOptions.php',
            'app/Modules/Media/Support/MediaPortfolioResolver.php',
            'app/Modules/Media/Support/MediaRules.php',
            'app/Modules/Media/Support/MediaUsage.php',
            'resources/js/modules/media/media-filters.ts',
            'resources/js/modules/media/media-format.ts',
            'resources/js/modules/media/media-metrics.tsx',
            'resources/js/modules/media/media-picker.tsx',
            'resources/js/modules/media/media-picker-copy.ts',
            'resources/js/modules/media/media-picker-panel.tsx',
            'resources/js/modules/media/media-picker-selection.tsx',
            'resources/js/modules/media/media-table.tsx',
            'resources/js/modules/media/media-table-config.tsx',
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
