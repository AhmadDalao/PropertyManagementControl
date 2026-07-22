<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LeaseModuleArchitectureTest extends TestCase
{
    #[Test]
    public function lease_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/LeaseController.php'));

        $this->assertLessThanOrEqual(150, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('LeaseIndexQuery', $source);
        $this->assertStringContainsString('LeaseFormPresenter', $source);
        $this->assertStringContainsString('LeaseDetailPresenter', $source);
        $this->assertStringContainsString('ManageLeases', $source);
        $this->assertStringContainsString('LeaseDocuments', $source);
        $this->assertStringNotContainsString('Lease::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('Storage::', $source);
    }

    #[Test]
    public function lease_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/leases/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './lease-metrics'", $source);
        $this->assertStringContainsString("from './lease-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function lease_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Modules/Leases/Actions/CreateLease.php'),
            $this->path('app/Modules/Leases/Actions/ManageLeases.php'),
            $this->path('app/Modules/Leases/Actions/TerminateLease.php'),
            $this->path('app/Modules/Leases/Actions/UpdateLease.php'),
            $this->path('app/Modules/Leases/Actions/LeaseDocuments.php'),
            $this->path('app/Modules/Leases/Data/LeaseDetailData.php'),
            $this->path('app/Modules/Leases/Data/LeaseFormData.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseDetailPresenter.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseFormPresenter.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseInstallmentLabelPresenter.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseRenewalFormPresenter.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseTableRowPresenter.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseWorkflowPresenter.php'),
            $this->path('app/Modules/Leases/Queries/LeaseDetailQuery.php'),
            $this->path('app/Modules/Leases/Queries/LeaseDirectoryQuery.php'),
            $this->path('app/Modules/Leases/Queries/LeaseFormOptionsQuery.php'),
            $this->path('app/Modules/Leases/Queries/LeaseIndexQuery.php'),
            $this->path('app/Modules/Leases/Queries/LeaseInsightsQuery.php'),
            $this->path('app/Modules/Leases/Requests/StoreLeaseRequest.php'),
            $this->path('app/Modules/Leases/Requests/UpdateLeaseRequest.php'),
            $this->path('app/Modules/Leases/Requests/UploadSignedContractRequest.php'),
            $this->path('app/Modules/Leases/Support/LeaseRenewalGuard.php'),
            $this->path('resources/js/modules/leases/lease-filters.ts'),
            $this->path('resources/js/modules/leases/lease-metrics.tsx'),
            $this->path('resources/js/modules/leases/lease-table.tsx'),
            $this->path('resources/js/modules/leases/lease-table-config.tsx'),
            $this->path('resources/js/modules/leases/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    #[Test]
    public function lease_facades_and_entry_components_stay_small(): void
    {
        foreach ([
            'app/Modules/Leases/Actions/ManageLeases.php' => 60,
            'app/Modules/Leases/Presenters/LeaseDetailPresenter.php' => 70,
            'app/Modules/Leases/Presenters/LeaseFormPresenter.php' => 55,
            'app/Modules/Leases/Queries/LeaseIndexQuery.php' => 90,
            'resources/js/modules/leases/lease-table.tsx' => 70,
        ] as $path => $maximum) {
            $source = $this->source($this->path($path));

            $this->assertLessThanOrEqual($maximum, substr_count($source, "\n") + 1, $path);
        }
    }

    #[Test]
    public function lease_requests_share_access_and_validation_contracts(): void
    {
        foreach ([
            'app/Modules/Leases/Requests/StoreLeaseRequest.php',
            'app/Modules/Leases/Requests/UpdateLeaseRequest.php',
            'app/Modules/Leases/Requests/UploadSignedContractRequest.php',
        ] as $path) {
            $source = $this->source($this->path($path));

            $this->assertStringContainsString('LeaseAccess', $source);
            $this->assertStringContainsString('HasLeaseValidationAttributes', $source);
            $this->assertStringNotContainsString("hasAnyRole(['superadmin'", $source);
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
