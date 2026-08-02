<?php

namespace App\Modules\Reports\Support;

use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\RentCollection\Queries\RentCollectionDirectoryQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class ArrearsAgingScope
{
    public function __construct(
        private RentCollectionDirectoryQuery $directory,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<LeaseInstallment>
     */
    public function query(User $actor, array $filters): Builder
    {
        $query = $this->directory->base($actor);
        $this->directory->apply($query, [
            ...$filters,
            'status' => 'overdue',
            'line_type' => 'all',
            'follow_up' => 'all',
            'date_from' => '',
            'date_to' => '',
        ], $actor);

        return $query;
    }

    /** @param Builder<LeaseInstallment> $query */
    public function applyBucket(Builder $query, string $bucket): void
    {
        $day30 = today()->subDays(30)->toDateString();
        $day60 = today()->subDays(60)->toDateString();
        $day90 = today()->subDays(90)->toDateString();

        match ($bucket) {
            'days_1_30' => $query->whereDate('due_date', '>=', $day30),
            'days_31_60' => $query
                ->whereDate('due_date', '<', $day30)
                ->whereDate('due_date', '>=', $day60),
            'days_61_90' => $query
                ->whereDate('due_date', '<', $day60)
                ->whereDate('due_date', '>=', $day90),
            'over_90' => $query->whereDate('due_date', '<', $day90),
            default => null,
        };
    }
}
