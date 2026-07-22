<?php

namespace App\Modules\PublicSite\Queries;

use App\Models\NavigationItem;
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
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }
}
