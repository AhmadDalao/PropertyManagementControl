<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Modules\Cms\Actions\ComposeCmsPage;
use App\Modules\Cms\Actions\ManageCmsPages;
use App\Modules\Cms\Actions\ManageCmsSections;
use App\Modules\Cms\Presenters\CmsBuilderPresenter;
use App\Modules\Cms\Presenters\CmsPageFormPresenter;
use App\Modules\Cms\Queries\CmsWorkspaceQuery;
use App\Modules\Cms\Queries\PublicCmsPageQuery;
use App\Modules\Cms\Requests\AttachCmsSectionRequest;
use App\Modules\Cms\Requests\ReorderCmsPageSectionsRequest;
use App\Modules\Cms\Requests\SaveCmsSectionRequest;
use App\Modules\Cms\Requests\StoreCmsPageRequest;
use App\Modules\Cms\Requests\UpdateCmsPageRequest;
use App\Modules\Cms\Requests\UpdateCmsPageSectionRequest;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly PublicCmsPageQuery $publicPages,
        private readonly CmsWorkspaceQuery $workspace,
        private readonly CmsPageFormPresenter $pageForms,
        private readonly CmsBuilderPresenter $builder,
        private readonly ManageCmsPages $pages,
        private readonly ManageCmsSections $sections,
        private readonly ComposeCmsPage $composition,
        private readonly CmsAccess $access,
    ) {}

    public function home(): Response
    {
        return Inertia::render('public/home', ['page' => $this->publicPages->homepage()]);
    }

    public function show(string $slug): Response
    {
        return Inertia::render('public/page', ['page' => $this->publicPages->bySlug($slug)]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/cms/index',
            $this->workspace->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->pageForms->present($this->actor($request)),
        ]);
    }

    public function builder(Request $request, CmsPage $cmsPage): Response
    {
        return Inertia::render(
            'admin/cms/builder',
            $this->builder->present($this->actor($request), $cmsPage),
        );
    }

    public function edit(Request $request, CmsPage $cmsPage): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->pageForms->present($this->actor($request), $cmsPage),
        ]);
    }

    public function createSection(Request $request): Response
    {
        $this->access->ensureAdmin($this->actor($request));

        return Inertia::render('admin/cms/section-form', [
            'section' => null,
            'sectionTypes' => CmsOptions::sectionTypes(),
        ]);
    }

    public function editSection(Request $request, CmsSection $cmsSection): Response
    {
        $this->access->ensureAdmin($this->actor($request));

        return Inertia::render('admin/cms/section-form', [
            'section' => $cmsSection,
            'sectionTypes' => CmsOptions::sectionTypes(),
        ]);
    }

    public function store(StoreCmsPageRequest $request): RedirectResponse
    {
        $page = $this->pages->create($this->actor($request), $request->validated());

        return to_route('cms.pages.show', $page)->with('success', trans('app.messages.cms_page_created'));
    }

    public function update(UpdateCmsPageRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $page = $this->pages->update($this->actor($request), $cmsPage, $request->validated());

        return to_route('cms.pages.show', $page)->with('success', trans('app.messages.cms_page_updated'));
    }

    public function destroy(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $this->pages->archive($this->actor($request), $cmsPage);

        return to_route('cms.index')->with('success', trans('app.messages.cms_page_archived'));
    }

    public function storeSection(SaveCmsSectionRequest $request): RedirectResponse
    {
        $this->sections->create($this->actor($request), $request->validated());

        return to_route('cms.index')->with('success', trans('app.messages.cms_section_created'));
    }

    public function updateSection(SaveCmsSectionRequest $request, CmsSection $cmsSection): RedirectResponse
    {
        $this->sections->update($this->actor($request), $cmsSection, $request->validated());

        return to_route('cms.index')->with('success', trans('app.messages.cms_section_updated'));
    }

    public function destroySection(Request $request, CmsSection $cmsSection): RedirectResponse
    {
        $this->sections->archive($this->actor($request), $cmsSection);

        return to_route('cms.index')->with('success', trans('app.messages.cms_section_archived'));
    }

    public function attachSection(AttachCmsSectionRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $this->composition->attach($this->actor($request), $cmsPage, $request->validated());

        return to_route('cms.pages.show', $cmsPage)->with('success', trans('app.messages.cms_section_attached'));
    }

    public function updatePageSection(UpdateCmsPageSectionRequest $request, CmsPageSection $cmsPageSection): RedirectResponse
    {
        $pageSection = $this->composition->update($this->actor($request), $cmsPageSection, $request->validated());

        return to_route('cms.pages.show', $pageSection->page)->with('success', trans('app.messages.cms_page_section_updated'));
    }

    public function reorderPageSections(ReorderCmsPageSectionsRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $orderedIds = array_map('intval', $request->validated('ordered_ids'));
        $this->composition->reorder($this->actor($request), $cmsPage, $orderedIds);

        return to_route('cms.pages.show', $cmsPage)->with('success', trans('app.messages.cms_sections_reordered'));
    }

    public function destroyPageSection(Request $request, CmsPageSection $cmsPageSection): RedirectResponse
    {
        $page = $this->composition->remove($this->actor($request), $cmsPageSection);

        return to_route('cms.pages.show', $page)->with('success', trans('app.messages.cms_page_section_removed'));
    }
}
