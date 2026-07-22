<?php

namespace App\Modules\PublicSite\Queries;

use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PublicNavigationQuery
{
    /** @return Collection<int, NavigationItem> */
    public function header(): Collection
    {
        return NavigationItem::query()
            ->where('location', 'header')
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->where(function ($query): void {
                $this->constrainToPublicDestination($query);
            })
            ->with([
                'page:id,slug,title_en,title_ar,status,is_homepage,is_visible',
                'children' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->where(function ($destination): void {
                        $this->constrainToPublicDestination($destination);
                    })
                    ->with('page:id,slug,title_en,title_ar,status,is_homepage,is_visible'),
            ])
            ->orderBy('sort_order')
            ->get();
    }

    /** @param Builder<NavigationItem> $query */
    private function constrainToPublicDestination(Builder $query): void
    {
        $query
            ->whereNull('cms_page_id')
            ->orWhereHas('page', fn (Builder $page) => $page
                ->where('status', 'published')
                ->where('is_visible', true));
    }
}
