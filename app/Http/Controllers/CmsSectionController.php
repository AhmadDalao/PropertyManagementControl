<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Modules\Cms\Actions\ManageCmsSections;
use App\Modules\Cms\Presenters\CmsSectionFormPresenter;
use App\Modules\Cms\Requests\SaveCmsSectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CmsSectionController extends Controller
{
    public function __construct(
        private readonly CmsSectionFormPresenter $forms,
        private readonly ManageCmsSections $sections,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render(
            'admin/cms/section-form',
            $this->forms->present($this->actor($request)),
        );
    }

    public function edit(Request $request, CmsSection $cmsSection): Response
    {
        return Inertia::render(
            'admin/cms/section-form',
            $this->forms->present($this->actor($request), $cmsSection),
        );
    }

    public function store(SaveCmsSectionRequest $request): RedirectResponse
    {
        $this->sections->create($this->actor($request), $request->validated());

        return to_route('cms.index')->with('success', trans('app.messages.cms_section_created'));
    }

    public function update(SaveCmsSectionRequest $request, CmsSection $cmsSection): RedirectResponse
    {
        $this->sections->update($this->actor($request), $cmsSection, $request->validated());

        return to_route('cms.index')->with('success', trans('app.messages.cms_section_updated'));
    }

    public function destroy(Request $request, CmsSection $cmsSection): RedirectResponse
    {
        $this->sections->archive($this->actor($request), $cmsSection);

        return to_route('cms.index')->with('success', trans('app.messages.cms_section_archived'));
    }
}
