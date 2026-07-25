<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Portfolio;
use App\Modules\Assets\Data\AssetFormData;

final class BuildingStructureInitialValuesPresenter
{
    /** @return array<string, mixed> */
    public function present(
        AssetFormData $data,
        Portfolio $portfolio,
        ?int $ownerId,
        ?int $managerId,
    ): array {
        return [
            'portfolio_id' => (string) $data->portfolioId,
            'title_en' => '',
            'title_ar' => '',
            'code_prefix' => '',
            'usage_type' => 'residential',
            'floor_count' => 4,
            'units_per_floor' => 4,
            'floor_start' => 1,
            'unit_type' => 'unit',
            'primary_owner_user_id' => (string) ($ownerId ?? ''),
            'primary_manager_user_id' => (string) ($managerId ?? ''),
            'valuation_amount' => '',
            'currency' => $portfolio->default_currency ?: 'SAR',
            'area' => '',
            'unit_area' => '',
            'address' => $portfolio->address ?? '',
            'address_ar' => $portfolio->address_ar ?? '',
            'map_zone_en' => '',
            'map_zone_ar' => '',
            'land_number' => '',
            'latitude' => '',
            'longitude' => '',
        ];
    }
}
