<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PropertyMapSourceQuery
{
    public const MAX_MARKERS = 40;

    private const MAP_ASSET_TYPES = ['property', 'building', 'space'];

    /**
     * @param  Builder<Asset>  $assetQuery
     * @return Collection<int, Asset>
     */
    public function candidates(Builder $assetQuery): Collection
    {
        return (clone $assetQuery)
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNull('parent_id')
                            ->whereIn('asset_type', self::MAP_ASSET_TYPES);
                    })
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('meta_json', 'like', '%"latitude"%')
                            ->where('meta_json', 'like', '%"longitude"%');
                    })
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('meta_json', 'like', '%"x"%')
                            ->where('meta_json', 'like', '%"y"%');
                    });
            })
            ->with(['portfolio', 'stakeholders.user'])
            ->orderByRaw("CASE asset_type WHEN 'property' THEN 0 WHEN 'building' THEN 1 WHEN 'space' THEN 2 ELSE 3 END")
            ->orderBy('id')
            ->limit(self::MAX_MARKERS)
            ->get();
    }

    /**
     * @param  Builder<Asset>  $assetQuery
     * @return Collection<int, Asset>
     */
    public function nodes(Builder $assetQuery): Collection
    {
        return (clone $assetQuery)->get([
            'id',
            'parent_id',
            'portfolio_id',
            'asset_type',
            'rentable',
            'occupancy_status',
        ]);
    }
}
