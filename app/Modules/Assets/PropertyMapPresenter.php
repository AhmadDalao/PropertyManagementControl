<?php

namespace App\Modules\Assets;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PropertyMapPresenter
{
    private const MAP_ASSET_TYPES = ['property', 'building', 'space'];

    private const MAX_MARKERS = 40;

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
        $locale = app()->getLocale();
        $titleColumn = $locale === 'ar' ? 'title_ar' : 'title_en';
        $candidates = (clone $assetQuery)
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
            ->orderBy($titleColumn)
            ->limit(self::MAX_MARKERS)
            ->get();

        $nodes = (clone $assetQuery)
            ->get([
                'id',
                'parent_id',
                'portfolio_id',
                'asset_type',
                'rentable',
                'occupancy_status',
            ]);
        $nodeCollection = $this->assetCollection($nodes);
        $children = $nodeCollection->groupBy(fn (Asset $asset): int => (int) ($asset->parent_id ?? 0));
        $candidateCollection = $this->assetCollection($candidates);
        $candidateDescendants = $candidateCollection->mapWithKeys(
            fn (Asset $asset): array => [$asset->id => $this->descendantIds($asset->id, $children)],
        );
        $scopedAssetIds = $candidateDescendants
            ->flatten()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $leaseCounts = $this->activeLeaseCounts($scopedAssetIds);
        $maintenanceCounts = $this->openMaintenanceCounts($scopedAssetIds);
        $bounds = $this->coordinateBounds($candidateCollection);
        $mappedAssets = $candidateCollection
            ->map(fn (Asset $asset): array => $this->mapAsset(
                $asset,
                collect($candidateDescendants->get($asset->id, [$asset->id])),
                $nodeCollection,
                $leaseCounts,
                $maintenanceCounts,
                $locale,
                $bounds,
            ))
            ->values();
        $ready = $mappedAssets->where('map_ready', true)->count();

