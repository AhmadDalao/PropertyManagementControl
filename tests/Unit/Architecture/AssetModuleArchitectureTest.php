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
    public function asset_facades_and_composers_stay_thin(): void
    {
        $actions = $this->source($this->path('app/Modules/Assets/Actions/ManageAssets.php'));
        $index = $this->source($this->path('app/Modules/Assets/Queries/AssetIndexQuery.php'));
        $form = $this->source($this->path('app/Modules/Assets/Presenters/AssetFormPresenter.php'));
        $detail = $this->source($this->path('app/Modules/Assets/Presenters/AssetDetailPresenter.php'));

        $this->assertLinesAtMost($actions, 40);
        $this->assertLinesAtMost($index, 90);
        $this->assertLinesAtMost($form, 35);
        $this->assertLinesAtMost($detail, 40);
        $this->assertStringNotContainsString('DB::', $actions);
        $this->assertStringNotContainsString('selectRaw(', $index);
        $this->assertStringNotContainsString('->loadMissing(', $detail);
        $this->assertStringContainsString('AssetDirectoryQuery', $index);
        $this->assertStringContainsString('AssetInsightsQuery', $index);
        $this->assertStringContainsString('AssetTableRowPresenter', $index);
        $this->assertStringContainsString('AssetCreateFormPresenter', $form);
        $this->assertStringContainsString('AssetEditFormPresenter', $form);
        $this->assertStringContainsString('AssetDetailQuery', $detail);
        $this->assertStringContainsString('AssetRelatedPresenter', $detail);
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
    public function asset_frontend_table_is_composed_from_typed_parts(): void
    {
        $table = $this->source($this->path('resources/js/modules/assets/asset-table.tsx'));
        $config = $this->source($this->path('resources/js/modules/assets/asset-table-config.tsx'));
        $cells = $this->source($this->path('resources/js/modules/assets/asset-table-cells.tsx'));

        $this->assertLinesAtMost($table, 50);
        $this->assertLinesAtMost($config, 110);
        $this->assertLinesAtMost($cells, 160);
        $this->assertStringContainsString("from './asset-table-config'", $table);
        $this->assertStringContainsString("from './asset-table-cells'", $config);
        $this->assertStringNotContainsString('<RecordActions', $table);
        $this->assertStringNotContainsString('columns={[', $table);
        $this->assertStringNotContainsString('text(', $table.$config.$cells);
    }

    #[Test]
    public function asset_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Assets/Actions/ArchiveAsset.php'),
            $this->path('app/Modules/Assets/Actions/CreateAsset.php'),
            $this->path('app/Modules/Assets/Actions/ManageAssets.php'),
            $this->path('app/Modules/Assets/Actions/UpdateAsset.php'),
            $this->path('app/Modules/Assets/Data/AssetDetailData.php'),
            $this->path('app/Modules/Assets/Data/AssetFormData.php'),
            $this->path('app/Modules/Assets/Presenters/AssetCreateFormPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetDecisionCardsPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetDetailPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetDetailOverviewPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetEditFormPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetFormDefinitionPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetFormOptionPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetFormPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetRelatedPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetTableRowPresenter.php'),
            $this->path('app/Modules/Assets/Queries/AssetDetailQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetDirectoryQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetFormOptionsQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetIndexQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetInsightsQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyMapQuery.php'),
            $this->path('app/Modules/Assets/Requests/HasAssetValidationAttributes.php'),
            $this->path('app/Modules/Assets/Requests/PropertyMapRequest.php'),
            $this->path('app/Modules/Assets/Requests/StoreAssetRequest.php'),
            $this->path('app/Modules/Assets/Requests/UpdateAssetRequest.php'),
            $this->path('app/Modules/Assets/Support/AssetAttributes.php'),
            $this->path('app/Modules/Assets/Support/AssetInputGuard.php'),
            $this->path('app/Modules/Assets/Support/AssetLeaseBalance.php'),
            $this->path('app/Modules/Assets/Support/AssetOptions.php'),
            $this->path('app/Modules/Assets/Support/AssetReferenceGuard.php'),
            $this->path('app/Modules/Assets/Support/AssetStakeholderManager.php'),
            $this->path('resources/js/modules/assets/asset-filters.ts'),
            $this->path('resources/js/modules/assets/asset-metrics.tsx'),
            $this->path('resources/js/modules/assets/asset-table-cells.tsx'),
            $this->path('resources/js/modules/assets/asset-table-config.tsx'),
            $this->path('resources/js/modules/assets/asset-table.tsx'),
            $this->path('resources/js/modules/assets/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    private function assertLinesAtMost(string $source, int $limit): void
    {
        $this->assertLessThanOrEqual($limit, substr_count($source, "\n") + 1);
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
