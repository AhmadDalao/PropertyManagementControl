<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ModuleOwnershipArchitectureTest extends TestCase
{
    #[Test]
    public function generic_service_and_support_namespaces_are_empty(): void
    {
        foreach (['app/Services', 'app/Support'] as $directory) {
            $files = glob($this->path("{$directory}/*.php")) ?: [];
            $this->assertSame([], $files, "{$directory} contains unowned code.");
        }

        $this->assertFileDoesNotExist($this->path('app/Support/LocalizedCopy.php'));
        $this->assertFileDoesNotExist($this->path('app/Services/XlsxWorkbook.php'));
        $this->assertFileDoesNotExist($this->path('app/Support/BilingualPdf.php'));
        $this->assertFileDoesNotExist($this->path('app/Support/PortfolioModules.php'));
    }

    #[Test]
    public function shared_infrastructure_has_a_named_module_owner(): void
    {
        foreach ([
            'app/Modules/Exports/Support/XlsxWorkbook.php' => 190,
            'app/Modules/Documents/Support/BilingualPdf.php' => 60,
            'app/Modules/Portfolios/Support/PortfolioModules.php' => 110,
            'app/Modules/Shared/Authorization/ActorAccess.php' => 40,
            'app/Modules/Shared/LocalizedStatusCounts.php' => 30,
        ] as $path => $limit) {
            $source = $this->source($path);
            $this->assertLessThanOrEqual(
                $limit,
                substr_count($source, "\n") + 1,
                "{$path} is becoming an infrastructure monolith.",
            );
        }
    }

    #[Test]
    public function base_controller_does_not_inherit_legacy_resource_concerns(): void
    {
        $this->assertFileDoesNotExist(
            $this->path('app/Http/Controllers/Concerns/BuildsResourcePages.php'),
        );
        $this->assertFileDoesNotExist(
            $this->path('app/Http/Controllers/Concerns/InteractsWithPortfolioScope.php'),
        );

        $source = $this->source('app/Http/Controllers/Controller.php');

        $this->assertLessThanOrEqual(30, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('ActorAccess::class', $source);
        $this->assertStringNotContainsString('AuthorizesRequests', $source);
        $this->assertStringNotContainsString('ValidatesRequests', $source);
    }

    #[Test]
    public function queue_compatibility_entries_are_only_alias_adapters(): void
    {
        foreach ([
            'app/Jobs/GenerateShowcaseBuilding.php',
            'app/Notifications/ResetPasswordNotification.php',
        ] as $path) {
            $source = $this->source($path);
            $this->assertLessThanOrEqual(6, substr_count($source, "\n") + 1);
            $this->assertStringContainsString('extends \App\Modules', $source);
            $this->assertStringNotContainsString('function ', $source);
        }
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
