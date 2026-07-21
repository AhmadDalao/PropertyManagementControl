<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SharedFrontendArchitectureTest extends TestCase
{
    #[Test]
    public function shared_component_entry_points_are_compatibility_barrels(): void
    {
        foreach ([
            'resources/js/components/resource-cycle.tsx' => './resource-cycle/index',
            'resources/js/components/data-table.tsx' => './data-table/index',
        ] as $path => $module) {
            $source = $this->source($path);

            $this->assertLessThanOrEqual(5, substr_count($source, "\n") + 1);
            $this->assertStringContainsString($module, $source);
            $this->assertStringNotContainsString('useState', $source);
            $this->assertStringNotContainsString('@inertiajs/react', $source);
        }
    }

    #[Test]
    public function resource_cycle_keeps_forms_details_and_workflows_separate(): void
    {
        $paths = [
            'action-link.tsx',
            'decision-card-grid.tsx',
            'detail-card.tsx',
            'document-strip.tsx',
            'history-timeline.tsx',
            'related-records-table.tsx',
            'resource-detail-shell.tsx',
            'resource-detail-tabs.tsx',
            'resource-form-shell.tsx',
            'resource-header.tsx',
            'resource-input.tsx',
            'resource-spotlight-panel.tsx',
            'types.ts',
        ];

        $this->assertModulesStayFocused('resource-cycle', $paths, 250);

        $form = $this->source(
            'resources/js/components/resource-cycle/resource-form-shell.tsx',
        );
        $detail = $this->source(
            'resources/js/components/resource-cycle/resource-detail-shell.tsx',
        );

        $this->assertStringNotContainsString('HistoryTimeline', $form);
        $this->assertStringNotContainsString('useForm', $detail);
    }

    #[Test]
    public function data_table_keeps_query_and_rendering_responsibilities_separate(): void
    {
        $paths = [
            'data-table.tsx',
            'desktop-record-table.tsx',
            'mobile-record-list.tsx',
            'showcase-badge.tsx',
            'table-empty.tsx',
            'table-header.tsx',
            'table-pagination.tsx',
            'table-toolbar.tsx',
            'table-utils.ts',
            'types.ts',
            'use-table-query.ts',
        ];

        $this->assertModulesStayFocused('data-table', $paths, 220);

        $desktop = $this->source(
            'resources/js/components/data-table/desktop-record-table.tsx',
        );
        $mobile = $this->source(
            'resources/js/components/data-table/mobile-record-list.tsx',
        );

        $this->assertStringNotContainsString('router.get', $desktop.$mobile);
        $this->assertStringNotContainsString('pmc-mobile-record-card', $desktop);
        $this->assertStringNotContainsString('<table', $mobile);
    }

    /**
     * @param  list<string>  $paths
     */
    private function assertModulesStayFocused(
        string $module,
        array $paths,
        int $maximumLines,
    ): void {
        foreach ($paths as $path) {
            $source = $this->source(
                "resources/js/components/{$module}/{$path}",
            );

            $this->assertLessThanOrEqual(
                $maximumLines,
                substr_count($source, "\n") + 1,
                "{$module}/{$path} is becoming a monolith.",
            );
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
