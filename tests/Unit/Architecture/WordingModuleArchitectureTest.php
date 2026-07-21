<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WordingModuleArchitectureTest extends TestCase
{
    #[Test]
    public function wording_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source('app/Http/Controllers/WordingController.php');

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('WordingPagePresenter', $source);
        $this->assertStringContainsString('WordingIndexRequest', $source);
        $this->assertStringContainsString('SaveWordingRequest', $source);
        $this->assertStringContainsString('ResetWordingRequest', $source);
        $this->assertStringNotContainsString('LengthAwarePaginator', $source);
        $this->assertStringNotContainsString('LabelOverride', $source);
        $this->assertStringNotContainsString('->validate([', $source);
    }

    #[Test]
    public function public_catalog_facade_only_delegates_translation_work(): void
    {
        $source = $this->source('app/Modules/Wording/UiTranslationCatalog.php');

        $this->assertLessThanOrEqual(90, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('ResolvedUiTranslations', $source);
        $this->assertStringContainsString('WordingEntryCatalog', $source);
        $this->assertStringContainsString('ManageWordingOverrides', $source);
        $this->assertStringNotContainsString('LabelOverride', $source);
        $this->assertStringNotContainsString('Facades\\Cache', $source);
        $this->assertStringNotContainsString('Facades\\DB', $source);
        $this->assertStringNotContainsString('Facades\\Lang', $source);
    }

    #[Test]
    public function backend_wording_responsibilities_stay_in_focused_units(): void
    {
        foreach ([
            'app/Modules/Wording/Actions/ManageWordingOverrides.php',
            'app/Modules/Wording/Presenters/WordingEntryCatalog.php',
            'app/Modules/Wording/Presenters/WordingPagePresenter.php',
            'app/Modules/Wording/Queries/CmsContentTranslationQuery.php',
            'app/Modules/Wording/Queries/DocumentMediaContentTranslationQuery.php',
            'app/Modules/Wording/Queries/GlobalWordingOverrideQuery.php',
            'app/Modules/Wording/Queries/PropertyContentTranslationQuery.php',
            'app/Modules/Wording/Queries/ReportPresetContentTranslationQuery.php',
            'app/Modules/Wording/Queries/WordingIndexQuery.php',
            'app/Modules/Wording/Requests/ResetWordingRequest.php',
            'app/Modules/Wording/Requests/SaveWordingRequest.php',
            'app/Modules/Wording/Requests/WordingIndexRequest.php',
            'app/Modules/Wording/Support/ContentTranslationItem.php',
            'app/Modules/Wording/Support/DocumentationTranslationDefaults.php',
            'app/Modules/Wording/Support/RequiredTranslationTokens.php',
            'app/Modules/Wording/Support/ResolvedUiTranslations.php',
            'app/Modules/Wording/Support/TranslationDefaults.php',
            'app/Modules/Wording/TranslationCompletenessService.php',
        ] as $path) {
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                160,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $completeness = $this->source(
            'app/Modules/Wording/TranslationCompletenessService.php',
        );
        $this->assertLessThanOrEqual(100, substr_count($completeness, "\n") + 1);
        $this->assertStringNotContainsString('Portfolio::query()', $completeness);
        $this->assertStringNotContainsString('CmsPage::query()', $completeness);
    }

    #[Test]
    public function frontend_wording_entry_only_composes_focused_sections(): void
    {
        $source = $this->source('resources/js/modules/wording/index-page.tsx');

        $this->assertLessThanOrEqual(100, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './wording-catalog'", $source);
        $this->assertStringContainsString("from './wording-editor'", $source);
        $this->assertStringContainsString("from './content-translation-queue'", $source);
        $this->assertStringContainsString("from './use-wording-workspace'", $source);
        $this->assertStringNotContainsString('useState', $source);
        $this->assertStringNotContainsString('useForm', $source);
        $this->assertStringNotContainsString('router.', $source);
    }

    #[Test]
    public function frontend_wording_units_stay_focused(): void
    {
        foreach ([
            'content-translation-queue.tsx',
            'types.ts',
            'use-wording-workspace.ts',
            'wording-catalog.tsx',
            'wording-editor.tsx',
            'wording-entry-list.tsx',
            'wording-filters.tsx',
            'wording-labels.ts',
            'wording-metrics.tsx',
            'wording-pagination.tsx',
            'wording-tabs.tsx',
        ] as $file) {
            $path = "resources/js/modules/wording/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                220,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $state = $this->source(
            'resources/js/modules/wording/use-wording-workspace.ts',
        );
        $this->assertStringContainsString('useState', $state);
        $this->assertStringContainsString('router.get', $state);
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
