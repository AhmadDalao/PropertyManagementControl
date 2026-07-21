<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Queries\PropertyMapSourceQuery;
use Illuminate\Support\Collection;

class PropertyMapPayloadPresenter
{
    /**
     * @param  Collection<int, array<string, mixed>>  $assets
     * @return array{
     *     assets:array<int, array<string, mixed>>,
     *     summary:array<string, mixed>,
     *     config:array<string, mixed>
     * }
     */
    public function present(Collection $assets): array
    {
        $ready = $assets->where('map_ready', true)->count();

        return [
            'assets' => $assets->values()->all(),
            'summary' => [
                'mapped' => $assets->where('has_coordinates', true)->count(),
                'total' => $assets->count(),
                'ready' => $ready,
                'needs_position' => $assets->where('has_coordinates', false)->count(),
                'needs_identity' => $assets->where('has_identity', false)->count(),
                'coverage_percent' => $assets->isNotEmpty()
                    ? round(($ready / $assets->count()) * 100, 1)
                    : 0,
                'zones' => $assets
                    ->pluck('zone')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'payload_limit' => PropertyMapSourceQuery::MAX_MARKERS,
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
}
