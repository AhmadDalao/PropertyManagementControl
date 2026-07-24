<?php

namespace App\Modules\LeaseRenewals\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class LeaseRenewalSearch
{
    public function __construct(
        private TableQuery $tables,
        private MorphTypes $morphTypes,
    ) {}

    /** @param Builder<Lease> $query */
    public function apply(Builder $query, string $search): void
    {
        $this->tables->search($query, $search, [
            'code',
            'notes',
            fn (Builder $leases, string $term, string $like) => $leases->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like),
            ),
            fn (Builder $leases, string $term, string $like) => $leases->orWhere(function (Builder $assets) use ($like): void {
                $assets
                    ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
                    ->whereIn('leaseable_id', Asset::query()
                        ->select('id')
                        ->where(function (Builder $titles) use ($like): void {
                            $titles
                                ->where('title_en', 'like', $like)
                                ->orWhere('title_ar', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        }));
            }),
        ]);
    }
}