        return [
            'assets' => $mappedAssets->all(),
            'summary' => [
                'mapped' => $mappedAssets->where('has_coordinates', true)->count(),
                'total' => $mappedAssets->count(),
                'ready' => $ready,
                'needs_position' => $mappedAssets->where('has_coordinates', false)->count(),
                'needs_identity' => $mappedAssets->where('has_identity', false)->count(),
                'coverage_percent' => $mappedAssets->isNotEmpty()
                    ? round(($ready / $mappedAssets->count()) * 100, 1)
                    : 0,
                'zones' => $mappedAssets->pluck('zone')->filter()->unique()->sort()->values()->all(),
                'payload_limit' => self::MAX_MARKERS,
            ],
            'config' => [
                'tile_url' => config('property-map.tile_url'),
                'attribution' => config('property-map.attribution'),
                'default_center' => config('property-map.default_center'),
                'default_zoom' => config('property-map.default_zoom'),
                'directory_page_size' => 12,
            ],
        ];
    }

    /**
     * @param  Collection<int, Collection<int, Asset>>  $children
     * @return array<int, int>
     */
    private function descendantIds(int $assetId, Collection $children): array
    {
        $ids = [$assetId];
        $queue = [$assetId];

        while ($queue !== []) {
            $parentId = array_shift($queue);

            foreach ($children->get($parentId, collect()) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, int>  $assetIds
     * @return Collection<int, int>
     */
    private function activeLeaseCounts(Collection $assetIds): Collection
    {
        if ($assetIds->isEmpty()) {
            return collect();
        }

        return Lease::query()
            ->whereIn('leaseable_type', array_unique([Asset::class, (new Asset)->getMorphClass(), 'asset']))
            ->whereIn('leaseable_id', $assetIds)
            ->where('status', 'active')
            ->selectRaw('leaseable_id, COUNT(*) as aggregate')
            ->groupBy('leaseable_id')
            ->pluck('aggregate', 'leaseable_id')
            ->map(fn (mixed $count): int => (int) $count);
    }

    /**
     * @param  Collection<int, int>  $assetIds
     * @return Collection<int, int>
     */
    private function openMaintenanceCounts(Collection $assetIds): Collection
    {
        if ($assetIds->isEmpty()) {
            return collect();
        }

        return MaintenanceRequest::query()
            ->whereIn('asset_id', $assetIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->selectRaw('asset_id, COUNT(*) as aggregate')
            ->groupBy('asset_id')
            ->pluck('aggregate', 'asset_id')
            ->map(fn (mixed $count): int => (int) $count);
    }

    /**
     * @param  Collection<int, int>  $descendantIds
     * @param  Collection<int, Asset>  $nodes
     * @param  Collection<int, int>  $leaseCounts
     * @param  Collection<int, int>  $maintenanceCounts
     * @param  array{min_latitude:float,max_latitude:float,min_longitude:float,max_longitude:float}|null  $bounds
     * @return array<string, mixed>
     */
    private function mapAsset(
        Asset $asset,
        Collection $descendantIds,
        Collection $nodes,
        Collection $leaseCounts,
        Collection $maintenanceCounts,
        string $locale,
        ?array $bounds,
    ): array {
        $map = $this->mapMetadata($asset);
        $latitude = is_numeric($map['latitude'] ?? null) ? (float) $map['latitude'] : null;
        $longitude = is_numeric($map['longitude'] ?? null) ? (float) $map['longitude'] : null;
        $zone = $this->localizedMapValue($map, 'zone', $locale);
        $landNumber = $this->mapValue($map, 'land_number');
        $descendantNodes = $nodes->whereIn('id', $descendantIds);
        $rentableUnits = $descendantNodes
            ->where('rentable', true)
            ->whereIn('asset_type', ['unit', 'space'])
            ->count();
        $activeLeases = $descendantIds->sum(fn (int $id): int => (int) $leaseCounts->get($id, 0));
        $openRequests = $descendantIds->sum(fn (int $id): int => (int) $maintenanceCounts->get($id, 0));
        $owner = $asset->stakeholders->firstWhere('relationship_type', 'owner');
        $manager = $asset->stakeholders->firstWhere('relationship_type', 'manager');
        $occupancy = $this->occupancy($asset, $rentableUnits, $activeLeases);
        $hasCoordinates = $latitude !== null && $longitude !== null;
        $hasIdentity = $zone !== null && $landNumber !== null;

        return [
            'id' => $asset->id,
            'title' => $this->localized($asset->title_en, $asset->title_ar, $locale),
            'code' => $asset->code,
            'portfolio' => $this->localizedModel(
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
            'x' => is_numeric($map['x'] ?? null)
                ? (float) $map['x']
                : $this->coordinatePercent($longitude, $bounds, 'longitude'),
            'y' => is_numeric($map['y'] ?? null)
                ? (float) $map['y']
                : $this->coordinatePercent($latitude, $bounds, 'latitude', true),
            'has_coordinates' => $hasCoordinates,
            'has_identity' => $hasIdentity,
            'map_ready' => $hasCoordinates && $hasIdentity,
            'href' => route('assets.show', $asset),
            'edit_href' => route('assets.edit', $asset),
            'children_count' => max(0, $descendantIds->count() - 1),
            'rentable_children_count' => $rentableUnits,
            'active_leases_count' => $activeLeases,
            'open_requests_count' => $openRequests,
            'owner' => $this->stakeholderName($owner),
            'manager' => $this->stakeholderName($manager),
            'is_showcase' => $asset->getIsShowcaseAttribute(),
        ];
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function localizedMapValue(array $map, string $key, string $locale): ?string
    {
        return $this->mapValue($map, "{$key}_{$locale}")
            ?? $this->mapValue($map, $key)
            ?? $this->mapValue($map, $locale === 'ar' ? "{$key}_en" : "{$key}_ar");
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function mapValue(array $map, string $key): ?string
    {
        $value = trim((string) ($map[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    private function occupancy(Asset $asset, int $rentableUnits, int $activeLeases): string
    {
        if ($rentableUnits === 0) {
            return $asset->occupancy_status;
        }

        if ($activeLeases === 0) {
            return 'vacant';
        }

        return $activeLeases >= $rentableUnits ? 'occupied' : 'partially_occupied';
    }

    private function localized(?string $english, ?string $arabic, string $locale): ?string
    {
        return $locale === 'ar'
            ? ($arabic ?: $english)
            : ($english ?: $arabic);
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array{min_latitude:float,max_latitude:float,min_longitude:float,max_longitude:float}|null
     */
    private function coordinateBounds(Collection $assets): ?array
    {
        $coordinates = $assets
            ->map(function (Asset $asset): ?array {
                $map = $this->mapMetadata($asset);

                if (! is_numeric($map['latitude'] ?? null) || ! is_numeric($map['longitude'] ?? null)) {
                    return null;
                }

                return [
                    'latitude' => (float) $map['latitude'],
                    'longitude' => (float) $map['longitude'],
                ];
            })
            ->filter()
            ->values();

        if ($coordinates->isEmpty()) {
            return null;
        }

        return [
            'min_latitude' => (float) $coordinates->min('latitude'),
            'max_latitude' => (float) $coordinates->max('latitude'),
            'min_longitude' => (float) $coordinates->min('longitude'),
            'max_longitude' => (float) $coordinates->max('longitude'),
        ];
    }

    /**
     * @param  array<string, float>|null  $bounds
     */
    private function coordinatePercent(?float $value, ?array $bounds, string $axis, bool $inverse = false): float
    {
        if ($value === null || $bounds === null) {
            return 50.0;
        }

        $minimum = $bounds["min_{$axis}"];
        $maximum = $bounds["max_{$axis}"];
        $range = $maximum - $minimum;
        $ratio = $range > 0 ? ($value - $minimum) / $range : 0.5;

        if ($inverse) {
            $ratio = 1 - $ratio;
        }

        return round(10 + ($ratio * 80), 4);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMetadata(Asset $asset): array
    {
        $metadata = $asset->getAttribute('meta_json');

        if (! is_array($metadata)) {
            return [];
        }

        $map = $metadata['map'] ?? null;

        return is_array($map) ? $map : [];
    }

    private function localizedModel(
        mixed $model,
        string $englishAttribute,
        string $arabicAttribute,
        string $locale,
    ): ?string {
        if (! $model instanceof Model) {
            return null;
        }

        $english = $model->getAttribute($englishAttribute);
        $arabic = $model->getAttribute($arabicAttribute);

        return $this->localized(
            is_string($english) ? $english : null,
            is_string($arabic) ? $arabic : null,
            $locale,
        );
    }

    private function stakeholderName(mixed $stakeholder): ?string
    {
        if (! $stakeholder instanceof Model) {
            return null;
        }

        $user = $stakeholder->getRelation('user');
        $name = $user instanceof Model ? $user->getAttribute('name') : null;

        return is_string($name) ? $name : null;
    }

    /**
     * @param  iterable<mixed>  $assets
     * @return Collection<int, Asset>
     */
    private function assetCollection(iterable $assets): Collection
    {
        return collect($assets)
            ->filter(fn (mixed $asset): bool => $asset instanceof Asset)
            ->values();
    }
}
