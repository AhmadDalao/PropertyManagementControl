<?php

namespace App\Modules\Cms\Support;

use App\Models\NavigationItem;

final class NavigationAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forCreate(array $data): array
    {
        return $this->build($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forUpdate(NavigationItem $item, array $data): array
    {
        return $this->build($data, $item);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function build(array $data, ?NavigationItem $item = null): array
    {
        return [
            'parent_id' => $data['parent_id'] ?? null,
            'cms_page_id' => $data['cms_page_id'] ?? null,
            'location' => $data['location'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'url' => $data['url'] ?? null,
            'target' => $data['target'],
            'sort_order' => $data['sort_order'],
            'is_visible' => (bool) ($data['is_visible'] ?? ($item ? $item->is_visible : true)),
        ];
    }
}
