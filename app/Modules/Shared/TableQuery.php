<?php

namespace App\Modules\Shared;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TableQuery
{
    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function filters(Request $request, array $defaults = []): array
    {
        $filters = [
            'search' => trim((string) $request->query('search', $defaults['search'] ?? '')),
            'status' => trim((string) $request->query('status', $defaults['status'] ?? 'all')),
            'portfolio_id' => $this->nullableInteger($request->query('portfolio_id')),
            'per_page' => $this->perPage($request),
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

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string|callable>  $columns
     * @return Builder<TModel>
     */
    public function search(Builder $query, string $search, array $columns): Builder
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

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function exact(Builder $query, array $filters, string $key, ?string $column = null): Builder
    {
        $value = $filters[$key] ?? null;

        return $value === null || $value === '' || $value === 'all'
            ? $query
            : $query->where($column ?? $key, $value);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $allowedSorts
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(
        Builder $query,
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
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $statuses
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    public function statusCounts(Builder $query, array $statuses, array $filters, string $column = 'status'): array
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

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null || $value === '' || $value === 'all' || ! ctype_digit((string) $value)
            ? null
            : (int) $value;
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
