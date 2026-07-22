<?php

namespace App\Modules\Cms\Queries;

use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Collection;

final class CmsNavigationDirectoryQuery
{
    private const LIMIT = 60;

    /** @return array{navigationItems:Collection<int,NavigationItem>,navigationLimitReached:bool} */
    public function handle(): array
    {
        $count = NavigationItem::query()->count();

        return [
            'navigationItems' => NavigationItem::query()
                ->select(['id', 'parent_id', 'cms_page_id', 'location', 'title_en', 'title_ar', 'url', 'target', 'sort_order', 'is_visible'])
                ->with([
                    'children:id,parent_id,cms_page_id,location,title_en,title_ar,url,target,sort_order,is_visible',
                    'page:id,title_en,title_ar,slug,status,is_visible,is_homepage',
                ])
                ->whereNull('parent_id')
                ->orderBy('location')
                ->orderBy('sort_order')
                ->limit(self::LIMIT)
                ->get(),
            'navigationLimitReached' => $count > self::LIMIT,
        ];
    }
}
