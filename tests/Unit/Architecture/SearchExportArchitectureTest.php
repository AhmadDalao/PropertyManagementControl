<?php

namespace Tests\Unit\Architecture;

use App\Modules\ModuleRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SearchExportArchitectureTest extends TestCase
{
    #[Test]
    public function shared_search_and_export_controllers_stay_thin(): void
    {
        $search = $this->source('app/Http/Controllers/GlobalSearchController.php');
        $exports = $this->source('app/Http/Controllers/AdminExportController.php');

        $this->assertLessThanOrEqual(30, substr_count($search, "\n") + 1);
        $this->assertLessThanOrEqual(30, substr_count($exports, "\n") + 1);
        $this->assertStringContainsString('GlobalSearchQuery', $search);
        $this->assertStringContainsString('ResourceExportRegistry', $exports);
        $this->assertStringNotContainsString('::query()', $search.$exports);
        $this->assertStringNotContainsString('XlsxWorkbook', $exports);
        $this->assertStringNotContainsString('PortfolioModules', $search.$exports);
    }

    #[Test]
    public function every_searchable_and_exportable_resource_owns_its_adapter(): void
    {
        foreach ([
            ['app/Modules/Portfolios/Queries/PortfolioSearch.php', 'app/Modules/Portfolios/Actions/PortfolioWorkbookExport.php'],
            ['app/Modules/Users/Queries/UserSearch.php', 'app/Modules/Users/Actions/UserWorkbookExport.php'],
            ['app/Modules/Assets/Queries/AssetSearch.php', 'app/Modules/Assets/Actions/AssetWorkbookExport.php'],
            ['app/Modules/Tenants/Queries/TenantSearch.php', 'app/Modules/Tenants/Actions/TenantWorkbookExport.php'],
            ['app/Modules/Leases/Queries/LeaseSearch.php', 'app/Modules/Leases/Actions/LeaseWorkbookExport.php'],
            ['app/Modules/Payments/Queries/PaymentSearch.php', 'app/Modules/Payments/Actions/PaymentWorkbookExport.php'],
            ['app/Modules/Maintenance/Queries/MaintenanceSearch.php', 'app/Modules/Maintenance/Actions/MaintenanceWorkbookExport.php'],
            ['app/Modules/Expenses/Queries/ExpenseSearch.php', 'app/Modules/Expenses/Actions/ExpenseWorkbookExport.php'],
            ['app/Modules/Documents/Queries/DocumentSearch.php', 'app/Modules/Documents/Actions/DocumentWorkbookExport.php'],
            ['app/Modules/Media/Queries/MediaFileSearch.php', 'app/Modules/Media/Actions/MediaFileWorkbookExport.php'],
            ['app/Modules/Cms/Queries/CmsPageSearch.php', 'app/Modules/Cms/Actions/CmsPageWorkbookExport.php'],
        ] as [$search, $export]) {
            $this->assertFileExists($this->path($search));
            $this->assertFileExists($this->path($export));
            $this->assertStringContainsString('SearchSource', $this->source($search));
            $this->assertStringContainsString('ResourceExporter', $this->source($export));
        }
    }

    #[Test]
    public function export_queries_are_shared_with_resource_indexes(): void
    {
        foreach ([
            'app/Modules/Portfolios/Queries/PortfolioIndexQuery.php',
            'app/Modules/Users/Queries/UserIndexQuery.php',
            'app/Modules/Assets/Queries/AssetIndexQuery.php',
            'app/Modules/Tenants/Queries/TenantIndexQuery.php',
            'app/Modules/Leases/Queries/LeaseIndexQuery.php',
            'app/Modules/Payments/Queries/PaymentIndexQuery.php',
            'app/Modules/Maintenance/Queries/MaintenanceIndexQuery.php',
            'app/Modules/Expenses/Queries/ExpenseIndexQuery.php',
            'app/Modules/Documents/Queries/DocumentIndexQuery.php',
            'app/Modules/Media/Queries/MediaFileIndexQuery.php',
            'app/Modules/Cms/Queries/CmsWorkspaceQuery.php',
        ] as $query) {
            $this->assertStringContainsString('function forExport(', $this->source($query));
        }

        $controller = $this->source('app/Http/Controllers/Controller.php');
        $this->assertStringNotContainsString('BuildsAdminTables', $controller);
        $this->assertFileDoesNotExist($this->path('app/Http/Controllers/Concerns/BuildsAdminTables.php'));
    }

    #[Test]
    public function global_search_frontend_is_a_module_not_a_monolith(): void
    {
        $wrapper = $this->source('resources/js/components/global-search.tsx');
        $composer = $this->source('resources/js/modules/search/global-search.tsx');

        $this->assertLessThanOrEqual(5, substr_count($wrapper, "\n") + 1);
        $this->assertLessThanOrEqual(70, substr_count($composer, "\n") + 1);
        $this->assertStringContainsString("from './use-global-search'", $composer);
        $this->assertStringContainsString("from './mobile-search-sheet'", $composer);
        $this->assertStringContainsString("from './search-field'", $composer);

        foreach ([
            'resources/js/modules/search/types.ts',
            'resources/js/modules/search/group-results.ts',
            'resources/js/modules/search/use-global-search.ts',
            'resources/js/modules/search/use-mobile-search.ts',
            'resources/js/modules/search/search-field.tsx',
            'resources/js/modules/search/mobile-search-sheet.tsx',
        ] as $path) {
            $this->assertFileExists($this->path($path));
        }
    }

    #[Test]
    public function cross_cutting_modules_are_registered_as_infrastructure(): void
    {
        $this->assertSame(
            ['search', 'exports'],
            array_keys(ModuleRegistry::infrastructureModules()),
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
