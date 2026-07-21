<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AssetModuleArchitectureTest extends TestCase
{
    #[Test]
    public function asset_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/AssetController.php'));

        $this->assertLessThanOrEqual(130, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('AssetIndexQuery', $source);
        $this->assertStringContainsString('AssetFormPresenter', $source);
        $this->assertStringContainsString('AssetDetailPresenter', $source);
        $this->assertStringContainsString('ManageAssets', $source);
        $this->assertStringNotContainsString('Asset::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }

    #[Test]
    public function asset_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/assets/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './asset-metrics'", $source);
        $this->assertStringContainsString("from './asset-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function asset_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Assets/Actions/ManageAssets.php'),
            $this->path('app/Modules/Assets/Presenters/AssetDetailPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetFormPresenter.php'),
            $this->path('app/Modules/Assets/Queries/AssetIndexQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyMapQuery.php'),
            $this->path('app/Modules/Assets/Requests/PropertyMapRequest.php'),
            $this->path('app/Modules/Assets/Requests/StoreAssetRequest.php'),
            $this->path('app/Modules/Assets/Requests/UpdateAssetRequest.php'),
            $this->path('resources/js/modules/assets/asset-filters.ts'),
            $this->path('resources/js/modules/assets/asset-metrics.tsx'),
            $this->path('resources/js/modules/assets/asset-table.tsx'),
            $this->path('resources/js/modules/assets/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    #[Test]
    public function property_map_controller_is_a_thin_asset_module_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/PropertyMapController.php'));

        $this->assertLessThanOrEqual(35, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('PropertyMapRequest', $source);
        $this->assertStringContainsString('PropertyMapQuery', $source);
        $this->assertStringNotContainsString('Asset::query()', $source);
        $this->assertStringNotContainsString('nullableInteger', $source);
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
