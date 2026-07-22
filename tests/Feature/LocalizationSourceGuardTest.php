<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationSourceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_workspaces_do_not_add_visible_english_jsx_literals(): void
    {
        $files = [
            'resources/js/modules/property-map/geographic-map.tsx',
            'resources/js/modules/property-map/index-page.tsx',
            'resources/js/modules/property-map/map-filters.tsx',
            'resources/js/modules/property-map/map-metrics.tsx',
            'resources/js/modules/property-map/map-setup-status.tsx',
            'resources/js/modules/property-map/map-stage.tsx',
            'resources/js/modules/property-map/map-workspace.tsx',
            'resources/js/modules/property-map/portfolio-filter.tsx',
            'resources/js/modules/property-map/property-map-detail.tsx',
            'resources/js/modules/property-map/property-map-directory.tsx',
            'resources/js/modules/showcase-data/index-page.tsx',
            'resources/js/modules/showcase-data/data-lab-header.tsx',
            'resources/js/modules/showcase-data/data-lab-overview.tsx',
            'resources/js/modules/showcase-data/data-lab-target-plan.tsx',
            'resources/js/modules/showcase-data/dataset-card.tsx',
            'resources/js/modules/showcase-data/dataset-history.tsx',
            'resources/js/modules/showcase-data/dataset-pagination.tsx',
            'resources/js/modules/showcase-data/purge-dialog.tsx',
            'resources/js/modules/wording/content-translation-queue.tsx',
            'resources/js/modules/wording/index-page.tsx',
            'resources/js/modules/wording/wording-catalog.tsx',
            'resources/js/modules/wording/wording-editor.tsx',
            'resources/js/modules/wording/wording-entry-list.tsx',
            'resources/js/modules/wording/wording-filters.tsx',
            'resources/js/modules/wording/wording-metrics.tsx',
            'resources/js/modules/wording/wording-pagination.tsx',
            'resources/js/modules/wording/wording-tabs.tsx',
            'resources/js/modules/audit/index-page.tsx',
            'resources/js/modules/audit/audit-metrics.tsx',
            'resources/js/modules/audit/audit-table.tsx',
            'resources/js/modules/search/global-search.tsx',
            'resources/js/modules/search/mobile-search-sheet.tsx',
            'resources/js/modules/search/search-field.tsx',
            'resources/js/layouts/admin-layout.tsx',
            'resources/js/layouts/public-layout.tsx',
            'resources/js/pages/auth/login.tsx',
        ];

        $publicSite = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                resource_path('js/modules/public-site'),
            ),
        );

        foreach ($publicSite as $file) {
            if ($file->isFile() && $file->getExtension() === 'tsx') {
                $files[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        foreach ($files as $file) {
            $source = (string) file_get_contents(base_path($file));
            $source = preg_replace('/>\s*(?:PMC|PC)\s*</', '><', $source) ?? $source;

            $this->assertDoesNotMatchRegularExpression(
                '/>\s*[A-Z][A-Za-z][^<{]*</',
                $source,
                "Visible English JSX text was added to [{$file}]. Use a translation key.",
            );
            $this->assertDoesNotMatchRegularExpression(
                '/(?:placeholder|aria-label|title)="[A-Z][^"]+"/',
                $source,
                "A visible English attribute was added to [{$file}]. Use a translation key.",
            );
        }
    }

    public function test_server_success_and_authorization_messages_use_translation_keys(): void
    {
        $directories = [
            app_path('Http/Controllers'),
            app_path('Http/Middleware'),
            app_path('Http/Requests'),
            app_path('Services'),
        ];

        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $source = (string) file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    "/->with\\('(success|error)',\\s*'[A-Z]/",
                    $source,
                    "Hardcoded flash copy found in [{$file->getPathname()}].",
                );
                $this->assertDoesNotMatchRegularExpression(
                    "/abort_(?:if|unless)\\([^\\n]*'[A-Z][A-Za-z ]{5,}/",
                    $source,
                    "Hardcoded authorization copy found in [{$file->getPathname()}].",
                );
            }
        }
    }
}
