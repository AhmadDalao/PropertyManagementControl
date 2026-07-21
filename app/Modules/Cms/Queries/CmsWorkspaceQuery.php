<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsOptions;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CmsWorkspaceQuery
{
    private const SECTION_LIMIT = 60;

    private const NAVIGATION_LIMIT = 60;

    public function __construct(
        private readonly CmsAccess $access,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureAdmin($actor);
        $view = (string) $request->query('view', 'pages');
        $view = in_array($view, CmsOptions::WORKSPACE_VIEWS, true) ? $view : 'pages';
        $filters = $this->filters($request);
        $basePages = CmsPage::query();
        $pages = $this->pageIndexQuery(clone $basePages);
        $this->applyPageFilters($pages, $filters);
        $sectionCount = CmsSection::query()->count();
        $navigationCount = NavigationItem::query()->count();

        return [
            'view' => $view,
            'pages' => $this->tables->paginate($pages, $filters, [
                'created_at',
                'title_en',
                'slug',
                'status',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts(clone $basePages, $filters),
            'workspaceStats' => [
                'pages' => (clone $basePages)->count(),
                'published' => (clone $basePages)->where('status', 'published')->count(),
                'sections' => $sectionCount,
                'active_sections' => CmsSection::query()->where('status', 'active')->count(),
                'navigation' => $navigationCount,
                'visible_navigation' => NavigationItem::query()->where('is_visible', true)->count(),
            ],
            'sections' => CmsSection::query()
                ->select(['id', 'section_type', 'name_en', 'name_ar', 'status', 'created_at'])
                ->withCount('pageSections')
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'inactive' THEN 1 ELSE 2 END")
                ->orderBy('name_en')
                ->limit(self::SECTION_LIMIT)
                ->get(),
            'sectionLimitReached' => $sectionCount > self::SECTION_LIMIT,
            'navigationItems' => NavigationItem::query()
                ->select(['id', 'parent_id', 'cms_page_id', 'location', 'title_en', 'title_ar', 'url', 'target', 'sort_order', 'is_visible'])
                ->with([
                    'children:id,parent_id,cms_page_id,location,title_en,title_ar,url,target,sort_order,is_visible',
                    'page:id,title_en,title_ar,slug,status,is_visible',
                ])
                ->whereNull('parent_id')
                ->orderBy('location')
                ->orderBy('sort_order')
                ->limit(self::NAVIGATION_LIMIT)
                ->get(),
            'navigationLimitReached' => $navigationCount > self::NAVIGATION_LIMIT,
        ];
    }

    /** @return Builder<CmsPage> */
    public function forExport(Request $request, User $actor): Builder
    {
        $this->access->ensureAdmin($actor);
        $filters = $this->filters($request);
        $pages = $this->pageIndexQuery(CmsPage::query());
        $this->applyPageFilters($pages, $filters);

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
    private function applyPageFilters(Builder $pages, array $filters): void
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
    private function pageIndexQuery(Builder $query): Builder
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
