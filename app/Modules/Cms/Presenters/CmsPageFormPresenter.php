<?php

namespace App\Modules\Cms\Presenters;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsOptions;
use App\Modules\Shared\ResourcePresenter;

class CmsPageFormPresenter
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?CmsPage $page = null): array
    {
        $this->access->ensureAdmin($actor);
        $fields = $this->resources->sectionFields([
            ['name' => 'slug', 'label' => trans('app.cms.page_slug'), 'help' => trans('app.cms.page_slug_help')],
            ['name' => 'title_en', 'label' => trans('app.cms.page_title_en'), 'required' => true],
            ['name' => 'title_ar', 'label' => trans('app.cms.page_title_ar'), 'required' => true],
            ['name' => 'excerpt_en', 'label' => trans('app.cms.excerpt_en'), 'type' => 'textarea'],
            ['name' => 'excerpt_ar', 'label' => trans('app.cms.excerpt_ar'), 'type' => 'textarea'],
            ['name' => 'seo_title_en', 'label' => trans('app.cms.seo_title_en')],
            ['name' => 'seo_title_ar', 'label' => trans('app.cms.seo_title_ar')],
            ['name' => 'seo_description_en', 'label' => trans('app.cms.seo_description_en'), 'type' => 'textarea'],
            ['name' => 'seo_description_ar', 'label' => trans('app.cms.seo_description_ar'), 'type' => 'textarea'],
            [
                'name' => 'status',
                'label' => trans('app.cms.status'),
                'type' => 'select',
                'required' => true,
                'options' => collect(CmsOptions::PAGE_STATUSES)->map(fn (string $status): array => [
                    'label' => trans("app.status.{$status}"),
                    'value' => $status,
                ])->all(),
            ],
            ['name' => 'is_homepage', 'label' => trans('app.cms.set_homepage'), 'type' => 'checkbox', 'help' => trans('app.cms.set_homepage_help')],
            ['name' => 'is_visible', 'label' => trans('app.cms.publicly_visible'), 'type' => 'checkbox'],
        ], [
            trans('app.cms.page_identity') => [
                'description' => trans('app.cms.page_identity_help'),
                'fields' => ['slug', 'title_en', 'title_ar', 'excerpt_en', 'excerpt_ar'],
            ],
            trans('app.cms.search_preview') => [
                'description' => trans('app.cms.search_preview_help'),
                'fields' => ['seo_title_en', 'seo_title_ar', 'seo_description_en', 'seo_description_ar'],
            ],
            trans('app.cms.publishing') => [
                'description' => trans('app.cms.publishing_help'),
                'fields' => ['status', 'is_homepage', 'is_visible'],
            ],
        ]);
        $initialValues = $page
            ? [
                'slug' => $page->slug,
                'title_en' => $page->title_en,
                'title_ar' => $page->title_ar,
                'excerpt_en' => $page->excerpt_en ?? '',
                'excerpt_ar' => $page->excerpt_ar ?? '',
                'seo_title_en' => $page->seo_title_en ?? '',
                'seo_title_ar' => $page->seo_title_ar ?? '',
                'seo_description_en' => $page->seo_description_en ?? '',
                'seo_description_ar' => $page->seo_description_ar ?? '',
                'status' => $page->status,
                'is_homepage' => (bool) $page->is_homepage,
                'is_visible' => (bool) $page->is_visible,
            ]
            : [
                'slug' => '',
                'title_en' => '',
                'title_ar' => '',
                'excerpt_en' => '',
                'excerpt_ar' => '',
                'seo_title_en' => '',
                'seo_title_ar' => '',
                'seo_description_en' => '',
                'seo_description_ar' => '',
                'status' => 'draft',
                'is_homepage' => false,
                'is_visible' => true,
            ];

        return [
            'title' => $page ? trans('app.cms.edit_page') : trans('app.cms.create_page'),
            'description' => $page
                ? trans('app.cms.edit_page_description')
                : trans('app.cms.create_page_description'),
            'backHref' => $page ? route('cms.pages.show', $page) : route('cms.index'),
            'backLabel' => $page ? trans('app.cms.builder') : trans('app.cms.website_control'),
            'action' => $page ? route('cms.pages.update', $page) : route('cms.pages.store'),
            'method' => $page ? 'put' : 'post',
            'submitLabel' => $page ? trans('app.cms.update_page') : trans('app.cms.create_page'),
            'fields' => $fields,
            'initialValues' => $initialValues,
        ];
    }
}
