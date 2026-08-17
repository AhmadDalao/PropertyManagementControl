<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DashboardModuleArchitectureTest extends TestCase
{
    #[Test]
    public function dashboard_entry_only_selects_the_role_presenter(): void
    {
        $source = $this->source('app/Modules/Dashboard/DashboardPresenter.php');

        $this->assertLessThanOrEqual(35, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('OperationsDashboardPresenter', $source);
        $this->assertStringContainsString('TenantDashboardPresenter', $source);
        $this->assertStringNotContainsString('Asset::query()', $source);
        $this->assertStringNotContainsString('Lease::query()', $source);
        $this->assertStringNotContainsString('TenantProfile::query()', $source);
    }

    #[Test]
    public function backend_dashboard_responsibilities_stay_focused(): void
    {
        foreach ([
            'Presenters/DashboardActionPresenter.php',
            'Presenters/ManagerSetupChecklistPresenter.php',
            'Presenters/OperationsDashboardPresenter.php',
            'Presenters/PlatformActivityPresenter.php',
            'Presenters/SetupChecklistPresenter.php',
            'Presenters/TenantDashboardPresenter.php',
            'Queries/DashboardPropertyMapQuery.php',
            'Queries/DashboardPropertyContextQuery.php',
            'Queries/DashboardSetupTargetQuery.php',
            'Queries/LaunchReadinessSummaryQuery.php',
            'Queries/OperationsActivityQuery.php',
            'Queries/OperationsCollectionQuery.php',
            'Queries/OperationsFinancialQuery.php',
            'Queries/OperationsLeaseQuery.php',
            'Queries/OperationsOccupancyQuery.php',
            'Queries/OperationsStatsQuery.php',
            'Queries/PlatformActivityQuery.php',
            'Queries/PlatformCompositionQuery.php',
            'Queries/PlatformStatusQuery.php',
            'Queries/TenantDashboardQuery.php',
            'Requests/DashboardIndexRequest.php',
            'Support/DashboardPropertyContext.php',
            'Support/DashboardPayloadCache.php',
        ] as $file) {
            $path = "app/Modules/Dashboard/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                130,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $stats = $this->source('app/Modules/Dashboard/Queries/OperationsStatsQuery.php');
        $financial = $this->source('app/Modules/Dashboard/Queries/OperationsFinancialQuery.php');
        $operations = $this->source('app/Modules/Dashboard/Presenters/OperationsDashboardPresenter.php');
        $properties = $this->source('app/Modules/Dashboard/Queries/OperationsPropertyPerformanceQuery.php');
        $propertyDataset = $this->source('app/Modules/Dashboard/Queries/PropertyPerformanceDatasetQuery.php');
        $rootMap = $this->source('app/Modules/Assets/Support/AssetRootMap.php');
        $scorer = $this->source('app/Modules/Dashboard/Support/PropertyPerformanceScorer.php');

        $this->assertStringContainsString('LeaseInstallment::query()', $financial);
        $this->assertStringContainsString('OperationsCurrencySummary', $financial);
        $this->assertStringNotContainsString("->with('installments')", $stats);
        $this->assertStringNotContainsString('paymentHealth', $operations);
        $this->assertLessThanOrEqual(220, substr_count($properties, "\n") + 1);
        $this->assertLessThanOrEqual(230, substr_count($propertyDataset, "\n") + 1);
        $this->assertStringContainsString('PropertyPerformanceDatasetQuery', $properties);
        $this->assertLessThanOrEqual(60, substr_count($rootMap, "\n") + 1);
        $this->assertLessThanOrEqual(60, substr_count($scorer, "\n") + 1);
    }

    #[Test]
    public function frontend_role_composers_delegate_to_dashboard_sections(): void
    {
        $operations = $this->source('resources/js/modules/dashboard/views/operations-dashboard.tsx');
        $tenant = $this->source('resources/js/modules/dashboard/views/tenant-dashboard.tsx');

        $this->assertLessThanOrEqual(50, substr_count($operations, "\n") + 1);
        $this->assertLessThanOrEqual(45, substr_count($tenant, "\n") + 1);
        $this->assertStringContainsString("from '../operations/", $operations);
        $this->assertStringContainsString("from '../tenant/", $tenant);
        foreach ([
            'OperationsHeader',
            'PropertyFocus',
            'PortfolioSetupPanel',
            'OperationsMetrics',
            'OperationsDashboardGroups',
        ] as $component) {
            $this->assertStringContainsString($component, $operations);
        }
        $this->assertStringNotContainsString('ManagementCommandCenter', $operations);
        $this->assertFileDoesNotExist($this->path(
            'resources/js/modules/dashboard/operations/management-command-center.tsx',
        ));
        $this->assertStringNotContainsString('MetricGrid', $operations.$tenant);
        $this->assertStringNotContainsString('WorkspacePanel', $operations.$tenant);
    }

    #[Test]
    public function frontend_dashboard_units_and_styles_stay_modular(): void
    {
        foreach ([
            'operations/action-queue.tsx',
            'operations/launch-readiness-panel.tsx',
            'operations/operations-header.tsx',
            'operations/operations-dashboard-groups.tsx',
            'operations/operations-insight-panels.tsx',
            'operations/operations-metrics.tsx',
            'operations/operations-priority-panels.tsx',
            'operations/operations-system-workspace.tsx',
            'operations/operations-today-workspace.tsx',
            'operations/operations-view-tabs.tsx',
            'operations/platform-activity-panel.tsx',
            'operations/work-panel.ts',
            'operations/platform-status-panel.tsx',
            'operations/platform-composition-panel.tsx',
            'operations/platform-metrics.ts',
            'operations/portfolio-setup-panel.tsx',
            'operations/portfolio-metrics.ts',
            'operations/property-focus-url.ts',
            'operations/property-focus.tsx',
            'operations/property-performance-grid.tsx',
            'operations-types.ts',
            'shared-types.ts',
            'shared/health-signals.tsx',
            'shared/record-list.tsx',
            'tenant/tenant-header.tsx',
            'tenant/tenant-lease-documents.tsx',
            'tenant/tenant-maintenance-panel.tsx',
            'tenant/tenant-metrics.tsx',
            'tenant/tenant-payment-history.tsx',
            'tenant-types.ts',
            'types.ts',
        ] as $file) {
            $path = "resources/js/modules/dashboard/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                170,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $stylesheet = $this->source('resources/css/styles/dashboard.css');
        $this->assertLessThanOrEqual(12, substr_count($stylesheet, "\n") + 1);
        $this->assertStringContainsString('./dashboard/metrics.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/command-flow.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/actions.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/focus.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/period.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/panels.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/tenant.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/groups.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/today-workspace.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/platform.css', $stylesheet);
        $platformStyles = $this->source('resources/css/styles/dashboard/platform.css');
        $this->assertStringContainsString('./platform-composition.css', $platformStyles);
        $this->assertStringContainsString('./platform-activity.css', $platformStyles);
        $this->assertStringNotContainsString('../reference/dashboard.css', $platformStyles);
        $this->assertFileDoesNotExist($this->path(
            'resources/css/styles/reference/dashboard.css',
        ));
        $this->assertFileDoesNotExist($this->path('resources/js/modules/dashboard/widgets.tsx'));

        $groups = $this->source(
            'resources/js/modules/dashboard/operations/operations-dashboard-groups.tsx',
        );
        $viewTabs = $this->source(
            'resources/js/modules/dashboard/operations/operations-view-tabs.tsx',
        );
        $today = $this->source(
            'resources/js/modules/dashboard/operations/operations-today-workspace.tsx',
        );
        $header = $this->source(
            'resources/js/modules/dashboard/operations/operations-header.tsx',
        );
        $metrics = $this->source(
            'resources/js/modules/dashboard/operations/operations-metrics.tsx',
        );

        $this->assertStringContainsString("selected === 'today'", $groups);
        $this->assertStringContainsString("selected === 'portfolio'", $groups);
        $this->assertStringContainsString("selected === 'system'", $groups);
        $this->assertStringContainsString('role="tabpanel"', $groups);
        $this->assertStringContainsString('role="tablist"', $viewTabs);
        $this->assertStringContainsString('aria-selected', $viewTabs);
        $this->assertStringContainsString('onKeyDown', $viewTabs);
        $this->assertStringContainsString('role="tablist"', $today);
        $this->assertStringContainsString('role="tabpanel"', $today);
        $this->assertStringNotContainsString("href: '/action-center'", $header);
        $this->assertStringContainsString(
            'className="pmc-dashboard-metrics"',
            $metrics,
        );

        $entry = $this->source('resources/js/modules/dashboard/dashboard-page.tsx');
        $appStyles = $this->source('resources/css/app.css');
        $propertyFocus = $this->source(
            'resources/js/modules/dashboard/operations/property-focus.tsx',
        );

        $this->assertStringContainsString(
            "import '../../../css/styles/dashboard.css';",
            $entry,
        );
        $this->assertStringNotContainsString(
            "@import './styles/dashboard.css';",
            $appStyles,
        );
        $this->assertStringNotContainsString('<select', $propertyFocus);
        $this->assertStringContainsString('pmc-dashboard-focus-action', $propertyFocus);
        $operationsView = $this->source(
            'resources/js/modules/dashboard/views/operations-dashboard.tsx',
        );
        $this->assertStringContainsString('pmc-dashboard-command-flow', $operationsView);
        $this->assertStringContainsString('pmc-dashboard-command-work', $operationsView);
        $commandFlow = $this->source(
            'resources/css/styles/dashboard/command-flow.css',
        );
        $this->assertStringContainsString('@media (max-width: 1199.98px)', $commandFlow);
        $this->assertStringContainsString('order: 2', $commandFlow);
        $this->assertStringContainsString('order: 4', $commandFlow);
        $this->assertLessThanOrEqual(
            180,
            substr_count($this->source('resources/css/styles/dashboard/focus.css'), "\n") + 1,
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
