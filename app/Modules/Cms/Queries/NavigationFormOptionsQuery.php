<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Models\NavigationItem;
use App\Modules\Shared\ResourcePresenter;

final class NavigationFormOptionsQuery
{
    private const LIMIT = 100;

    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array{parents:array<int,array{label:string,value:int|string}>,pages:array<int,array{label:string,value:int|string}>} */
    public function handle(?NavigationItem $item = null): array
    {
        return [
            'parents' => $this->parents($item),
            'pages' => $this->pages($item),
        ];
    }

    /** @return array<int, array{label:string,value:int|string}> */
    private function parents(?NavigationItem $item): array
    {
        $excludedIds = $item ? $this->descendantIds($item) : [];
        $options = [[
            'label' => (string) trans('app.cms.no_parent'),
            'value' => '',
        ]];
        $parents = NavigationItem::query()
            ->whereNull('parent_id')
            ->whereNotIn('id', $excludedIds)
            ->orderBy('location')
            ->orderBy('sort_order')
            ->limit(self::LIMIT)
            ->get(['id', 'title_en', 'title_ar', 'location']);

        foreach ($parents as $parent) {
            $options[] = [
                'label' => ($this->resources->localized($parent->title_en, $parent->title_ar) ?? '-')
                    .' · '.(string) trans("app.cms.location_{$parent->location}"),
                'value' => $parent->id,
            ];
        }

        return $options;
    }

    /** @return array<int, array{label:string,value:int|string}> */
    private function pages(?NavigationItem $item): array
    {
        $options = [[
            'label' => (string) trans('app.cms.custom_url'),
            'value' => '',
        ]];
        $pages = CmsPage::query()
            ->where(function ($query) use ($item): void {
                $query->where('status', '!=', 'archived');

                if ($item?->cms_page_id) {
                    $query->orWhere('id', $item->cms_page_id);
                }
            })
            ->orderByDesc('is_homepage')
            ->orderBy('title_en')
            ->limit(self::LIMIT)
            ->get(['id', 'title_en', 'title_ar', 'slug', 'is_homepage']);

        foreach ($pages as $page) {
            $options[] = [
                'label' => ($this->resources->localized($page->title_en, $page->title_ar) ?? '-')
                    .' · '.($page->is_homepage ? '/' : "/pages/{$page->slug}"),
                'value' => $page->id,
            ];
        }

        return $options;
    }

    /** @return array<int, int> */
    private function descendantIds(NavigationItem $item): array
    {
        $ids = [$item->id];
        $pending = [$item->id];

        while ($pending !== []) {
            $children = NavigationItem::query()
                ->whereIn('parent_id', $pending)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
            $pending = array_values(array_diff($children, $ids));
            $ids = [...$ids, ...$pending];
        }

        return $ids;
    }
}
