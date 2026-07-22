<?php

namespace App\Modules\Cms\Presenters;

use App\Modules\Cms\Support\CmsOptions;
use App\Modules\Shared\ResourcePresenter;

final class NavigationFormFieldsPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /**
     * @param  array{parents:array<int,array{label:string,value:int|string}>,pages:array<int,array{label:string,value:int|string}>}  $options
     * @return array<int, array<string, mixed>>
     */
    public function present(array $options): array
    {
        return $this->resources->sectionFields([
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
            ['name' => 'parent_id', 'label' => trans('app.cms.parent_item'), 'type' => 'select', 'options' => $options['parents']],
            ['name' => 'cms_page_id', 'label' => trans('app.cms.linked_page'), 'type' => 'select', 'options' => $options['pages'], 'help' => trans('app.cms.linked_page_help')],
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
        ]);
    }
}
