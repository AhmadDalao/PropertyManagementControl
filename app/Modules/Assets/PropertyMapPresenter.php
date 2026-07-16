<?php

namespace App\Modules\Assets;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class PropertyMapPresenter
{
    /**
     * These are shown by default because they represent land/property-level records.
     */
    private const MAP_ASSET_TYPES = ['property', 'building', 'space'];

    /**
     * @return array{assets:array<int, array<string, mixed>>,summary:array<string, mixed>}
     */
    public function forQuery(Builder $assetQuery): array
    {
        $allAssets = (clone $assetQuery)
            ->with(['portfolio', 'stakeholders.user'])
            ->withCount([
                'children',
                'children as rentable_children_count' => fn (Builder $query) => $query->where('rentable', true),
                'leases as active_leases_count' => fn (Builder $query) => $query->where('status', 'active'),
                'maintenanceRequests as open_requests_count' => fn (Builder $query) => $query->whereIn('status', ['open', 'in_progress']),
            ])
            ->orderByRaw("CASE asset_type WHEN 'property' THEN 0 WHEN 'building' THEN 1 WHEN 'space' THEN 2 ELSE 3 END")
            ->orderBy('title_en')
            ->get();

        $assets = $allAssets
            ->filter(fn (Asset $asset) => $this->isMapCandidate($asset))
            ->values();

        if ($assets->isEmpty()) {
            $assets = $allAssets->values();
        }

        $bounds = $this->coordinateBounds($assets);
        $mappedAssets = $assets
            ->values()
            ->map(fn (Asset $asset, int $index) => $this->propertyMapAsset($asset, $index, $bounds))
            ->all();

        return [
            'assets' => $mappedAssets,
            'summary' => [
                'mapped' => collect($mappedAssets)->filter(fn (array $asset) => $asset['has_coordinates'])->count(),
                'total' => count($mappedAssets),
                'zones' => collect($mappedAssets)->pluck('zone')->filter()->unique()->values()->all(),
            ],
        ];
    }

    private function isMapCandidate(Asset $asset): bool
    {
        if (in_array($asset->asset_type, self::MAP_ASSET_TYPES, true)) {
            return true;
        }

        $map = is_array($asset->meta_json['map'] ?? null) ? $asset->meta_json['map'] : [];

        return collect(['zone', 'land_number', 'latitude', 'longitude', 'x', 'y'])
            ->contains(fn (string $key) => isset($map[$key]) && $map[$key] !== '');
    }

    /**
     * @return array<string, float>|null
     */
    private function coordinateBounds($assets): ?array
    {
        $coordinates = $assets
            ->map(function (Asset $asset): ?array {
                $map = is_array($asset->meta_json['map'] ?? null) ? $asset->meta_json['map'] : [];

                if (! isset($map['latitude'], $map['longitude']) || ! is_numeric($map['latitude']) || ! is_numeric($map['longitude'])) {
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
     * @return array<string, mixed>
     */
    private function propertyMapAsset(Asset $asset, int $index, ?array $bounds): array
    {
        $map = is_array($asset->meta_json['map'] ?? null) ? $asset->meta_json['map'] : [];
        $coordinates = $this->coordinates($map);
        $fallbackX = 14 + (($index * 23) % 72);
        $fallbackY = 18 + (($index * 31) % 62);
        $owner = $asset->stakeholders->firstWhere('relationship_type', 'owner');
        $manager = $asset->stakeholders->firstWhere('relationship_type', 'manager');

        return [
            'id' => $asset->id,
            'title' => $asset->title_en,
            'code' => $asset->code,
            'portfolio' => $asset->portfolio?->name_en,
            'asset_type' => $asset->asset_type,
            'usage_type' => $asset->usage_type,
            'status' => $asset->status,
            'occupancy_status' => $asset->occupancy_status,
            'valuation_amount' => (float) $asset->valuation_amount,
            'currency' => $asset->currency,
            'address' => $asset->address,
            'zone' => $map['zone'] ?? 'Zone '.chr(65 + ($index % 6)),
            'land_number' => $map['land_number'] ?? $asset->unit_label ?? $asset->code,
            'latitude' => $coordinates['latitude'] ?? null,
            'longitude' => $coordinates['longitude'] ?? null,
            'x' => $this->mapPercent($map['x'] ?? null, $this->coordinateX($coordinates, $bounds) ?? $fallbackX),
            'y' => $this->mapPercent($map['y'] ?? null, $this->coordinateY($coordinates, $bounds) ?? $fallbackY),
            'has_coordinates' => $coordinates !== null || isset($map['x'], $map['y']),
            'href' => route('assets.show', $asset),
            'children_count' => (int) $asset->children_count,
            'rentable_children_count' => (int) $asset->rentable_children_count,
            'active_leases_count' => (int) $asset->active_leases_count,
            'open_requests_count' => (int) $asset->open_requests_count,
            'owner' => $owner?->user?->name,
            'manager' => $manager?->user?->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array{latitude:float,longitude:float}|null
     */
    private function coordinates(array $map): ?array
    {
        if (! isset($map['latitude'], $map['longitude']) || ! is_numeric($map['latitude']) || ! is_numeric($map['longitude'])) {
            return null;
        }

        return [
            'latitude' => (float) $map['latitude'],
            'longitude' => (float) $map['longitude'],
        ];
    }

    /**
     * @param  array{latitude:float,longitude:float}|null  $coordinates
     * @param  array<string, float>|null  $bounds
     */
    private function coordinateX(?array $coordinates, ?array $bounds): ?float
    {
        if (! $coordinates || ! $bounds) {
            return null;
        }

        $range = $bounds['max_longitude'] - $bounds['min_longitude'];

        if ($range <= 0) {
            return 50;
        }

        return 10 + (($coordinates['longitude'] - $bounds['min_longitude']) / $range) * 80;
    }

    /**
     * @param  array{latitude:float,longitude:float}|null  $coordinates
     * @param  array<string, float>|null  $bounds
     */
    private function coordinateY(?array $coordinates, ?array $bounds): ?float
    {
        if (! $coordinates || ! $bounds) {
            return null;
        }

        $range = $bounds['max_latitude'] - $bounds['min_latitude'];

        if ($range <= 0) {
            return 50;
        }

        return 10 + (($bounds['max_latitude'] - $coordinates['latitude']) / $range) * 80;
    }

    private function mapPercent(mixed $value, float $fallback): float
    {
        if (is_numeric($value)) {
            return max(4, min(96, (float) $value));
        }

        return max(4, min(96, $fallback));
    }
}
