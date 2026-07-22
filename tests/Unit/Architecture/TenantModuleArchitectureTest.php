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
            $this->path('app/Modules/Shared/AccountContinuityGuard.php'),
            $this->path('app/Modules/Shared/AccountSessionRevoker.php'),
            $this->path('app/Modules/Tenants/Actions/ArchiveTenant.php'),
            $this->path('app/Modules/Tenants/Actions/CreateTenant.php'),
            $this->path('app/Modules/Tenants/Actions/ManageTenants.php'),
            $this->path('app/Modules/Tenants/Actions/UpdateTenant.php'),
            $this->path('app/Modules/Tenants/Data/TenantDetailData.php'),
            $this->path('app/Modules/Tenants/Data/TenantFormData.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantCreateFormPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantDetailPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantDetailHeaderPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantDetailOverviewPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantEditFormPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantFormDefinitionPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantFormFieldsPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantFormPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantFormValuesPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantRelatedPresenter.php'),
            $this->path('app/Modules/Tenants/Presenters/TenantTableRowPresenter.php'),
            $this->path('app/Modules/Tenants/Queries/TenantDetailQuery.php'),
            $this->path('app/Modules/Tenants/Queries/TenantDirectoryQuery.php'),
            $this->path('app/Modules/Tenants/Queries/TenantFormOptionsQuery.php'),
            $this->path('app/Modules/Tenants/Queries/TenantIndexQuery.php'),
            $this->path('app/Modules/Tenants/Queries/TenantInsightsQuery.php'),
            $this->path('app/Modules/Tenants/Requests/HasTenantValidationAttributes.php'),
            $this->path('app/Modules/Tenants/Requests/StoreTenantRequest.php'),
            $this->path('app/Modules/Tenants/Requests/UpdateTenantRequest.php'),
            $this->path('app/Modules/Tenants/Support/TenantAccess.php'),
            $this->path('app/Modules/Tenants/Support/TenantContinuityGuard.php'),
            $this->path('app/Modules/Tenants/Support/TenantInputGuard.php'),
            $this->path('app/Modules/Tenants/Support/TenantOptions.php'),
            $this->path('app/Modules/Tenants/Support/TenantPortfolioResolver.php'),
            $this->path('app/Modules/Tenants/Support/TenantPortalAccountManager.php'),
            $this->path('app/Modules/Tenants/Support/TenantProfileAttributes.php'),
            $this->path('resources/js/modules/tenants/tenant-filters.ts'),
            $this->path('resources/js/modules/tenants/tenant-metrics.tsx'),
            $this->path('resources/js/modules/tenants/tenant-profile-completeness.tsx'),
            $this->path('resources/js/modules/tenants/tenant-table-cells.tsx'),
            $this->path('resources/js/modules/tenants/tenant-table-config.tsx'),
            $this->path('resources/js/modules/tenants/tenant-table.tsx'),
            $this->path('resources/js/modules/tenants/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    #[Test]
    public function tenant_facades_and_frontend_composers_stay_small(): void
    {
        foreach ([
            'app/Modules/Tenants/Actions/ManageTenants.php' => 40,
            'app/Modules/Tenants/Presenters/TenantDetailPresenter.php' => 45,
            'app/Modules/Tenants/Presenters/TenantFormDefinitionPresenter.php' => 35,
            'app/Modules/Tenants/Presenters/TenantFormFieldsPresenter.php' => 150,
            'app/Modules/Tenants/Presenters/TenantFormPresenter.php' => 45,
            'app/Modules/Tenants/Presenters/TenantFormValuesPresenter.php' => 80,
            'app/Modules/Tenants/Queries/TenantIndexQuery.php' => 90,
            'resources/js/modules/tenants/tenant-table.tsx' => 65,
            'resources/js/modules/tenants/tenant-table-config.tsx' => 110,
            'resources/js/modules/tenants/tenant-table-cells.tsx' => 140,
        ] as $path => $maximumLines) {
            $source = $this->source($this->path($path));

            $this->assertLessThanOrEqual(
                $maximumLines,
                substr_count($source, "\n") + 1,
                "{$path} should only coordinate focused collaborators.",
            );
        }

        $actions = $this->source($this->path('app/Modules/Tenants/Actions/ManageTenants.php'));
        $index = $this->source($this->path('app/Modules/Tenants/Queries/TenantIndexQuery.php'));
        $detail = $this->source($this->path('app/Modules/Tenants/Presenters/TenantDetailPresenter.php'));
        $form = $this->source($this->path('app/Modules/Tenants/Presenters/TenantFormPresenter.php'));
        $table = $this->source($this->path('resources/js/modules/tenants/tenant-table.tsx'));

        $this->assertStringNotContainsString('DB::', $actions);
        $this->assertStringContainsString('TenantDirectoryQuery', $index);
        $this->assertStringContainsString('TenantInsightsQuery', $index);
        $this->assertStringContainsString('TenantTableRowPresenter', $index);
        $this->assertStringContainsString('TenantDetailQuery', $detail);
        $this->assertStringContainsString('TenantRelatedPresenter', $detail);
        $this->assertStringContainsString('TenantCreateFormPresenter', $form);
        $this->assertStringContainsString('TenantEditFormPresenter', $form);
        $this->assertStringContainsString("from './tenant-table-config'", $table);
        $this->assertStringNotContainsString('<RecordActions', $table);
        $this->assertStringNotContainsString('columns={[', $table);
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
