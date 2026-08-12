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
        $this->assertStringNotContainsString('requireRoles', $source);
        $this->assertStringNotContainsString('ensurePortfolioAccess', $source);
    }

    #[Test]
    public function asset_facades_and_composers_stay_thin(): void
    {
        $actions = $this->source($this->path('app/Modules/Assets/Actions/ManageAssets.php'));
        $index = $this->source($this->path('app/Modules/Assets/Queries/AssetIndexQuery.php'));
        $form = $this->source($this->path('app/Modules/Assets/Presenters/AssetFormPresenter.php'));
        $detail = $this->source($this->path('app/Modules/Assets/Presenters/AssetDetailPresenter.php'));
        $operations = $this->source($this->path('app/Modules/Assets/Queries/AssetOperationsQuery.php'));
        $operationRecords = $this->source($this->path('app/Modules/Assets/Queries/AssetOperationsRecordsQuery.php'));
        $operationDocuments = $this->source($this->path('app/Modules/Assets/Queries/AssetOperationsDocumentsQuery.php'));
        $related = $this->source($this->path('app/Modules/Assets/Presenters/AssetRelatedPresenter.php'));

        $this->assertLinesAtMost($actions, 40);
        $this->assertLinesAtMost($index, 90);
        $this->assertLinesAtMost($form, 35);
        $this->assertLinesAtMost($detail, 40);
        $this->assertLinesAtMost($operations, 160);
        $this->assertLinesAtMost($operationRecords, 210);
        $this->assertLinesAtMost($operationDocuments, 60);
        $this->assertLinesAtMost($related, 35);
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
        $this->assertStringContainsString('WorkspaceHeader', $source);
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
    public function building_setup_stays_a_bounded_asset_owned_workflow(): void
    {
        $controller = $this->source($this->path('app/Http/Controllers/AssetStructureController.php'));
        $action = $this->source($this->path('app/Modules/Assets/Actions/CreateBuildingStructure.php'));
        $factory = $this->source($this->path('app/Modules/Assets/Actions/BuildingStructureFactory.php'));
        $input = $this->source($this->path('app/Modules/Assets/Support/BuildingStructureInputGuard.php'));
        $references = $this->source($this->path('app/Modules/Assets/Support/BuildingStructureReferenceGuard.php'));
        $presenter = $this->source($this->path('app/Modules/Assets/Presenters/BuildingStructureFormPresenter.php'));
        $continuation = $this->source($this->path('app/Modules/Assets/Presenters/BuildingSetupContinuationPresenter.php'));
        $initialValues = $this->source($this->path('app/Modules/Assets/Presenters/BuildingStructureInitialValuesPresenter.php'));
        $entry = $this->source($this->path('resources/js/modules/assets/building-setup/index-page.tsx'));
        $form = $this->source($this->path('resources/js/modules/assets/building-setup/building-setup-form.tsx'));

        $this->assertLinesAtMost($controller, 50);
        $this->assertLinesAtMost($action, 100);
        $this->assertLinesAtMost($factory, 180);
        $this->assertLinesAtMost($input, 210);
        $this->assertLinesAtMost($references, 60);
        $this->assertLinesAtMost($presenter, 100);
        $this->assertLinesAtMost($continuation, 70);
        $this->assertLinesAtMost($initialValues, 50);
        $this->assertLinesAtMost($entry, 50);
        $this->assertLinesAtMost($form, 130);
        $this->assertStringContainsString('BuildingStructureInputGuard', $action);
        $this->assertStringContainsString('BuildingStructureReferenceGuard', $action);
        $this->assertStringContainsString('BuildingStructureFactory', $action);
        $this->assertStringContainsString('BuildingSetupPreview', $form);
        $this->assertStringNotContainsString('Asset::query()', $controller);
        $this->assertStringNotContainsString('DB::', $controller);
    }

    #[Test]
    public function property_context_stays_a_bounded_asset_owned_shell_contract(): void
    {
        $middleware = $this->source($this->path('app/Http/Middleware/ResolvePropertyContext.php'));
        $presenter = $this->source($this->path('app/Modules/Assets/Presenters/PropertyContextPresenter.php'));
        $query = $this->source($this->path('app/Modules/Assets/Queries/PropertyContextQuery.php'));
        $routes = $this->source($this->path('app/Modules/Assets/Support/PropertyContextRoutes.php'));
        $switcher = $this->source($this->path('resources/js/modules/shell/property-context-switcher.tsx'));
        $switcherState = $this->source($this->path('resources/js/modules/shell/use-property-context-switcher.ts'));
        $styles = $this->source($this->path('resources/css/styles/shell/property-context.css'));

        $this->assertLinesAtMost($middleware, 100);
        $this->assertLinesAtMost($presenter, 60);
        $this->assertLinesAtMost($query, 150);
        $this->assertLinesAtMost($routes, 50);
        $this->assertLinesAtMost($switcher, 130);
        $this->assertLinesAtMost($switcherState, 100);
        $this->assertLinesAtMost($styles, 100);
        $this->assertStringContainsString('PropertyContextQuery', $middleware);
        $this->assertStringContainsString('PortfolioModules::enabledForUser', $presenter);
        $this->assertStringContainsString('AssignedPropertyScope', $query);
        $this->assertStringContainsString('exports.resource', $routes);
        $this->assertStringContainsString("url.searchParams.set('property_id'", $switcherState);
        $this->assertStringNotContainsString('Asset::query()', $middleware);
    }

    #[Test]
    public function property_explorer_stays_a_bounded_modular_workflow(): void
    {
        $controller = $this->source($this->path('app/Http/Controllers/PropertyExplorerController.php'));
        $selection = $this->source($this->path('app/Modules/Assets/Queries/PropertyExplorerSelectionQuery.php'));
        $activeLeases = $this->source($this->path('app/Modules/Assets/Queries/PropertyExplorerActiveLeaseQuery.php'));
        $records = $this->source($this->path('app/Modules/Assets/Queries/PropertyExplorerRecordQuery.php'));
        $metrics = $this->source($this->path('app/Modules/Assets/Queries/PropertyExplorerMetricsQuery.php'));
        $presenter = $this->source($this->path('app/Modules/Assets/Presenters/PropertyExplorerPresenter.php'));
        $entry = $this->source($this->path('resources/js/modules/assets/explorer/index-page.tsx'));
        $focus = $this->source($this->path('resources/js/modules/assets/explorer/explorer-focus-panel.tsx'));
        $nodeFacts = $this->source($this->path('resources/js/modules/assets/explorer/explorer-node-facts.tsx'));
        $tenancy = $this->source($this->path('resources/js/modules/assets/explorer/explorer-tenancy-panel.tsx'));
        $workspace = $this->source($this->path('resources/js/modules/assets/explorer/explorer-workspace.tsx'));
        $viewTabs = $this->source($this->path('resources/js/modules/assets/explorer/explorer-view-tabs.tsx'));
        $facade = $this->source($this->path('resources/css/styles/property-explorer.css'));

        $this->assertLinesAtMost($controller, 35);
        $this->assertLinesAtMost($selection, 155);
        $this->assertLinesAtMost($activeLeases, 55);
        $this->assertLinesAtMost($records, 90);
        $this->assertLinesAtMost($metrics, 75);
        $this->assertLinesAtMost($presenter, 110);
        $this->assertLinesAtMost($entry, 100);
        $this->assertLinesAtMost($focus, 105);
        $this->assertLinesAtMost($nodeFacts, 75);
        $this->assertLinesAtMost($tenancy, 145);
        $this->assertLinesAtMost($workspace, 145);
        $this->assertLinesAtMost($viewTabs, 65);
        $this->assertStringContainsString('PropertyExplorerPresenter', $controller);
        $this->assertStringContainsString('PropertyExplorerSelectionQuery', $presenter);
        $this->assertStringContainsString('leaseableTypes()', $activeLeases);
        $this->assertStringContainsString('PropertyExplorerRecordQuery', $presenter);
        $this->assertStringContainsString('PropertyExplorerMetricsQuery', $presenter);
        $this->assertStringNotContainsString('Asset::query()', $controller);
        $this->assertStringContainsString("@import './property-explorer/controls.css';", $facade);
        $this->assertStringContainsString("@import './property-explorer/content.css';", $facade);
        $this->assertStringContainsString("@import './property-explorer/workspace.css';", $facade);
        $this->assertStringContainsString("@import './property-explorer/responsive.css';", $facade);
    }

    #[Test]
    public function asset_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Assets/Actions/ArchiveAsset.php'),
            $this->path('app/Modules/Assets/Actions/BuildingStructureFactory.php'),
            $this->path('app/Modules/Assets/Actions/CreateBuildingStructure.php'),
            $this->path('app/Modules/Assets/Actions/CreateAsset.php'),
            $this->path('app/Modules/Assets/Actions/ManageAssets.php'),
            $this->path('app/Modules/Assets/Actions/UpdateAsset.php'),
            $this->path('app/Modules/Assets/Data/AssetDetailData.php'),
            $this->path('app/Modules/Assets/Data/AssetOperationsData.php'),
            $this->path('app/Modules/Assets/Data/AssetOperationsRecordsData.php'),
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
            $this->path('app/Modules/Assets/Presenters/AssetLeaseRelatedPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetServiceRelatedPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetStructureRelatedPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetWorkflowPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/AssetTableRowPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/BuildingStructureFormPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/BuildingSetupContinuationPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/BuildingStructureInitialValuesPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/PropertyContextPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/PropertyExplorerAssetPresenter.php'),
            $this->path('app/Modules/Assets/Presenters/PropertyExplorerLeasePresenter.php'),
            $this->path('app/Modules/Assets/Presenters/PropertyExplorerPresenter.php'),
            $this->path('app/Modules/Assets/Queries/AssetDetailQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetDirectoryQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetFormOptionsQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetIndexQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetInsightsQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetOperationsQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetOperationsDocumentsQuery.php'),
            $this->path('app/Modules/Assets/Queries/AssetOperationsRecordsQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyMapQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyContextQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyExplorerMetricsQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyExplorerActiveLeaseQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyExplorerRecordQuery.php'),
            $this->path('app/Modules/Assets/Queries/PropertyExplorerSelectionQuery.php'),
            $this->path('app/Modules/Assets/Requests/HasAssetValidationAttributes.php'),
            $this->path('app/Modules/Assets/Requests/PropertyMapRequest.php'),
            $this->path('app/Modules/Assets/Requests/PropertyExplorerRequest.php'),
            $this->path('app/Modules/Assets/Requests/StoreAssetRequest.php'),
            $this->path('app/Modules/Assets/Requests/StoreBuildingStructureRequest.php'),
            $this->path('app/Modules/Assets/Requests/UpdateAssetRequest.php'),
            $this->path('app/Modules/Assets/Support/AssetAttributes.php'),
            $this->path('app/Modules/Assets/Support/AssetInputGuard.php'),
            $this->path('app/Modules/Assets/Support/AssetLeaseBalance.php'),
            $this->path('app/Modules/Assets/Support/AssetOptions.php'),
            $this->path('app/Modules/Assets/Support/AssetReferenceGuard.php'),
            $this->path('app/Modules/Assets/Support/AssetStakeholderManager.php'),
            $this->path('app/Modules/Assets/Support/BuildingStructureInputGuard.php'),
            $this->path('app/Modules/Assets/Support/BuildingStructurePlan.php'),
            $this->path('app/Modules/Assets/Support/BuildingStructureReferenceGuard.php'),
            $this->path('app/Modules/Assets/Support/PropertyScope.php'),
            $this->path('app/Modules/Assets/Support/PropertyContextRoutes.php'),
            $this->path('resources/js/modules/assets/asset-filters.ts'),
            $this->path('resources/js/modules/assets/asset-metrics.tsx'),
            $this->path('resources/js/modules/assets/asset-table-cells.tsx'),
            $this->path('resources/js/modules/assets/asset-table-config.tsx'),
            $this->path('resources/js/modules/assets/asset-table.tsx'),
            $this->path('resources/js/modules/assets/building-setup/index-page.tsx'),
            $this->path('resources/js/modules/assets/building-setup/types.ts'),
            $this->path('resources/js/modules/assets/explorer/index-page.tsx'),
            $this->path('resources/js/modules/assets/explorer/explorer-fact.tsx'),
            $this->path('resources/js/modules/assets/explorer/explorer-node-facts.tsx'),
            $this->path('resources/js/modules/assets/explorer/explorer-tenancy-panel.tsx'),
            $this->path('resources/js/modules/assets/explorer/explorer-view-tabs.tsx'),
            $this->path('resources/js/modules/assets/explorer/explorer-workspace.tsx'),
            $this->path('resources/js/modules/assets/explorer/types.ts'),
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
