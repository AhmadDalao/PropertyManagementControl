<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;

class AssetMetadata
{
    public function get(?Asset $asset, string $key): mixed
    {
        $meta = $this->metadata($asset);
        $map = $meta['map'] ?? [];

        return is_array($map) ? ($map[$key] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function merge(array $data, ?Asset $asset = null): ?array
    {
        $meta = $this->metadata($asset);
        $storedMap = $meta['map'] ?? null;
        $map = is_array($storedMap) ? $storedMap : [];
        $values = [
            'zone_en' => $data['map_zone_en'] ?? $data['map_zone'] ?? null,
            'zone_ar' => $data['map_zone_ar'] ?? null,
            'zone' => $data['map_zone_en'] ?? $data['map_zone'] ?? null,
            'land_number' => $data['land_number'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'x' => $data['map_x'] ?? null,
            'y' => $data['map_y'] ?? null,
        ];

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                unset($map[$key]);

                continue;
            }

            $map[$key] = in_array($key, ['latitude', 'longitude', 'x', 'y'], true)
                ? (float) $value
                : trim((string) $value);
        }

        if ($map === []) {
            unset($meta['map']);
        } else {
            $meta['map'] = $map;
        }

        return $meta === [] ? null : $meta;
    }

    public function coordinateLabel(Asset $asset): ?string
    {
        $latitude = $this->get($asset, 'latitude');
        $longitude = $this->get($asset, 'longitude');

        return $latitude === null || $longitude === null ? null : "{$latitude}, {$longitude}";
    }

    public function canvasPositionLabel(Asset $asset): ?string
    {
        $x = $this->get($asset, 'x');
        $y = $this->get($asset, 'y');

        return $x === null || $y === null ? null : "{$x}, {$y}";
    }

    public function hasIdentity(Asset $asset): bool
    {
        return filled($this->get($asset, 'zone')) && filled($this->get($asset, 'land_number'));
    }

    public function hasPosition(Asset $asset): bool
    {
        $hasCoordinates = filled($this->get($asset, 'latitude')) && filled($this->get($asset, 'longitude'));
        $hasCanvasPosition = filled($this->get($asset, 'x')) && filled($this->get($asset, 'y'));

        return $hasCoordinates || $hasCanvasPosition;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(?Asset $asset): array
    {
        if (! $asset) {
            return [];
        }

        $metadata = $asset->getAttribute('meta_json');

        return is_array($metadata) ? $metadata : [];
    }
}
