<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\TableQuery;

class CmsPageSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $actor->hasRole('superadmin')) {
            return [];
        }

        $pages = CmsPage::query();
        $this->tables->search($pages, $query, ['title_en', 'title_ar', 'slug']);

        return $pages
            ->limit(4)
            ->get()
            ->map(fn (CmsPage $page): array => $this->results->result(
                trans('app.search.cms_pages'),
                $this->results->localized($page->title_en, $page->title_ar),
                $page->slug,
                $this->results->status($page->status),
                route('cms.pages.show', $page),
            ))
            ->all();
    }
}
