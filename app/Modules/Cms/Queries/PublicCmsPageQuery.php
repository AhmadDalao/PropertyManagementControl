<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use Illuminate\Database\Eloquent\Builder;

class PublicCmsPageQuery
{
    public function homepage(): ?CmsPage
    {
        return $this->visiblePage()
            ->where('is_homepage', true)
            ->with([
                'pageSections' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->whereHas('section', fn (Builder $section) => $section->where('status', 'active'))
                    ->with(['section' => fn ($query) => $query->where('status', 'active')]),
                'navigationItems' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->orderBy('sort_order'),
            ])
            ->first();
    }

    public function bySlug(string $slug): CmsPage
    {
        return $this->visiblePage()
            ->where('slug', $slug)
            ->with([
                'pageSections' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->whereHas('section', fn (Builder $section) => $section->where('status', 'active'))
                    ->with(['section' => fn ($query) => $query->where('status', 'active')]),
            ])
            ->firstOrFail();
    }

    /** @return Builder<CmsPage> */
    private function visiblePage(): Builder
    {
        return CmsPage::query()
            ->where('status', 'published')
            ->where('is_visible', true);
    }
}
