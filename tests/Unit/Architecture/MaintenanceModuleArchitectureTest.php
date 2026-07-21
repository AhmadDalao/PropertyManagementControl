<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MaintenanceModuleArchitectureTest extends TestCase
{
    #[Test]
    public function maintenance_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/MaintenanceRequestController.php'));

        $this->assertLessThanOrEqual(130, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('MaintenanceIndexQuery', $source);
        $this->assertStringContainsString('MaintenanceFormPresenter', $source);
        $this->assertStringContainsString('MaintenanceDetailPresenter', $source);
        $this->assertStringContainsString('ManageMaintenance', $source);
        $this->assertStringNotContainsString('MaintenanceRequest::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }

    #[Test]
    public function maintenance_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/maintenance/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './maintenance-metrics'", $source);
        $this->assertStringContainsString("from './maintenance-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function maintenance_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Maintenance/Actions/ManageMaintenance.php'),
            $this->path('app/Modules/Maintenance/Presenters/MaintenanceDetailPresenter.php'),
            $this->path('app/Modules/Maintenance/Presenters/MaintenanceFormPresenter.php'),
            $this->path('app/Modules/Maintenance/Queries/MaintenanceIndexQuery.php'),
            $this->path('app/Modules/Maintenance/Requests/StoreMaintenanceRequest.php'),
            $this->path('app/Modules/Maintenance/Requests/UpdateMaintenanceRequest.php'),
            $this->path('resources/js/modules/maintenance/maintenance-filters.ts'),
            $this->path('resources/js/modules/maintenance/maintenance-metrics.tsx'),
            $this->path('resources/js/modules/maintenance/maintenance-table.tsx'),
            $this->path('resources/js/modules/maintenance/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
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
