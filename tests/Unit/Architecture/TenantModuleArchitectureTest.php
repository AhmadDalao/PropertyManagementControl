<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TenantModuleArchitectureTest extends TestCase
{
    #[Test]
    public function tenant_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/TenantController.php'));

        $this->assertLessThanOrEqual(120, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('TenantIndexQuery', $source);
        $this->assertStringContainsString('TenantFormPresenter', $source);
        $this->assertStringContainsString('TenantDetailPresenter', $source);
        $this->assertStringContainsString('ManageTenants', $source);
        $this->assertStringNotContainsString('TenantProfile::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
        $this->assertStringNotContainsString('Hash::', $source);
    }

    #[Test]
    public function tenant_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/tenants/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './tenant-metrics'", $source);
        $this->assertStringContainsString("from './tenant-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function tenant_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Tenants/Actions/ManageTenants.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantDetailPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantFormPresenter.php'),
            $this->path('app/Modules/Tenants/Queries/TenantIndexQuery.php'),
            $this->path('app/Modules/Tenants/Requests/HasTenantValidationAttributes.php'),
            $this->path('app/Modules/Tenants/Requests/StoreTenantRequest.php'),
            $this->path('app/Modules/Tenants/Requests/UpdateTenantRequest.php'),
            $this->path('app/Modules/Tenants/Support/TenantAccess.php'),
            $this->path('app/Modules/Tenants/Support/TenantOptions.php'),
            $this->path('resources/js/modules/tenants/tenant-filters.ts'),
            $this->path('resources/js/modules/tenants/tenant-metrics.tsx'),
            $this->path('resources/js/modules/tenants/tenant-table.tsx'),
            $this->path('resources/js/modules/tenants/types.ts'),
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
