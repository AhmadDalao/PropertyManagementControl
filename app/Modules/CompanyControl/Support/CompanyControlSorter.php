<?php

namespace App\Modules\CompanyControl\Support;

use Illuminate\Support\Collection;

final class CompanyControlSorter
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function sort(Collection $rows, string $sort, string $direction): Collection
    {
        $value = match ($sort) {
            'valuation' => fn (array $row): float => $this->sum($row['valuation_totals'], 'amount'),
            'arrears' => fn (array $row): float => $this->sum($row['currency_totals'], 'arrears'),
            'occupancy' => fn (array $row): float => (float) $row['occupancy_rate'],
            'collection' => fn (array $row): float => $this->weightedCollection($row['currency_totals']),
            'net' => fn (array $row): float => $this->sum($row['currency_totals'], 'net'),
            'name' => fn (array $row): string => mb_strtolower((string) (
                app()->isLocale('ar') ? $row['name_ar'] : $row['name_en']
            )),
            default => fn (array $row): int => match ($row['attention']) {
                'risk' => 3,
                'watch' => 2,
                default => 1,
            },
        };

        return ($direction === 'asc' ? $rows->sortBy($value) : $rows->sortByDesc($value))
            ->values();
    }

    /** @param array<int, array<string, mixed>> $positions */
    private function sum(array $positions, string $field): float
    {
        return (float) collect($positions)->sum($field);
    }

    /** @param array<int, array<string, mixed>> $positions */
    private function weightedCollection(array $positions): float
    {
        $due = $this->sum($positions, 'scheduled_due');

        return $due > 0
            ? ($this->sum($positions, 'scheduled_paid') / $due) * 100
            : 0.0;
    }
}
