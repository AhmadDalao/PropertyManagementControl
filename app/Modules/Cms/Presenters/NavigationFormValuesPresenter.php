<?php

namespace App\Modules\Cms\Presenters;

use App\Models\NavigationItem;

final class NavigationFormValuesPresenter
{
    /** @return array<string, mixed> */
    public function present(?NavigationItem $item): array
    {
        if (! $item) {
            return [
                'location' => 'header',
                'parent_id' => '',
                'cms_page_id' => '',
                'title_en' => '',
                'title_ar' => '',
                'url' => '/',
                'target' => '_self',
                'sort_order' => 1,
                'is_visible' => true,
            ];
        }

        return [
            'location' => $item->location,
            'parent_id' => $item->parent_id ?? '',
            'cms_page_id' => $item->cms_page_id ?? '',
            'title_en' => $item->title_en,
            'title_ar' => $item->title_ar,
            'url' => $item->url ?? '/',
            'target' => $item->target,
            'sort_order' => $item->sort_order,
            'is_visible' => (bool) $item->is_visible,
        ];
    }
}
