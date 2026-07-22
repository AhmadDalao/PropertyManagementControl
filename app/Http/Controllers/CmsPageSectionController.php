<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Modules\Cms\Actions\ComposeCmsPage;
use App\Modules\Cms\Requests\AttachCmsSectionRequest;
use App\Modules\Cms\Requests\ReorderCmsPageSectionsRequest;
use App\Modules\Cms\Requests\UpdateCmsPageSectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CmsPageSectionController extends Controller
{
    public function __construct(private readonly ComposeCmsPage $composition) {}

    public function store(AttachCmsSectionRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $this->composition->attach($this->actor($request), $cmsPage, $request->validated());

        return to_route('cms.pages.show', $cmsPage)->with('success', trans('app.messages.cms_section_attached'));
    }

    public function update(UpdateCmsPageSectionRequest $request, CmsPageSection $cmsPageSection): RedirectResponse
    {
        $pageSection = $this->composition->update($this->actor($request), $cmsPageSection, $request->validated());

        return to_route('cms.pages.show', $pageSection->page)->with('success', trans('app.messages.cms_page_section_updated'));
    }

    public function reorder(ReorderCmsPageSectionsRequest $request, CmsPage $cmsPage): RedirectResponse
    {
        $this->composition->reorder($this->actor($request), $cmsPage, $request->orderedIds());

        return to_route('cms.pages.show', $cmsPage)->with('success', trans('app.messages.cms_sections_reordered'));
    }

    public function destroy(Request $request, CmsPageSection $cmsPageSection): RedirectResponse
    {
        $page = $this->composition->remove($this->actor($request), $cmsPageSection);

        return to_route('cms.pages.show', $page)->with('success', trans('app.messages.cms_page_section_removed'));
    }
}
