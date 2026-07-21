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
            $this->path('app/Modules/Leases/Actions/ManageLeases.php'),
            $this->path('app/Modules/Leases/Actions/LeaseDocuments.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseDetailPresenter.php'),
            $this->path('app/Modules/Leases/Presenters/LeaseFormPresenter.php'),
            $this->path('app/Modules/Leases/Queries/LeaseIndexQuery.php'),
            $this->path('app/Modules/Leases/Requests/StoreLeaseRequest.php'),
            $this->path('app/Modules/Leases/Requests/UpdateLeaseRequest.php'),
            $this->path('app/Modules/Leases/Requests/UploadSignedContractRequest.php'),
            $this->path('resources/js/modules/leases/lease-filters.ts'),
            $this->path('resources/js/modules/leases/lease-metrics.tsx'),
            $this->path('resources/js/modules/leases/lease-table.tsx'),
            $this->path('resources/js/modules/leases/types.ts'),
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
