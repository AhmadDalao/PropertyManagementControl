<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeaseMoveOutModuleArchitectureTest extends TestCase
{
    #[Test]
    public function move_out_controller_and_inertia_entry_stay_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/LeaseMoveOutController.php');
        $page = $this->source('resources/js/modules/lease-move-outs/index-page.tsx');
        $wrapper = $this->source('resources/js/pages/admin/lease-move-outs/index.tsx');

        $this->assertLessThanOrEqual(90, substr_count($controller, "\n") + 1);
        $this->assertStringContainsString('LeaseMoveOutIndexQuery', $controller);
        $this->assertStringContainsString('PlanLeaseMoveOut', $controller);
        $this->assertStringNotContainsString('LeaseMoveOut::query()', $controller);
        $this->assertLessThanOrEqual(10, substr_count($wrapper, "\n") + 1);
        $this->assertStringContainsString('LeaseMoveOutIndexPage', $page);
        $this->assertStringContainsString(
            "from '@/modules/lease-move-outs/index-page'",
            $wrapper,
        );
    }

    #[Test]
    public function move_out_module_keeps_actions_queries_presenters_and_ui_separate(): void
    {
        foreach ([
            'app/Modules/LeaseMoveOuts/Actions/PlanLeaseMoveOut.php',
            'app/Modules/LeaseMoveOuts/Actions/CompleteLeaseMoveOut.php',
            'app/Modules/LeaseMoveOuts/Actions/CancelLeaseMoveOut.php',
            'app/Modules/LeaseMoveOuts/Actions/LeaseMoveOutWorkbookExport.php',
            'app/Modules/LeaseMoveOuts/Queries/LeaseMoveOutDirectoryQuery.php',
            'app/Modules/LeaseMoveOuts/Queries/LeaseMoveOutIndexQuery.php',
            'app/Modules/LeaseMoveOuts/Presenters/LeaseMoveOutFormPresenter.php',
            'app/Modules/LeaseMoveOuts/Presenters/LeaseMoveOutProgressPresenter.php',
            'app/Modules/LeaseMoveOuts/Requests/UpsertLeaseMoveOutRequest.php',
            'app/Modules/LeaseMoveOuts/Support/LeaseMoveOutGuard.php',
            'resources/js/modules/lease-move-outs/index-page.tsx',
            'resources/js/modules/lease-move-outs/lease-move-out-table.tsx',
            'resources/js/modules/lease-move-outs/lease-move-out-table-config.tsx',
            'resources/js/modules/lease-move-outs/types.ts',
        ] as $path) {
            $this->assertFileExists($this->path($path), $path);
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
