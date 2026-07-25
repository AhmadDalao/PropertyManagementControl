<?php

namespace App\Modules\LeaseMoveOuts\Queries;

use App\Models\LeaseMoveOut;
use Illuminate\Database\Eloquent\Builder;

final readonly class LeaseMoveOutInsightsQuery
{
    public function __construct(private LeaseMoveOutDirectoryQuery $directory) {}

    /**
     * @param  Builder<LeaseMoveOut>  $query
     * @return array<string, int>
     */
    public function get(Builder $query): array
    {
        $attention = clone $query;
        $this->directory->applyQueue($attention, 'attention');
        $ready = clone $query;
        $this->directory->applyQueue($ready, 'ready');

        return [
            'planned' => (clone $query)->where('status', 'planned')->count(),
            'attention' => $attention->count(),
            'ready' => $ready->count(),
            'completed_30_days' => (clone $query)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }
}
