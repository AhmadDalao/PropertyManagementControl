<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class AssetInsightsQuery
{
    /**
     * @param  Builder<Asset>  $query
     * @return array<string, int|float>
     */
    public function get(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total_assets')
            ->selectRaw('COALESCE(SUM(valuation_amount), 0) as total_value')
            ->selectRaw('SUM(CASE WHEN rentable = 1 THEN 1 ELSE 0 END) as rentable_assets')
            ->selectRaw("SUM(CASE WHEN rentable = 1 AND occupancy_status = 'vacant' THEN 1 ELSE 0 END) as vacant_rentable_assets")
            ->selectRaw("SUM(CASE WHEN rentable = 1 AND occupancy_status IN ('occupied', 'partially_occupied') THEN 1 ELSE 0 END) as occupied_rentable_assets")
            ->selectRaw("SUM(CASE WHEN occupancy_status = 'occupied' THEN 1 ELSE 0 END) as occupied_assets")
            ->selectRaw("SUM(CASE WHEN occupancy_status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets")
            ->selectRaw("SUM(CASE WHEN asset_type = 'building' THEN 1 ELSE 0 END) as buildings")
            ->selectRaw("SUM(CASE WHEN asset_type = 'floor' THEN 1 ELSE 0 END) as floors")
            ->selectRaw("SUM(CASE WHEN asset_type = 'unit' THEN 1 ELSE 0 END) as units")
            ->selectRaw("SUM(CASE WHEN asset_type = 'space' THEN 1 ELSE 0 END) as spaces")
            ->selectRaw("SUM(CASE WHEN parent_id IS NULL AND NOT EXISTS (SELECT 1 FROM asset_stakeholders s WHERE s.asset_id = assets.id AND s.relationship_type = 'owner' AND s.is_primary = 1 AND s.ends_on IS NULL) THEN 1 ELSE 0 END) as missing_owner")
            ->selectRaw("SUM(CASE WHEN parent_id IS NULL AND NOT EXISTS (SELECT 1 FROM asset_stakeholders s WHERE s.asset_id = assets.id AND s.relationship_type = 'manager' AND s.is_primary = 1 AND s.ends_on IS NULL) THEN 1 ELSE 0 END) as missing_manager")
            ->first();
        $rentable = (int) ($summary?->getAttribute('rentable_assets') ?? 0);
        $occupiedRentable = (int) ($summary?->getAttribute('occupied_rentable_assets') ?? 0);

        return [
            'total_assets' => (int) ($summary?->getAttribute('total_assets') ?? 0),
            'total_value' => (float) ($summary?->getAttribute('total_value') ?? 0),
            'rentable_assets' => $rentable,
            'vacant_rentable_assets' => (int) ($summary?->getAttribute('vacant_rentable_assets') ?? 0),
            'occupied_assets' => (int) ($summary?->getAttribute('occupied_assets') ?? 0),
            'maintenance_assets' => (int) ($summary?->getAttribute('maintenance_assets') ?? 0),
            'buildings' => (int) ($summary?->getAttribute('buildings') ?? 0),
            'floors' => (int) ($summary?->getAttribute('floors') ?? 0),
            'units' => (int) ($summary?->getAttribute('units') ?? 0),
            'spaces' => (int) ($summary?->getAttribute('spaces') ?? 0),
            'missing_owner' => (int) ($summary?->getAttribute('missing_owner') ?? 0),
            'missing_manager' => (int) ($summary?->getAttribute('missing_manager') ?? 0),
            'rentable_occupancy_rate' => $rentable > 0
                ? round(($occupiedRentable / $rentable) * 100, 1)
                : 0,
        ];
    }
}
