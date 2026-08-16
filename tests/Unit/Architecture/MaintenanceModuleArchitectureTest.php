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

        $attachmentController = $this->source(
            'app/Http/Controllers/MaintenanceAttachmentController.php',
        );
        $this->assertLinesAtMost($attachmentController, 90);
        $this->assertStringNotContainsString('MaintenanceAttachment::query()', $attachmentController);

        foreach ([
            'app/Http/Controllers/MaintenanceVendorController.php',
            'app/Http/Controllers/MaintenanceWorkOrderController.php',
        ] as $path) {
            $resourceController = $this->source($path);
            $this->assertLinesAtMost($resourceController, 110);
            $this->assertStringNotContainsString('::query()', $resourceController);
            $this->assertStringNotContainsString('DB::', $resourceController);
        }
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

        foreach ([
            'resources/js/modules/maintenance/request-form-page.tsx',
            'resources/js/modules/maintenance/request-form-workspace.tsx',
            'resources/js/modules/maintenance/triage-page.tsx',
            'resources/js/modules/maintenance/triage-workspace.tsx',
            'resources/js/modules/maintenance/detail-page.tsx',
            'resources/js/modules/maintenance/maintenance-detail-workspace.tsx',
            'resources/js/modules/maintenance/maintenance-detail-tabs.tsx',
            'resources/js/modules/maintenance/maintenance-next-step-panel.tsx',
            'resources/css/styles/maintenance/next-step.css',
        ] as $path) {
            $this->assertFileExists($this->path($path));
        }

        $detailWorkspace = $this->source('resources/js/modules/maintenance/maintenance-detail-workspace.tsx');
        $this->assertStringContainsString('MaintenanceNextStepPanel', $detailWorkspace);
        $this->assertStringNotContainsString('WorkflowActionPanel', $detailWorkspace);

        $this->assertStringNotContainsString(
            'admin/resource-form',
            $this->source('app/Http/Controllers/MaintenanceRequestController.php'),
        );
        $this->assertStringNotContainsString(
            'admin/resource-show',
            $this->source('app/Http/Controllers/MaintenanceRequestController.php'),
        );
    }

    #[Test]
    public function work_order_register_is_composed_from_scoped_backend_and_typed_frontend_parts(): void
    {
        $controller = $this->source('app/Http/Controllers/MaintenanceWorkOrderController.php');
        $index = $this->source('app/Modules/Maintenance/Queries/MaintenanceWorkOrderIndexQuery.php');
        $directory = $this->source('app/Modules/Maintenance/Queries/MaintenanceWorkOrderDirectoryQuery.php');
        $entry = $this->source('resources/js/modules/maintenance-work-orders/index-page.tsx');
        $table = $this->source('resources/js/modules/maintenance-work-orders/work-order-table.tsx');
        $config = $this->source('resources/js/modules/maintenance-work-orders/work-order-table-config.tsx');
        $cells = $this->source('resources/js/modules/maintenance-work-orders/work-order-cells.tsx');

        $this->assertLinesAtMost($controller, 110);
        $this->assertLinesAtMost($index, 90);
        $this->assertLinesAtMost($entry, 90);
        $this->assertLinesAtMost($table, 50);
        $this->assertLinesAtMost($config, 110);
        $this->assertLinesAtMost($cells, 150);
        $this->assertStringContainsString('MaintenanceWorkOrderDirectoryQuery', $index);
        $this->assertStringContainsString('AssignedPropertyScope', $directory);
        $this->assertStringContainsString("from './work-order-table-config'", $table);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringNotContainsString('columns={[', $table);
        $this->assertStringNotContainsString('text(', $entry.$table.$config.$cells);
    }

    #[Test]
    public function maintenance_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            'app/Modules/Maintenance/Actions/CancelMaintenance.php',
            'app/Modules/Maintenance/Actions/AddMaintenanceAttachments.php',
            'app/Modules/Maintenance/Actions/CreateMaintenance.php',
            'app/Modules/Maintenance/Actions/ManageMaintenance.php',
            'app/Modules/Maintenance/Actions/MaintenanceServiceReportPdf.php',
            'app/Modules/Maintenance/Actions/PersistMaintenanceAttachments.php',
            'app/Modules/Maintenance/Actions/RespondToMaintenanceResolution.php',
            'app/Modules/Maintenance/Actions/UpdateMaintenance.php',
            'app/Modules/Maintenance/Actions/ManageMaintenanceVendors.php',
            'app/Modules/Maintenance/Actions/ManageMaintenanceWorkOrders.php',
            'app/Modules/Maintenance/Actions/MaintenanceWorkOrderWorkbookExport.php',
            'app/Modules/Maintenance/Data/MaintenanceDetailData.php',
            'app/Modules/Maintenance/Data/StoredMaintenancePhoto.php',
            'app/Modules/Maintenance/Presenters/MaintenanceAttachmentFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceAttachmentPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceCreateFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceDetailOverviewPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceDetailPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceFormOptionPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceProgressPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceQueueCounts.php',
            'app/Modules/Maintenance/Presenters/MaintenanceRelatedPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceResolutionFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceTableRowPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceTriageFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceWorkflowPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceVendorDetailPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceVendorFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceWorkOrderDetailPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceWorkOrderFormPresenter.php',
            'app/Modules/Maintenance/Presenters/MaintenanceWorkOrderRowPresenter.php',
            'app/Modules/Maintenance/Queries/MaintenanceDetailQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceDirectoryQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceFormOptionsQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceIndexQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceInsightsQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceOperationsSearch.php',
            'app/Modules/Maintenance/Queries/MaintenanceVendorIndexQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceWorkOrderDirectoryQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceWorkOrderIndexQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceWorkOrderInsightsQuery.php',
            'app/Modules/Maintenance/Requests/RespondToMaintenanceResolutionRequest.php',
            'app/Modules/Maintenance/Support/MaintenanceReferenceGuard.php',
            'app/Modules/Maintenance/Support/MaintenanceAttachmentOptions.php',
            'app/Modules/Maintenance/Support/MaintenanceAttachmentRules.php',
            'app/Modules/Maintenance/Support/MaintenanceAttachmentStorage.php',
            'app/Modules/Maintenance/Support/MaintenanceTransitionGuard.php',
            'app/Modules/Maintenance/Support/MaintenanceVendorAccess.php',
            'app/Modules/Maintenance/Support/MaintenanceVendorOptions.php',
            'app/Modules/Maintenance/Support/MaintenanceWorkOrderAccess.php',
            'app/Modules/Maintenance/Support/MaintenanceWorkOrderOptions.php',
            'resources/js/modules/maintenance-vendors/index-page.tsx',
            'resources/js/modules/maintenance-vendors/types.ts',
            'resources/js/modules/maintenance-vendors/vendor-table.tsx',
            'resources/js/modules/maintenance-work-orders/index-page.tsx',
            'resources/js/modules/maintenance-work-orders/types.ts',
            'resources/js/modules/maintenance-work-orders/work-order-cells.tsx',
            'resources/js/modules/maintenance-work-orders/work-order-filters.ts',
            'resources/js/modules/maintenance-work-orders/work-order-table-config.tsx',
            'resources/js/modules/maintenance-work-orders/work-order-table.tsx',
            'resources/js/modules/maintenance/maintenance-filters.ts',
            'resources/js/modules/maintenance/maintenance-header.tsx',
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
