<?php

namespace App\Modules\PortfolioControl\Support;

use Illuminate\Support\Collection;

final readonly class PortfolioControlSorter
{
    public function __construct(
        private PortfolioControlCurrencyPositions $positions,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function sort(Collection $rows, string $sort): Collection
    {
        $title = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return (match ($sort) {
            'arrears' => $rows->sortByDesc(
                fn (array $row): int => $this->positions->hasArrears($row) ? 1 : 0,
            ),
            'occupancy' => $rows->sortBy('occupancy_rate'),
            'collection' => $rows->sortBy(
                fn (array $row): float => $this->positions->minimumCollectionRate($row),
            ),
            'net' => $rows->sortByDesc(
                fn (array $row): int => $this->positions->hasNegativeNet($row) ? 1 : 0,
            ),
            'name' => $rows->sortBy($title, SORT_NATURAL | SORT_FLAG_CASE),
            default => $rows->sortBy([
                ['attention_score', 'desc'],
                [$title, 'asc'],
            ]),
        })->values();
    }
}
