<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Illuminate\Support\Str;

class ShowcaseUnitBuilder
{
    public function __construct(
        private readonly ShowcaseTargets $targets,
    ) {}

    /** @return list<Asset> */
    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        Asset $building,
        int $buildingIndex,
    ): array {
        $units = [];

        for ($floorIndex = 0; $floorIndex < 4; $floorIndex++) {
            $floorNumber = $floorIndex + 1;
            $floor = Asset::query()->updateOrCreate(
                ['code' => $this->targets->buildingCode($dataset, $buildingIndex)."-F{$floorNumber}"],
                [
                    'portfolio_id' => $portfolio->id,
                    'parent_id' => $building->id,
                    'asset_type' => 'floor',
                    'usage_type' => $building->usage_type,
                    'title_en' => "Floor {$floorNumber}",
                    'title_ar' => "الطابق {$floorNumber}",
                    'slug' => Str::slug("{$building->code}-floor-{$floorNumber}"),
                    'status' => 'active',
                    'occupancy_status' => 'partially_occupied',
                    'rentable' => false,
                    'valuation_amount' => 1_600_000,
                    'currency' => 'SAR',
                    'area' => 540,
                    'sort_order' => $floorIndex,
                    'level_label' => (string) $floorNumber,
                    'address' => $building->address,
                    'address_ar' => $building->address_ar,
                ],
            );

            for ($unitIndex = 0; $unitIndex < 4; $unitIndex++) {
                $unitNumber = ($floorNumber * 100) + $unitIndex + 1;
                $globalUnitIndex = ($floorIndex * 4) + $unitIndex;
                $occupied = $globalUnitIndex < 10;
                $maintenance = $globalUnitIndex === 14;
                $units[] = Asset::query()->updateOrCreate(
                    ['code' => $this->targets->buildingCode($dataset, $buildingIndex)."-U{$unitNumber}"],
                    [
                        'portfolio_id' => $portfolio->id,
                        'parent_id' => $floor->id,
                        'asset_type' => 'unit',
                        'usage_type' => $building->usage_type === 'mixed' && $floorIndex === 0
                            ? 'commercial'
                            : $building->usage_type,
                        'title_en' => ($building->usage_type === 'commercial' ? 'Commercial Unit ' : 'Apartment ').$unitNumber,
                        'title_ar' => ($building->usage_type === 'commercial' ? 'وحدة تجارية ' : 'شقة ').$unitNumber,
                        'slug' => Str::slug("{$building->code}-unit-{$unitNumber}"),
                        'status' => 'active',
                        'occupancy_status' => $maintenance ? 'maintenance' : ($occupied ? 'occupied' : 'vacant'),
                        'rentable' => true,
                        'valuation_amount' => 420_000 + ($globalUnitIndex * 7_500),
                        'currency' => 'SAR',
                        'area' => 105 + ($unitIndex * 8),
                        'sort_order' => $unitIndex,
                        'level_label' => (string) $floorNumber,
                        'unit_label' => (string) $unitNumber,
                        'address' => $building->address,
                        'address_ar' => $building->address_ar,
                    ],
                );
            }
        }

        return $units;
    }
}
