<?php

namespace App\Modules\PortfolioControl\Queries;

use App\Models\User;
use App\Modules\Dashboard\Queries\DashboardPropertyContextQuery;
use App\Modules\Dashboard\Queries\PropertyPerformanceDatasetQuery;
use App\Modules\PortfolioControl\Presenters\PortfolioControlSummaryPresenter;
use App\Modules\PortfolioControl\Support\PortfolioControlAccess;
use App\Modules\PortfolioControl\Support\PortfolioControlSorter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class PortfolioControlIndexQuery
{
    public function __construct(
        private PortfolioControlAccess $access,
        private DashboardPropertyContextQuery $context,
        private PropertyPerformanceDatasetQuery $performance,
        private PortfolioControlSorter $sorter,
        private PortfolioControlSummaryPresenter $summary,
    ) {}

    /**
     * @param  array{
     *     search:string,
     *     attention:string,
     *     portfolio_id:int|null,
     *     sort:string,
     *     per_page:int,
     *     page:int
     * }  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $this->access->ensureCanView($actor);
        $context = $this->context->forUser($actor, null);
        $all = collect($this->performance->forUser($actor, $context));
        $portfolioOptions = $this->portfolioOptions($all);
        $base = $this->filterBase($all, $filters);
        $counts = $this->counts($base);
        $filtered = $filters['attention'] === 'all'
            ? $base
            : $base->where('attention', $filters['attention'])->values();
        $sorted = $this->sorter->sort($filtered, $filters['sort']);
        $properties = new LengthAwarePaginator(
            $sorted->forPage($filters['page'], $filters['per_page'])->values(),
            $sorted->count(),
            $filters['per_page'],
            $filters['page'],
            [
                'path' => route('portfolio-control.index'),
                'query' => collect($filters)
                    ->except('page')
                    ->reject(fn (mixed $value): bool => $value === null || $value === '')
                    ->all(),
            ],
        );

        return [
            'filters' => $filters,
            'counts' => $counts,
            'summary' => $this->summary->present($filtered, $actor),
            'portfolioOptions' => $portfolioOptions,
            'properties' => $properties->toArray(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{search:string,portfolio_id:int|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function filterBase(Collection $rows, array $filters): Collection
    {
        $search = Str::lower($filters['search']);

        return $rows
            ->when(
                $filters['portfolio_id'] !== null,
                fn (Collection $items) => $items->where(
                    'portfolio_id',
                    $filters['portfolio_id'],
                ),
            )
            ->when($search !== '', fn (Collection $items) => $items->filter(
                fn (array $row): bool => str_contains(
                    Str::lower(implode(' ', array_map(static fn (mixed $value): string => (string) $value, [
                        $row['code'],
                        $row['title_en'],
                        $row['title_ar'],
                        $row['portfolio_code'],
                        $row['portfolio_name_en'],
                        $row['portfolio_name_ar'],
                    ]))),
                    $search,
                ),
            ))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array{key:string,count:int}>
     */
    private function counts(Collection $rows): array
    {
        return array_values(collect(['all', 'risk', 'watch', 'on_track'])
            ->map(fn (string $key): array => [
                'key' => $key,
                'count' => $key === 'all'
                    ? $rows->count()
                    : $rows->where('attention', $key)->count(),
            ])
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array{
     *     id:int,
     *     code:string,
     *     name_en:string,
     *     name_ar:string|null
     * }>
     */
    private function portfolioOptions(Collection $rows): array
    {
        $name = app()->isLocale('ar')
            ? 'portfolio_name_ar'
            : 'portfolio_name_en';

        return array_values($rows
            ->unique('portfolio_id')
            ->sortBy($name, SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn (array $row): array => [
                'id' => (int) $row['portfolio_id'],
                'code' => (string) $row['portfolio_code'],
                'name_en' => (string) $row['portfolio_name_en'],
                'name_ar' => is_string($row['portfolio_name_ar'])
                    ? $row['portfolio_name_ar']
                    : null,
            ])
            ->values()
            ->all());
    }
}
