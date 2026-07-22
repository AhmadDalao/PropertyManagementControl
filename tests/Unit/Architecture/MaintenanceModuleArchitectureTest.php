<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MaintenanceModuleArchitectureTest extends TestCase
{
    #[Test]
    public function maintenance_controller_and_facades_stay_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/MaintenanceRequestController.php');
        $actions = $this->source('app/Modules/Maintenance/Actions/ManageMaintenance.php');
        $form = $this->source('app/Modules/Maintenance/Presenters/MaintenanceFormPresenter.php');
        $detail = $this->source('app/Modules/Maintenance/Presenters/MaintenanceDetailPresenter.php');

        $this->assertLinesAtMost($controller, 115);
        $this->assertLinesAtMost($actions, 40);
        $this->assertLinesAtMost($form, 35);
        $this->assertLinesAtMost($detail, 40);
        $this->assertStringNotContainsString('MaintenanceRequest::query()', $controller);
        $this->assertStringNotContainsString('DB::', $actions);
        $this->assertStringNotContainsString('->loadMissing(', $detail);
        $this->assertStringNotContainsString('->expenses()', $detail);
    }

    #[Test]
    public function maintenance_queries_and_presenters_have_single_responsibilities(): void
    {
        $index = $this->source('app/Modules/Maintenance/Queries/MaintenanceIndexQuery.php');
        $detail = $this->source('app/Modules/Maintenance/Presenters/MaintenanceDetailPresenter.php');
        $form = $this->source('app/Modules/Maintenance/Presenters/MaintenanceFormPresenter.php');

        $this->assertLinesAtMost($index, 90);
        $this->assertStringContainsString('MaintenanceDirectoryQuery', $index);
        $this->assertStringContainsString('MaintenanceInsightsQuery', $index);
        $this->assertStringContainsString('MaintenanceTableRowPresenter', $index);
        $this->assertStringNotContainsString('selectRaw(', $index);
        $this->assertStringContainsString('MaintenanceDetailQuery', $detail);
        $this->assertStringContainsString('MaintenanceDetailOverviewPresenter', $detail);
        $this->assertStringContainsString('MaintenanceRelatedPresenter', $detail);
        $this->assertStringContainsString('MaintenanceCreateFormPresenter', $form);
        $this->assertStringContainsString('MaintenanceTriageFormPresenter', $form);
    }

    #[Test]
    public function maintenance_frontend_table_is_composed_from_typed_parts(): void
    {
        $entry = $this->source('resources/js/modules/maintenance/index-page.tsx');
        $table = $this->source('resources/js/modules/maintenance/maintenance-table.tsx');
        $config = $this->source('resources/js/modules/maintenance/maintenance-table-config.tsx');
        $cells = $this->source('resources/js/modules/maintenance/maintenance-table-cells.tsx');

        $this->assertLinesAtMost($entry, 60);
        $this->assertLinesAtMost($table, 50);
        $this->assertLinesAtMost($config, 110);
        $this->assertLinesAtMost($cells, 150);
        $this->assertStringContainsString("from './maintenance-table-config'", $table);
        $this->assertStringContainsString("from './maintenance-table-cells'", $config);
        $this->assertStringNotContainsString('<RecordActions', $table);
        $this->assertStringNotContainsString('columns={[', $table);
        $this->assertStringNotContainsString('text(', $entry.$table.$config.$cells);
    }

    #[Test]
    public function maintenance_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            'app/Modules/Maintenance/Actions/CancelMaintenance.php',
            'app/Modules/Maintenance/Actions/CreateMaintenance.php',
            'app/Modules/Maintenance/Actions/ManageMaintenance.php',
            'app/Modules/Maintenance/Actions/UpdateMaintenance.php',
            'app/Modules/Maintenance/Data/MaintenanceDetailData.php',
            'app/Modules/Maintenance/Presenters/MaintenanceCreateFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceDetailOverviewPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceDetailPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceFormOptionPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceRelatedPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceTableRowPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceTriageFormPresenter.php',
            'app/Modules/Maintenance/Queries/MaintenanceDetailQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceDirectoryQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceFormOptionsQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceIndexQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceInsightsQuery.php',
            'app/Modules/Maintenance/Support/MaintenanceReferenceGuard.php',
            'resources/js/modules/maintenance/maintenance-filters.ts',
            'resources/js/modules/maintenance/maintenance-metrics.tsx',
            'resources/js/modules/maintenance/maintenance-table-cells.tsx',
            'resources/js/modules/maintenance/maintenance-table-config.tsx',
            'resources/js/modules/maintenance/maintenance-table.tsx',
            'resources/js/modules/maintenance/types.ts',
        ] as $path) {
            $this->assertFileExists($this->path($path));
        }
    }

    private function assertLinesAtMost(string $source, int $limit): void
    {
        $this->assertLessThanOrEqual($limit, substr_count($source, "\n") + 1);
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
