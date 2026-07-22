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
        $sections = $this->source($this->path('app/Http/Controllers/CmsSectionController.php'));
        $composition = $this->source($this->path('app/Http/Controllers/CmsPageSectionController.php'));
        $navigation = $this->source($this->path('app/Http/Controllers/NavigationItemController.php'));

        $this->assertLessThanOrEqual(110, substr_count($cms, "\n") + 1);
        $this->assertLessThanOrEqual(90, substr_count($sections, "\n") + 1);
        $this->assertLessThanOrEqual(80, substr_count($composition, "\n") + 1);
        $this->assertLessThanOrEqual(70, substr_count($navigation, "\n") + 1);
        $this->assertStringContainsString('CmsWorkspaceQuery', $cms);
        $this->assertStringContainsString('ManageCmsPages', $cms);
        $this->assertStringContainsString('ManageCmsSections', $sections);
        $this->assertStringContainsString('ComposeCmsPage', $composition);
        $this->assertStringContainsString('ManageNavigationItems', $navigation);
        $this->assertStringNotContainsString('CmsPage::query()', $cms);
        $controllers = $cms.$sections.$composition.$navigation;
        $this->assertStringNotContainsString('->validate([', $controllers);
        $this->assertStringNotContainsString('DB::', $controllers);
    }

    #[Test]
    public function cms_frontend_entries_only_compose_module_components(): void
    {
        $index = $this->source($this->path('resources/js/modules/cms/index-page.tsx'));
        $builder = $this->source($this->path('resources/js/modules/cms/builder-page.tsx'));
        $sectionEditor = $this->source($this->path('resources/js/modules/cms/section-content-editor.tsx'));
        $sectionForm = $this->source($this->path('resources/js/modules/cms/section-form-page.tsx'));
        $sectionSchema = $this->source($this->path('resources/js/modules/cms/section-schema.ts'));

        $this->assertLessThanOrEqual(70, substr_count($index, "\n") + 1);
        $this->assertLessThanOrEqual(70, substr_count($builder, "\n") + 1);
        $this->assertLessThanOrEqual(140, substr_count($sectionEditor, "\n") + 1);
        $this->assertLessThanOrEqual(95, substr_count($sectionForm, "\n") + 1);
        $this->assertLessThanOrEqual(30, substr_count($sectionSchema, "\n") + 1);
        $this->assertStringContainsString("from './cms-pages-table'", $index);
        $this->assertStringContainsString("from './cms-workspace-header'", $index);
        $this->assertStringContainsString("from './use-cms-builder'", $builder);
        $this->assertStringContainsString("from './cms-builder-preview-pane'", $builder);
        $this->assertStringContainsString("from './use-cms-section-form'", $sectionForm);
        $this->assertStringNotContainsString("from '@/components/data-table'", $index.$builder);
    }

    #[Test]
    public function cms_actions_queries_and_requests_have_explicit_boundaries(): void
    {
        $pages = $this->source($this->path('app/Modules/Cms/Actions/ManageCmsPages.php'));
        $sections = $this->source($this->path('app/Modules/Cms/Actions/ManageCmsSections.php'));
        $composition = $this->source($this->path('app/Modules/Cms/Actions/ComposeCmsPage.php'));
        $navigation = $this->source($this->path('app/Modules/Cms/Actions/ManageNavigationItems.php'));
        $workspace = $this->source($this->path('app/Modules/Cms/Queries/CmsWorkspaceQuery.php'));
        $builder = $this->source($this->path('resources/js/modules/cms/use-cms-builder.ts'));

        $this->assertLessThanOrEqual(45, substr_count($pages, "\n") + 1);
        $this->assertLessThanOrEqual(45, substr_count($sections, "\n") + 1);
        $this->assertLessThanOrEqual(55, substr_count($composition, "\n") + 1);
        $this->assertLessThanOrEqual(45, substr_count($navigation, "\n") + 1);
        $this->assertLessThanOrEqual(60, substr_count($workspace, "\n") + 1);
        $this->assertLessThanOrEqual(35, substr_count($builder, "\n") + 1);
        $this->assertStringContainsString('CreateCmsPage', $pages);
        $this->assertStringContainsString('UpdateCmsPage', $pages);
        $this->assertStringContainsString('ArchiveCmsPage', $pages);
        $this->assertStringContainsString('CreateCmsSection', $sections);
        $this->assertStringContainsString('AttachCmsPageSection', $composition);
        $this->assertStringContainsString('CmsPageDirectoryQuery', $workspace);
        $this->assertStringContainsString('CmsWorkspaceInsightsQuery', $workspace);
        $this->assertStringContainsString('useCmsBuilderState', $builder);
        $this->assertStringContainsString('useCmsBuilderActions', $builder);
        $this->assertStringNotContainsString('DB::', $pages.$sections.$composition.$navigation);

        foreach ([
            'app/Modules/Cms/Requests/StoreCmsPageRequest.php',
            'app/Modules/Cms/Requests/SaveCmsSectionRequest.php',
            'app/Modules/Cms/Requests/SaveNavigationItemRequest.php',
            'app/Modules/Cms/Support/CmsInputGuard.php',
        ] as $relativePath) {
            $this->assertStringContainsString(
                'CmsRules',
                $this->source($this->path($relativePath)),
            );
        }
    }

    #[Test]
    public function cms_styles_are_owned_by_small_concern_layers(): void
    {
        $appCss = $this->source($this->path('resources/css/app.css'));

        $this->assertFileDoesNotExist($this->path('resources/css/styles/cms.css'));

        foreach (['workspace', 'section-editor', 'builder', 'responsive'] as $layer) {
            $relativePath = "resources/css/styles/cms/{$layer}.css";
            $source = $this->source($this->path($relativePath));
            $this->assertStringContainsString("./styles/cms/{$layer}.css", $appCss);
            $this->assertLessThanOrEqual(500, substr_count($source, "\n") + 1);
        }
    }

    #[Test]
    public function cms_module_owns_each_content_responsibility(): void
    {
        foreach ([
            'app/Modules/Cms/Actions/ComposeCmsPage.php',
            'app/Modules/Cms/Actions/AttachCmsPageSection.php',
            'app/Modules/Cms/Actions/UpdateCmsPageSection.php',
            'app/Modules/Cms/Actions/ReorderCmsPageSections.php',
            'app/Modules/Cms/Actions/RemoveCmsPageSection.php',
            'app/Modules/Cms/Actions/CreateCmsPage.php',
            'app/Modules/Cms/Actions/UpdateCmsPage.php',
            'app/Modules/Cms/Actions/ArchiveCmsPage.php',
            'app/Modules/Cms/Actions/CreateCmsSection.php',
            'app/Modules/Cms/Actions/UpdateCmsSection.php',
            'app/Modules/Cms/Actions/ArchiveCmsSection.php',
            'app/Modules/Cms/Actions/ManageCmsPages.php',
            'app/Modules/Cms/Actions/ManageCmsSections.php',
            'app/Modules/Cms/Actions/ManageNavigationItems.php',
            'app/Modules/Cms/Queries/CmsWorkspaceQuery.php',
            'app/Modules/Cms/Queries/CmsBuilderQuery.php',
            'app/Modules/Cms/Queries/CmsPageDirectoryQuery.php',
            'app/Modules/Cms/Queries/CmsWorkspaceInsightsQuery.php',
            'app/Modules/Cms/Queries/CmsSectionLibraryQuery.php',
            'app/Modules/Cms/Queries/CmsNavigationDirectoryQuery.php',
            'app/Modules/Cms/Presenters/CmsBuilderPresenter.php',
            'app/Modules/Cms/Presenters/CmsPageFormPresenter.php',
            'app/Modules/Cms/Presenters/NavigationFormPresenter.php',
            'app/Modules/Cms/Presenters/NavigationFormFieldsPresenter.php',
            'app/Modules/Cms/Presenters/NavigationFormValuesPresenter.php',
            'app/Modules/Cms/Queries/NavigationFormOptionsQuery.php',
            'app/Modules/Cms/Requests/StoreCmsPageRequest.php',
            'app/Modules/Cms/Requests/UpdateCmsPageRequest.php',
            'app/Modules/Cms/Support/CmsAccess.php',
            'app/Modules/Cms/Support/CmsRules.php',
            'app/Modules/Cms/Support/CmsInputGuard.php',
            'app/Modules/Cms/Support/CmsPublicationPolicy.php',
            'app/Modules/Cms/Support/CmsPageSectionOrder.php',
            'resources/js/modules/cms/cms-builder-inspector-pane.tsx',
            'resources/js/modules/cms/cms-builder-outline.tsx',
            'resources/js/modules/cms/cms-builder-selection.tsx',
            'resources/js/modules/cms/cms-builder-history.tsx',
            'resources/js/modules/cms/cms-builder-library-pane.tsx',
            'resources/js/modules/cms/cms-builder-preview-pane.tsx',
            'resources/js/modules/cms/section-content-schema-definition.ts',
            'resources/js/modules/cms/section-content-state.ts',
            'resources/js/modules/cms/section-content-templates.ts',
            'resources/js/modules/cms/section-content-types.ts',
            'resources/js/modules/cms/section-json.ts',
            'resources/js/modules/cms/section-language-editor.tsx',
            'resources/js/modules/cms/section-field-control.tsx',
            'resources/js/modules/cms/section-collection-editor.tsx',
            'resources/js/modules/cms/use-cms-builder.ts',
            'resources/js/modules/cms/use-cms-builder-state.ts',
            'resources/js/modules/cms/use-cms-builder-actions.ts',
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
