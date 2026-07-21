<?php

namespace App\Modules\Wording\Queries;

use App\Modules\Wording\Presenters\WordingEntryCatalog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WordingIndexQuery
{
    public function __construct(private readonly WordingEntryCatalog $catalog) {}

    /**
     * @param  array{search:string,group:string,state:string,per_page:int,page:int,content_module:string}  $filters
     * @return array<string, mixed>
     */
    public function present(array $filters): array
    {
        $allEntries = collect($this->catalog->entries());
        $filteredEntries = $this->filter($allEntries, $filters);
        $entries = new LengthAwarePaginator(
            $filteredEntries->forPage($filters['page'], $filters['per_page'])->values(),
            $filteredEntries->count(),
            $filters['per_page'],
            $filters['page'],
            [
                'path' => route('wording.index'),
                'query' => $this->queryString($filters),
            ],
        );

        return [
            'entries' => $entries,
            'groups' => $allEntries->pluck('group')->unique()->values(),
            'customizedCount' => $allEntries->where('customized', true)->count(),
            'totalLabels' => $allEntries->count(),
            'filters' => [
                'search' => $filters['search'],
                'group' => $filters['group'],
                'state' => $filters['state'],
                'perPage' => $filters['per_page'],
                'contentModule' => $filters['content_module'],
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, bool|string>>  $entries
     * @param  array{search:string,group:string,state:string,per_page:int,page:int,content_module:string}  $filters
     * @return Collection<int, array<string, bool|string>>
     */
    private function filter(Collection $entries, array $filters): Collection
    {
        return $entries
            ->when(
                $filters['group'] !== 'all',
                fn (Collection $items) => $items->where('group', $filters['group']),
            )
            ->when(
                $filters['state'] === 'customized',
                fn (Collection $items) => $items->where('customized', true),
            )
            ->when(
                $filters['state'] === 'default',
                fn (Collection $items) => $items->where('customized', false),
            )
            ->when($filters['search'] !== '', function (Collection $items) use ($filters) {
                $needle = mb_strtolower($filters['search']);

                return $items->filter(fn (array $entry): bool => collect([
                    $entry['group'],
                    $entry['key'],
                    $entry['english'],
                    $entry['arabic'],
                ])->contains(fn (mixed $value): bool => str_contains(
                    mb_strtolower((string) $value),
                    $needle,
                )));
            })
            ->values();
    }

    /**
     * @param  array{search:string,group:string,state:string,per_page:int,page:int,content_module:string}  $filters
     * @return array<string, int|string>
     */
    private function queryString(array $filters): array
    {
        return [
            'search' => $filters['search'],
            'group' => $filters['group'],
            'state' => $filters['state'],
            'per_page' => $filters['per_page'],
            'content_module' => $filters['content_module'],
        ];
    }
}
