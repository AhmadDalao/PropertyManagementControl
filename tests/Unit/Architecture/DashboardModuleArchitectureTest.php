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
            'Presenters/OperationsDashboardPresenter.php',
            'Presenters/SetupChecklistPresenter.php',
            'Presenters/TenantDashboardPresenter.php',
            'Queries/DashboardPropertyMapQuery.php',
            'Queries/OperationsActivityQuery.php',
            'Queries/OperationsLeaseQuery.php',
            'Queries/OperationsOccupancyQuery.php',
            'Queries/OperationsStatsQuery.php',
            'Queries/PlatformStatusQuery.php',
            'Queries/TenantDashboardQuery.php',
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
        $operations = $this->source('app/Modules/Dashboard/Presenters/OperationsDashboardPresenter.php');

        $this->assertStringContainsString('LeaseInstallment::query()', $stats);
        $this->assertStringNotContainsString("->with('installments')", $stats);
        $this->assertStringNotContainsString('paymentHealth', $operations);
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
        $this->assertStringNotContainsString('MetricGrid', $operations.$tenant);
        $this->assertStringNotContainsString('WorkspacePanel', $operations.$tenant);
    }

    #[Test]
    public function frontend_dashboard_units_and_styles_stay_modular(): void
    {
        foreach ([
            'operations/action-queue.tsx',
            'operations/operations-header.tsx',
            'operations/operations-insight-panels.tsx',
            'operations/operations-metrics.tsx',
            'operations/operations-priority-panels.tsx',
            'operations/platform-status-panel.tsx',
            'shared/health-signals.tsx',
            'shared/record-list.tsx',
            'tenant/tenant-header.tsx',
            'tenant/tenant-lease-documents.tsx',
            'tenant/tenant-maintenance-panel.tsx',
            'tenant/tenant-metrics.tsx',
            'tenant/tenant-payment-history.tsx',
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
        $this->assertLessThanOrEqual(10, substr_count($stylesheet, "\n") + 1);
        $this->assertStringContainsString('./dashboard/metrics.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/actions.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/panels.css', $stylesheet);
        $this->assertStringContainsString('./dashboard/tenant.css', $stylesheet);
        $this->assertFileDoesNotExist($this->path('resources/js/modules/dashboard/widgets.tsx'));
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
