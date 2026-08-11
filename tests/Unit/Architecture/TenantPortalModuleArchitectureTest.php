<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TenantPortalModuleArchitectureTest extends TestCase
{
    #[Test]
    public function tenant_portal_controllers_remain_thin_http_adapters(): void
    {
        $portal = $this->source('app/Http/Controllers/TenantPortalController.php');
        $search = $this->source('app/Http/Controllers/GlobalSearchPageController.php');

        $this->assertLessThanOrEqual(45, substr_count($portal, "\n") + 1);
        $this->assertLessThanOrEqual(30, substr_count($search, "\n") + 1);
        $this->assertStringContainsString('TenantLeasePortalQuery', $portal);
        $this->assertStringContainsString('TenantPaymentPortalQuery', $portal);
        $this->assertStringContainsString('TenantDocumentPortalQuery', $portal);
        $this->assertStringContainsString('GlobalSearchQuery', $search);
        $this->assertStringNotContainsString('::query()', $portal.$search);
        $this->assertStringNotContainsString('->validate(', $portal.$search);
    }

    #[Test]
    public function tenant_portal_pages_are_composed_from_bounded_module_units(): void
    {
        foreach ([
            'resources/js/modules/tenant-portal/lease-page.tsx' => 80,
            'resources/js/modules/tenant-portal/lease-selector-hero.tsx' => 90,
            'resources/js/modules/tenant-portal/lease-metrics.tsx' => 80,
            'resources/js/modules/tenant-portal/lease-contract-panel.tsx' => 100,
            'resources/js/modules/tenant-portal/lease-document-panel.tsx' => 110,
            'resources/js/modules/tenant-portal/lease-schedule.tsx' => 125,
            'resources/js/modules/tenant-portal/payments-page.tsx' => 110,
            'resources/js/modules/tenant-portal/payment-records.tsx' => 150,
            'resources/js/modules/tenant-portal/documents-page.tsx' => 90,
            'resources/js/modules/tenant-portal/document-records.tsx' => 80,
            'resources/js/modules/tenant-portal/portal-filters.tsx' => 170,
            'resources/js/modules/search/results-page.tsx' => 150,
        ] as $path => $maximum) {
            $this->assertLessThanOrEqual(
                $maximum,
                substr_count($this->source($path), "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }
    }

    #[Test]
    public function tenant_portal_styles_are_route_loaded_and_layered(): void
    {
        $facade = $this->source('resources/css/styles/tenant-portal.css');
        $app = $this->source('resources/css/app.css');

        foreach (['base', 'downloads', 'filters', 'records', 'responsive'] as $layer) {
            $this->assertStringContainsString(
                "@import './tenant-portal/{$layer}.css';",
                $facade,
            );
            $this->assertLessThanOrEqual(
                160,
                substr_count($this->source("resources/css/styles/tenant-portal/{$layer}.css"), "\n") + 1,
            );
        }

        $this->assertLessThanOrEqual(6, substr_count($facade, "\n") + 1);
        $this->assertStringNotContainsString('styles/tenant-portal.css', $app);
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
