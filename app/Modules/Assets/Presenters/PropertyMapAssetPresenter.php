<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Modules\Assets\Support\PropertyMapCoordinates;
use App\Modules\Assets\Support\PropertyMapLocalization;
use Illuminate\Support\Collection;

class PropertyMapAssetPresenter
{
    public function __construct(
        private readonly PropertyMapCoordinates $coordinates,
        private readonly PropertyMapLocalization $localization,
    ) {}

    /**
     * @param  Collection<int, int>  $descendantIds
     * @param  Collection<int, Asset>  $nodes
     * @param  Collection<int, int>  $leaseCounts
     * @param  Collection<int, int>  $maintenanceCounts
     * @param  array{min_latitude:float,max_latitude:float,min_longitude:float,max_longitude:float}|null  $bounds
     * @return array<string, mixed>
     */
    public function present(
        Asset $asset,
        Collection $descendantIds,
        Collection $nodes,
        Collection $leaseCounts,
        Collection $maintenanceCounts,
        string $locale,
        ?array $bounds,
    ): array {
        $map = $this->coordinates->metadata($asset);
        $latitude = $this->coordinates->number($map, 'latitude');
        $longitude = $this->coordinates->number($map, 'longitude');
        $zone = $this->localization->mapValue($map, 'zone', $locale);
        $landNumber = $this->localization->value($map, 'land_number');
        $descendantNodes = $nodes->whereIn('id', $descendantIds);
        $rentableUnits = $descendantNodes
            ->where('rentable', true)
            ->whereIn('asset_type', ['unit', 'space'])
            ->count();
        $activeLeases = $descendantIds->sum(
            fn (int $id): int => (int) $leaseCounts->get($id, 0),
        );
        $openRequests = $descendantIds->sum(
            fn (int $id): int => (int) $maintenanceCounts->get($id, 0),
        );
        $owner = $asset->currentStakeholders->firstWhere('relationship_type', 'owner');
        $manager = $asset->currentStakeholders->firstWhere('relationship_type', 'manager');
        $occupancy = $this->occupancy($asset, $rentableUnits, $activeLeases);
        $hasCoordinates = $latitude !== null && $longitude !== null;
        $hasIdentity = $zone !== null && $landNumber !== null;
        $x = $this->coordinates->number($map, 'x');
        $y = $this->coordinates->number($map, 'y');

        return [
            'id' => $asset->id,
            'title' => $this->localization->text(
                $asset->title_en,
                $asset->title_ar,
                $locale,
            ),
            'code' => $asset->code,
            'portfolio' => $this->localization->model(
                $asset->getRelation('portfolio'),
                'name_en',
                'name_ar',
                $locale,
            ),
            'asset_type' => $asset->asset_type,
            'usage_type' => $asset->usage_type,
            'status' => $asset->status,
            'occupancy_status' => $occupancy,
            'valuation_amount' => (float) $asset->valuation_amount,
            'currency' => $asset->currency,
            'address' => $locale === 'ar'
                ? ($asset->address_ar ?: $asset->address)
                : ($asset->address ?: $asset->address_ar),
            'zone' => $zone,
            'land_number' => $landNumber,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'x' => $x ?? $this->coordinates->percent(
                $longitude,
                $bounds,
                'longitude',
            ),
            'y' => $y ?? $this->coordinates->percent(
                $latitude,
                $bounds,
                'latitude',
                true,
            ),
            'has_coordinates' => $hasCoordinates,
            'has_identity' => $hasIdentity,
            'map_ready' => $hasCoordinates && $hasIdentity,
            'href' => route('assets.show', $asset),
            'edit_href' => route('assets.edit', $asset),
            'children_count' => max(0, $descendantIds->count() - 1),
            'rentable_children_count' => $rentableUnits,
            'active_leases_count' => $activeLeases,
            'open_requests_count' => $openRequests,
            'owner' => $this->localization->stakeholderName($owner),
            'manager' => $this->localization->stakeholderName($manager),
            'is_showcase' => $asset->getIsShowcaseAttribute(),
        ];
    }

    private function occupancy(
        Asset $asset,
        int $rentableUnits,
        int $activeLeases,
    ): string {
        if ($rentableUnits === 0) {
            return $asset->occupancy_status;
        }

        if ($activeLeases === 0) {
            return 'vacant';
        }

        return $activeLeases >= $rentableUnits
            ? 'occupied'
            : 'partially_occupied';
    }
}
