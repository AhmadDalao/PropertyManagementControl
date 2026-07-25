<?php

namespace App\Modules\LeaseMoveOuts\Queries;

use App\Models\Asset;
use App\Models\LeaseMoveOut;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class LeaseMoveOutSearch
{
    public function __construct(
        private TableQuery $tables,
        private MorphTypes $morphTypes,
    ) {}

    /** @param Builder<LeaseMoveOut> $query */
    public function apply(Builder $query, string $search): void
    {
        $this->tables->search($query, $search, [
            'reason',
            'notes',
            fn (Builder $moveOuts, string $term, string $like) => $moveOuts
                ->orWhereHas('lease', fn (Builder $leases) => $leases
                    ->where('code', 'like', $like)
                    ->orWhereHas('tenantProfile.user', fn (Builder $users) => $users
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like))
                    ->orWhere(function (Builder $assets) use ($like): void {
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
                    })),
        ]);
    }
}
