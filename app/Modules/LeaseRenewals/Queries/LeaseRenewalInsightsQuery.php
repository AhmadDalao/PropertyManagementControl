<?php

namespace App\Modules\LeaseRenewals\Queries;

use App\Models\Lease;
use Illuminate\Database\Eloquent\Builder;

final readonly class LeaseRenewalInsightsQuery
{
    public function __construct(private LeaseRenewalDirectoryQuery $directory) {}

    /**
     * @param  Builder<Lease>  $query
     * @return array<string, int>
     */
    public function get(Builder $query): array
    {
        $attention = clone $query;
        $this->directory->applyQueue($attention, 'attention');
        $prepared = clone $query;
        $this->directory->applyQueue($prepared, 'prepared');
        $expired = clone $query;
        $this->directory->applyQueue($expired, 'expired');

        return [
            'action_required' => $attention->count(),
            'ending_30_days' => (clone $query)
                ->where('status', 'active')
                ->whereBetween('ends_at', [today(), today()->addDays(30)])
                ->count(),
            'renewals_prepared' => $prepared->count(),
            'expired_unresolved' => $expired->count(),
        ];
    }
}
