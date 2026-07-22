<?php

namespace App\Modules\PublicSite\Queries;

use App\Models\CmsPage;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class PublicPageQuery
{
    public function homepage(): ?CmsPage
    {
        return $this->visiblePage()
            ->where('is_homepage', true)
            ->with([
                ...$this->publicSections(),
                'navigationItems' => $this->visibleNavigation(),
            ])
            ->first();
    }

    public function bySlug(string $slug): CmsPage
    {
        return $this->visiblePage()
            ->where('slug', $slug)
            ->with($this->publicSections())
            ->firstOrFail();
    }

    /** @return Builder<CmsPage> */
    private function visiblePage(): Builder
    {
        return CmsPage::query()
            ->where('status', 'published')
            ->where('is_visible', true);
    }

    /**
     * @return array{pageSections: Closure(Relation<*, *, *>): mixed}
     */
    private function publicSections(): array
    {
        return [
            'pageSections' => fn ($query) => $query
                ->where('is_visible', true)
                ->whereHas('section', fn (Builder $section) => $section->where('status', 'active'))
                ->with(['section' => fn ($query) => $query->where('status', 'active')]),
        ];
    }

    /** @return Closure(Relation<*, *, *>): mixed */
    private function visibleNavigation(): Closure
    {
        return fn (Relation $query) => $query
            ->where('is_visible', true)
            ->orderBy('sort_order');
    }
}
