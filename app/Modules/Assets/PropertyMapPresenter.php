<?php

namespace App\Modules\Assets;

use App\Models\Asset;
use App\Modules\Assets\Presenters\PropertyMapAssetPresenter;
use App\Modules\Assets\Presenters\PropertyMapPayloadPresenter;
use App\Modules\Assets\Queries\PropertyMapActivityQuery;
use App\Modules\Assets\Queries\PropertyMapSourceQuery;
use App\Modules\Assets\Support\PropertyMapCoordinates;
use App\Modules\Assets\Support\PropertyMapHierarchy;
use Illuminate\Database\Eloquent\Builder;

class PropertyMapPresenter
{
    public function __construct(
        private readonly PropertyMapSourceQuery $source,
        private readonly PropertyMapHierarchy $hierarchy,
        private readonly PropertyMapActivityQuery $activity,
        private readonly PropertyMapCoordinates $coordinates,
        private readonly PropertyMapAssetPresenter $assets,
        private readonly PropertyMapPayloadPresenter $payload,
    ) {}

    /**
     * @param  Builder<Asset>  $assetQuery
     * @return array{
     *     assets:array<int, array<string, mixed>>,
     *     summary:array<string, mixed>,
     *     config:array<string, mixed>
     * }
     */
    public function forQuery(Builder $assetQuery): array
    {
        $candidates = $this->source->candidates($assetQuery);
        $nodes = $this->source->nodes($assetQuery);
        $descendants = $this->hierarchy->descendants($candidates, $nodes);
        $activity = $this->activity->forAssetIds(
            $this->hierarchy->scopedIds($descendants),
        );
        $bounds = $this->coordinates->bounds($candidates);
        $locale = app()->getLocale();
        $mappedAssets = $candidates
            ->map(fn (Asset $asset): array => $this->assets->present(
                $asset,
                $descendants->get($asset->id, collect([$asset->id])),
                $nodes,
                $activity['leases'],
                $activity['maintenance'],
                $locale,
                $bounds,
            ))
            ->values();

        return $this->payload->present($mappedAssets);
    }
}
