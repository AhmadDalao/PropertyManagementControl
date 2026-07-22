<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CmsModuleArchitectureTest extends TestCase
{
    #[Test]
    public function cms_controllers_stay_thin_http_adapters(): void
    {
        $cms = $this->source($this->path('app/Http/Controllers/CmsPageController.php'));
        $navigation = $this->source($this->path('app/Http/Controllers/NavigationItemController.php'));

        $this->assertLessThanOrEqual(180, substr_count($cms, "\n") + 1);
        $this->assertLessThanOrEqual(70, substr_count($navigation, "\n") + 1);
        $this->assertStringContainsString('CmsWorkspaceQuery', $cms);
        $this->assertStringContainsString('ComposeCmsPage', $cms);
        $this->assertStringContainsString('ManageCmsPages', $cms);
        $this->assertStringContainsString('ManageNavigationItems', $navigation);
        $this->assertStringNotContainsString('CmsPage::query()', $cms);
        $this->assertStringNotContainsString('->validate([', $cms.$navigation);
        $this->assertStringNotContainsString('DB::', $cms.$navigation);
    }

    #[Test]
    public function cms_frontend_entries_only_compose_module_components(): void
    {
        $index = $this->source($this->path('resources/js/modules/cms/index-page.tsx'));
        $builder = $this->source($this->path('resources/js/modules/cms/builder-page.tsx'));
        $sectionEditor = $this->source($this->path('resources/js/modules/cms/section-content-editor.tsx'));
        $sectionSchema = $this->source($this->path('resources/js/modules/cms/section-schema.ts'));

        $this->assertLessThanOrEqual(70, substr_count($index, "\n") + 1);
        $this->assertLessThanOrEqual(70, substr_count($builder, "\n") + 1);
        $this->assertLessThanOrEqual(140, substr_count($sectionEditor, "\n") + 1);
        $this->assertLessThanOrEqual(30, substr_count($sectionSchema, "\n") + 1);
        $this->assertStringContainsString("from './cms-pages-table'", $index);
        $this->assertStringContainsString("from './cms-workspace-header'", $index);
        $this->assertStringContainsString("from './use-cms-builder'", $builder);
        $this->assertStringContainsString("from './cms-builder-preview-pane'", $builder);
        $this->assertStringNotContainsString("from '@/components/data-table'", $index.$builder);
    }

    #[Test]
    public function cms_module_owns_each_content_responsibility(): void
    {
        foreach ([
            'app/Modules/Cms/Actions/ComposeCmsPage.php',
            'app/Modules/Cms/Actions/ManageCmsPages.php',
            'app/Modules/Cms/Actions/ManageCmsSections.php',
            'app/Modules/Cms/Actions/ManageNavigationItems.php',
            'app/Modules/Cms/Queries/CmsWorkspaceQuery.php',
            'app/Modules/Cms/Presenters/CmsBuilderPresenter.php',
            'app/Modules/Cms/Presenters/CmsPageFormPresenter.php',
            'app/Modules/Cms/Presenters/NavigationFormPresenter.php',
            'app/Modules/Cms/Requests/StoreCmsPageRequest.php',
            'app/Modules/Cms/Requests/UpdateCmsPageRequest.php',
            'app/Modules/Cms/Support/CmsAccess.php',
            'resources/js/modules/cms/cms-builder-inspector-pane.tsx',
            'resources/js/modules/cms/cms-builder-library-pane.tsx',
            'resources/js/modules/cms/cms-builder-preview-pane.tsx',
            'resources/js/modules/cms/section-content-schema-definition.ts',
            'resources/js/modules/cms/section-content-state.ts',
            'resources/js/modules/cms/section-content-templates.ts',
            'resources/js/modules/cms/section-content-types.ts',
            'resources/js/modules/cms/section-json.ts',
            'resources/js/modules/cms/section-language-editor.tsx',
            'resources/js/modules/cms/use-cms-builder.ts',
        ] as $relativePath) {
            $this->assertFileExists($this->path($relativePath));
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
