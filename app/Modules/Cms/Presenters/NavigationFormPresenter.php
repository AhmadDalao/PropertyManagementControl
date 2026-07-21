<?php

namespace App\Modules\Cms\Presenters;

use App\Models\CmsPage;
use App\Models\NavigationItem;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsOptions;
use App\Modules\Shared\ResourcePresenter;

class NavigationFormPresenter
{
    private const OPTION_LIMIT = 100;

    public function __construct(
        private readonly CmsAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?NavigationItem $item = null): array
    {
        $this->access->ensureAdmin($actor);
        $excludedIds = $item ? $this->descendantIds($item) : [];
        $parents = NavigationItem::query()
            ->whereNull('parent_id')
            ->whereNotIn('id', $excludedIds)
            ->orderBy('location')
            ->orderBy('sort_order')
            ->limit(self::OPTION_LIMIT)
            ->get(['id', 'title_en', 'title_ar', 'location']);
        $parentOptions = [[
            'label' => (string) trans('app.cms.no_parent'),
            'value' => '',
        ]];

        foreach ($parents as $parent) {
            $parentOptions[] = [
                'label' => ($this->resources->localized($parent->title_en, $parent->title_ar) ?? '-')
                    .' · '.(string) trans("app.cms.location_{$parent->location}"),
                'value' => $parent->id,
            ];
        }

        $pages = CmsPage::query()
            ->where('status', '!=', 'archived')
            ->orderByDesc('is_homepage')
            ->orderBy('title_en')
            ->limit(self::OPTION_LIMIT)
            ->get(['id', 'title_en', 'title_ar', 'slug', 'is_homepage']);
        $pageOptions = [[
            'label' => (string) trans('app.cms.custom_url'),
            'value' => '',
        ]];

        foreach ($pages as $page) {
            $pageOptions[] = [
                'label' => ($this->resources->localized($page->title_en, $page->title_ar) ?? '-')
                    .' · '.($page->is_homepage ? '/' : "/pages/{$page->slug}"),
                'value' => $page->id,
            ];
        }

        $initialValues = $item
            ? [
                'location' => $item->location,
                'parent_id' => $item->parent_id ?? '',
                'cms_page_id' => $item->cms_page_id ?? '',
                'title_en' => $item->title_en,
                'title_ar' => $item->title_ar,
                'url' => $item->url ?? '/',
                'target' => $item->target,
                'sort_order' => $item->sort_order,
                'is_visible' => (bool) $item->is_visible,
            ]
            : [
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

        return [
            'title' => $item ? trans('app.cms.edit_navigation') : trans('app.cms.create_navigation'),
            'description' => trans('app.cms.navigation_form_description'),
            'backHref' => route('cms.index', ['view' => 'navigation']),
            'backLabel' => trans('app.cms.website_control'),
            'action' => $item
                ? route('navigation-items.update', $item)
                : route('navigation-items.store'),
            'method' => $item ? 'put' : 'post',
            'submitLabel' => $item ? trans('app.cms.update_navigation') : trans('app.cms.create_navigation'),
            'fields' => $this->resources->sectionFields([
                [
                    'name' => 'location',
                    'label' => trans('app.cms.location'),
                    'type' => 'select',
                    'required' => true,
                    'options' => collect(CmsOptions::NAVIGATION_LOCATIONS)->map(fn (string $location): array => [
                        'label' => trans("app.cms.location_{$location}"),
                        'value' => $location,
                    ])->all(),
                ],
                ['name' => 'parent_id', 'label' => trans('app.cms.parent_item'), 'type' => 'select', 'options' => $parentOptions],
                ['name' => 'cms_page_id', 'label' => trans('app.cms.linked_page'), 'type' => 'select', 'options' => $pageOptions, 'help' => trans('app.cms.linked_page_help')],
                ['name' => 'title_en', 'label' => trans('app.cms.navigation_title_en'), 'required' => true],
                ['name' => 'title_ar', 'label' => trans('app.cms.navigation_title_ar'), 'required' => true],
                ['name' => 'url', 'label' => trans('app.cms.custom_url'), 'help' => trans('app.cms.custom_url_help')],
                [
                    'name' => 'target',
                    'label' => trans('app.cms.open_link'),
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        ['label' => trans('app.cms.same_tab'), 'value' => '_self'],
                        ['label' => trans('app.cms.new_tab'), 'value' => '_blank'],
                    ],
                ],
                ['name' => 'sort_order', 'label' => trans('app.cms.order'), 'type' => 'number', 'min' => 0, 'required' => true],
                ['name' => 'is_visible', 'label' => trans('app.cms.publicly_visible'), 'type' => 'checkbox'],
            ], [
                trans('app.cms.navigation_identity') => [
                    'description' => trans('app.cms.navigation_identity_help'),
                    'fields' => ['title_en', 'title_ar', 'location', 'parent_id'],
                ],
                trans('app.cms.navigation_destination') => [
                    'description' => trans('app.cms.navigation_destination_help'),
                    'fields' => ['cms_page_id', 'url', 'target', 'sort_order', 'is_visible'],
                ],
            ]),
            'initialValues' => $initialValues,
        ];
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
