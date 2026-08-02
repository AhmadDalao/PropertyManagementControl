<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ActionCenterModuleArchitectureTest extends TestCase
{
    #[Test]
    public function action_center_http_and_page_entries_stay_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/ActionCenterController.php');
        $reportController = $this->source('app/Http/Controllers/ActionCenterReportController.php');
        $page = $this->source('resources/js/modules/action-center/index-page.tsx');
        $wrapper = $this->source('resources/js/pages/admin/action-center/index.tsx');

        $this->assertLessThanOrEqual(45, substr_count($controller, "\n") + 1);
        $this->assertLessThanOrEqual(40, substr_count($reportController, "\n") + 1);
        $this->assertLessThanOrEqual(45, substr_count($page, "\n") + 1);
        $this->assertLessThanOrEqual(3, substr_count($wrapper, "\n") + 1);
        $this->assertStringContainsString('ActionCenterIndexQuery', $controller);
        $this->assertStringContainsString('ActionCenterWorkbookExport', $controller);
        $this->assertStringContainsString('ActionCenterReportQuery', $reportController);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringNotContainsString('::query()', $reportController);
        $this->assertStringContainsString("from './action-center-filters'", $page);
        $this->assertStringContainsString("from './action-center-workspace'", $page);
    }

    #[Test]
    public function queue_sources_and_frontend_units_remain_bounded(): void
    {
        foreach ([
            'app/Modules/ActionCenter/Queries/ActionCenterIndexQuery.php' => 250,
            'app/Modules/ActionCenter/Queries/CollectionActionSource.php' => 260,
            'app/Modules/ActionCenter/Queries/MaintenanceActionSource.php' => 210,
            'app/Modules/ActionCenter/Queries/RenewalActionSource.php' => 190,
            'app/Modules/ActionCenter/Queries/MoveOutActionSource.php' => 200,
            'app/Modules/ActionCenter/Actions/ActionCenterWorkbookExport.php' => 100,
            'app/Modules/ActionCenter/Actions/ActionCenterPdfExport.php' => 60,
            'app/Modules/ActionCenter/Actions/ActionCenterWordExport.php' => 90,
            'app/Modules/ActionCenter/Presenters/ActionCenterExportRows.php' => 160,
            'app/Modules/ActionCenter/Presenters/ActionCenterReportPresenter.php' => 210,
            'app/Modules/ActionCenter/Queries/ActionCenterReportQuery.php' => 50,
            'app/Modules/ActionCenter/Requests/ActionCenterIndexRequest.php' => 100,
            'resources/views/pdf/daily-operations-brief.blade.php' => 180,
            'resources/js/modules/action-center/action-center-downloads.ts' => 50,
            'resources/js/modules/action-center/action-center-card.tsx' => 190,
            'resources/js/modules/action-center/action-center-work-order-context.tsx' => 70,
            'resources/js/modules/action-center/action-center-filter-controls.tsx' => 220,
            'resources/js/modules/action-center/action-center-filters.tsx' => 90,
            'resources/js/modules/action-center/action-center-type-chips.tsx' => 70,
            'resources/js/modules/action-center/action-center-workspace.tsx' => 70,
        ] as $path => $maximum) {
            $this->assertLessThanOrEqual(
                $maximum,
                substr_count($this->source($path), "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }
    }

    #[Test]
    public function action_center_styles_are_route_loaded_and_layered(): void
    {
        $facade = $this->source('resources/css/styles/action-center.css');
        $cards = $this->source('resources/css/styles/action-center/cards.css');
        $app = $this->source('resources/css/app.css');

        foreach (['base.css', 'filters.css', 'cards.css', 'responsive.css'] as $file) {
            $this->assertStringContainsString(
                "@import './action-center/{$file}';",
                $facade,
            );
        }

        foreach ([
            'card-shell.css',
            'card-header.css',
            'card-context.css',
            'card-state.css',
            'card-footer.css',
        ] as $file) {
            $this->assertStringContainsString("@import './{$file}';", $cards);
            $this->assertLessThanOrEqual(
                120,
                substr_count($this->source("resources/css/styles/action-center/{$file}"), "\n") + 1,
            );
        }

        $this->assertLessThanOrEqual(8, substr_count($facade, "\n") + 1);
        $this->assertLessThanOrEqual(8, substr_count($cards, "\n") + 1);
        $this->assertStringNotContainsString('styles/action-center.css', $app);
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
