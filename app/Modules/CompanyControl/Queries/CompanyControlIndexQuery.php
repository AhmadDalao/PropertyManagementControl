<?php

namespace App\Modules\CompanyControl\Queries;

use App\Models\User;
use App\Modules\CompanyControl\Presenters\CompanyControlSummaryPresenter;
use App\Modules\CompanyControl\Support\CompanyControlAccess;
use App\Modules\CompanyControl\Support\CompanyControlSorter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class CompanyControlIndexQuery
{
    public function __construct(
        private CompanyControlAccess $access,
        private CompanyPortfolioFoundationQuery $foundation,
        private CompanyPortfolioOperationsQuery $operations,
        private CompanyControlSorter $sorter,
        private CompanyControlSummaryPresenter $summary,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $base = $this->base($actor, $filters);
        $rows = $this->filtered($base, $filters);
        $pageRows = new LengthAwarePaginator(
            $rows->forPage($filters['page'], $filters['per_page'])->values(),
            $rows->count(),
            $filters['per_page'],
            $filters['page'],
            [
                'path' => route('company-control.index'),
                'query' => collect($filters)
                    ->except('page')
                    ->reject(fn (mixed $value): bool => $value === null || $value === '')
                    ->all(),
            ],
        );

        return [
            'filters' => $filters,
            'counts' => $this->counts($base),
            'summary' => $this->summary->present($rows),
            'portfolios' => $pageRows->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function export(User $actor, array $filters): array
    {
        $rows = $this->filtered($this->base($actor, $filters), $filters);

        return [
            'filters' => $filters,
            'summary' => $this->summary->present($rows),
            'portfolios' => $rows->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $base
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function filtered(Collection $base, array $filters): Collection
    {
        $filtered = $filters['attention'] === 'all'
            ? $base
            : $base->where('attention', $filters['attention'])->values();

        return $this->sorter->sort(
            $filtered,
            (string) $filters['sort'],
            (string) $filters['direction'],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function base(User $actor, array $filters): Collection
    {
        $this->access->ensureSuperadmin($actor);
        $operations = $this->operations->get($actor);
        $search = Str::lower((string) $filters['search']);

        return $this->foundation->get()
            ->map(fn (array $row): array => $this->portfolioRow(
                $row,
                $operations->get($row['id'], $this->emptyOperations()),
            ))
            ->when(
                $filters['data_source'] === 'live',
                fn (Collection $rows) => $rows->where('is_showcase', false),
            )
            ->when(
                $filters['data_source'] === 'showcase',
                fn (Collection $rows) => $rows->where('is_showcase', true),
            )
            ->when(
                $filters['status'] !== 'all',
                fn (Collection $rows) => $rows->where('status', $filters['status']),
            )
            ->when($search !== '', fn (Collection $rows) => $rows->filter(
                fn (array $row): bool => str_contains(
                    Str::lower(implode(' ', array_filter([
                        $row['code'],
                        $row['name_en'],
                        $row['name_ar'],
                        $row['owner']['name'] ?? null,
                    ]))),
                    $search,
                ),
            ))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $foundation
     * @param  array<string, mixed>  $operations
     * @return array<string, mixed>
     */
    private function portfolioRow(array $foundation, array $operations): array
    {
        $row = [...$foundation, ...$operations];
        $hasArrears = $this->hasArrears($row['currency_totals']);
        $row['attention'] = $row['readiness']['status'] === 'blocked'
            || $row['risk_properties'] > 0
            || $hasArrears
            ? 'risk'
            : ($row['readiness']['status'] === 'attention'
                || $row['watch_properties'] > 0
                || $row['open_requests'] > 0
                || $row['expiring_leases'] > 0
                ? 'watch'
                : 'on_track');

        return $row;
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
            ->all());
    }

    /** @return array<string, mixed> */
    private function emptyOperations(): array
    {
        return [
            'properties' => 0,
            'risk_properties' => 0,
            'watch_properties' => 0,
            'rentable_units' => 0,
            'occupied_units' => 0,
            'occupancy_rate' => 0.0,
            'active_leases' => 0,
            'expiring_leases' => 0,
            'open_requests' => 0,
            'currency_totals' => [],
        ];
    }

    private function hasArrears(mixed $positions): bool
    {
        if (! is_array($positions)) {
            return false;
        }

        foreach ($positions as $position) {
            if (is_array($position) && (float) ($position['arrears'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
