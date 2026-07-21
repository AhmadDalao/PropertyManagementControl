<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use Illuminate\Support\Collection;

class PropertyMapCoordinates
{
    /**
     * @param  Collection<int, Asset>  $assets
     * @return array{min_latitude:float,max_latitude:float,min_longitude:float,max_longitude:float}|null
     */
    public function bounds(Collection $assets): ?array
    {
        $coordinates = $assets
            ->map(function (Asset $asset): ?array {
                $map = $this->metadata($asset);
                $latitude = $this->number($map, 'latitude');
                $longitude = $this->number($map, 'longitude');

                if ($latitude === null || $longitude === null) {
                    return null;
                }

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
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
     * @return array<string, mixed>
     */
    public function metadata(Asset $asset): array
    {
        $metadata = $asset->getAttribute('meta_json');

        if (! is_array($metadata)) {
            return [];
        }

        $map = $metadata['map'] ?? null;

        return is_array($map) ? $map : [];
    }

    /**
     * @param  array<string, mixed>  $map
     */
    public function number(array $map, string $key): ?float
    {
        return is_numeric($map[$key] ?? null) ? (float) $map[$key] : null;
    }

    /**
     * @param  array<string, float>|null  $bounds
     */
    public function percent(
        ?float $value,
        ?array $bounds,
        string $axis,
        bool $inverse = false,
    ): float {
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
}
