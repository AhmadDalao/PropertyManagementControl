<?php

namespace App\Modules\PortfolioControl\Queries;

use App\Models\User;
use App\Modules\Dashboard\Queries\DashboardPropertyContextQuery;
use App\Modules\Dashboard\Queries\PropertyPerformanceDatasetQuery;
use App\Modules\PortfolioControl\Support\PortfolioControlAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class PortfolioControlIndexQuery
{
    public function __construct(
        private PortfolioControlAccess $access,
        private DashboardPropertyContextQuery $context,
        private PropertyPerformanceDatasetQuery $performance,
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
        $sorted = $this->sort($filtered, $filters['sort']);
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
            'summary' => $this->summary($filtered),
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
     * @return Collection<int, array<string, mixed>>
     */
    private function sort(Collection $rows, string $sort): Collection
    {
        $title = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return (match ($sort) {
            'arrears' => $rows->sortByDesc('arrears'),
            'occupancy' => $rows->sortBy('occupancy_rate'),
            'collection' => $rows->sortBy('collection_rate'),
            'net' => $rows->sortBy('net'),
            'name' => $rows->sortBy($title, SORT_NATURAL | SORT_FLAG_CASE),
            default => $rows->sortBy([
                ['attention_score', 'desc'],
                ['arrears', 'desc'],
                [$title, 'asc'],
            ]),
        })->values();
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
     * @return array<string, int|float|string|null>
     */
    private function summary(Collection $rows): array
    {
        $currencies = $rows->pluck('currency')->filter()->unique()->values();
        $rentable = (int) $rows->sum('rentable_units');
        $occupied = (int) $rows->sum('occupied_units');
        $scheduled = (float) $rows->sum('scheduled_due');
        $paid = (float) $rows->sum('scheduled_paid');

        return [
            'properties' => $rows->count(),
            'risk' => $rows->where('attention', 'risk')->count(),
            'occupancy_rate' => $rentable > 0
                ? round(($occupied / $rentable) * 100, 1)
                : 0,
            'collection_rate' => $scheduled > 0
                ? round(min(100, ($paid / $scheduled) * 100), 1)
                : 0,
            'arrears' => (float) $rows->sum('arrears'),
            'net' => (float) $rows->sum('net'),
            'currency' => $currencies->count() === 1
                ? (string) $currencies->first()
                : null,
            'currency_count' => $currencies->count(),
            'open_requests' => (int) $rows->sum('open_requests'),
            'expiring_leases' => (int) $rows->sum('expiring_leases'),
        ];
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
