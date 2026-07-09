<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait BuildsAdminTables
{
    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    protected function tableFilters(Request $request, array $defaults = []): array
    {
        $filters = [
            'search' => trim((string) $request->query('search', $defaults['search'] ?? '')),
            'status' => trim((string) $request->query('status', $defaults['status'] ?? 'all')),
            'portfolio_id' => $this->nullableInteger($request->query('portfolio_id')),
            'per_page' => $this->tablePerPage($request),
            'page' => max(1, (int) $request->query('page', $defaults['page'] ?? 1)),
            'sort' => trim((string) $request->query('sort', $defaults['sort'] ?? 'created_at')),
            'direction' => strtolower((string) $request->query('direction', $defaults['direction'] ?? 'desc')) === 'asc'
                ? 'asc'
                : 'desc',
        ];

        foreach ($defaults as $key => $default) {
            if (array_key_exists($key, $filters)) {
                continue;
            }

            $value = $request->query($key, $default);
            $filters[$key] = is_string($value) ? trim($value) : $value;
        }

        return $filters;
    }

    protected function applySearch(Builder $query, string $search, array $columns): Builder
    {
        if ($search === '') {
            return $query;
        }

        $like = "%{$search}%";

        return $query->where(function (Builder $query) use ($columns, $like, $search): void {
            foreach ($columns as $column) {
                if (is_callable($column)) {
                    $column($query, $search, $like);

                    continue;
                }

                $query->orWhere($column, 'like', $like);
            }
        });
    }

    protected function applyExactFilter(Builder $query, array $filters, string $key, ?string $column = null): Builder
    {
        $value = $filters[$key] ?? null;

        if ($value === null || $value === '' || $value === 'all') {
            return $query;
        }

        return $query->where($column ?? $key, $value);
    }

    protected function applyDateRange(Builder $query, array $filters, string $column): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * @param  array<int, string>  $allowedSorts
     */
    protected function paginateTable(
        Builder $query,
        Request $request,
        array $filters,
        array $allowedSorts,
        string $defaultSort = 'created_at'
    ): LengthAwarePaginator {
        $sort = in_array($filters['sort'], $allowedSorts, true) ? $filters['sort'] : $defaultSort;
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate((int) $filters['per_page'])
            ->withQueryString();
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    protected function statusCounts(Builder $query, array $statuses, array $filters, string $column = 'status'): array
    {
        $activeStatus = (string) ($filters['status'] ?? 'all');
        $counts = [[
            'label' => 'All',
            'value' => (clone $query)->count(),
            'filter' => ['status' => 'all'],
            'active' => $activeStatus === 'all',
        ]];

        foreach ($statuses as $status) {
            $counts[] = [
                'label' => str($status)->replace('_', ' ')->title()->toString(),
                'value' => (clone $query)->where($column, $status)->count(),
                'filter' => ['status' => $status],
                'active' => $activeStatus === $status,
            ];
        }

        return $counts;
    }

    protected function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return ctype_digit((string) $value) ? (int) $value : null;
    }

    private function tablePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
