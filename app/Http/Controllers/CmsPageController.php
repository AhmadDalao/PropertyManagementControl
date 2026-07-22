<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Modules\Cms\Actions\ManageCmsPages;
use App\Modules\Cms\Presenters\CmsBuilderPresenter;
use App\Modules\Cms\Presenters\CmsPageFormPresenter;
use App\Modules\Cms\Queries\CmsWorkspaceQuery;
use App\Modules\Cms\Requests\StoreCmsPageRequest;
use App\Modules\Cms\Requests\UpdateCmsPageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly CmsWorkspaceQuery $workspace,
        private readonly CmsPageFormPresenter $pageForms,
        private readonly CmsBuilderPresenter $builder,
        private readonly ManageCmsPages $pages,
    ) {}

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
}
