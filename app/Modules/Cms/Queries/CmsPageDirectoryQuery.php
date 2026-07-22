<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Modules\Cms\Support\CmsOptions;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class CmsPageDirectoryQuery
{
    public function __construct(private readonly TableQuery $tables) {}

    /** @return array{pages:LengthAwarePaginator<int,CmsPage>,filters:array<string,mixed>,counts:array<int,array<string,mixed>>} */
    public function handle(Request $request): array
    {
        $filters = $this->filters($request);
        $base = CmsPage::query();
        $pages = $this->indexQuery(clone $base);
        $this->applyFilters($pages, $filters);

        return [
            'pages' => $this->tables->paginate($pages, $filters, [
                'created_at',
                'title_en',
                'slug',
                'status',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts($base, $filters),
        ];
    }

    /** @return Builder<CmsPage> */
    public function forExport(Request $request): Builder
    {
        $filters = $this->filters($request);
        $pages = $this->indexQuery(CmsPage::query());
        $this->applyFilters($pages, $filters);

        return $pages;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->tables->filters($request, ['status' => 'all']);
    }

    /**
     * @param  Builder<CmsPage>  $pages
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $pages, array $filters): void
    {
        $this->tables->exact($pages, $filters, 'status');
        $this->tables->search($pages, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'slug',
            'excerpt_en',
            'excerpt_ar',
        ]);
    }

    /**
     * @param  Builder<CmsPage>  $query
     * @return Builder<CmsPage>
     */
    private function indexQuery(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'slug',
                'title_en',
                'title_ar',
                'excerpt_en',
                'excerpt_ar',
                'status',
                'is_homepage',
                'is_visible',
                'published_at',
                'created_at',
            ])
            ->withCount('pageSections');
    }

    /**
     * @param  Builder<CmsPage>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function statusCounts(Builder $query, array $filters): array
    {
        return collect($this->tables->statusCounts($query, CmsOptions::PAGE_STATUSES, $filters))
            ->map(function (array $count): array {
                $status = (string) data_get($count, 'filter.status', 'all');
                $count['label'] = $status === 'all'
                    ? trans('app.cms.all_pages')
                    : trans("app.status.{$status}");

                return $count;
            })
            ->all();
    }
}
