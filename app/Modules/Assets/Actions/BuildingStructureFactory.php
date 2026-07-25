<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Assets\Support\AssetStakeholderManager;
use App\Modules\Assets\Support\BuildingStructurePlan;
use Illuminate\Support\Str;

final class BuildingStructureFactory
{
    public function __construct(
        private readonly AssetStakeholderManager $stakeholders,
        private readonly AssetMetadata $metadata,
        private readonly BuildingStructurePlan $plan,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, int $portfolioId): Asset
    {
        $prefix = strtoupper(trim((string) $data['code_prefix']));
        $floors = (int) $data['floor_count'];
        $unitsPerFloor = (int) $data['units_per_floor'];
        $floorStart = (int) $data['floor_start'];
        $building = $this->building($data, $portfolioId, $prefix);

        foreach ($this->plan->floorNumbers($floorStart, $floors) as $floorIndex => $floorNumber) {
            $floor = $this->floor(
                $data,
                $portfolioId,
                $building,
                $prefix,
                $floorNumber,
                $floorIndex,
                $unitsPerFloor,
            );

            for ($position = 1; $position <= $unitsPerFloor; $position++) {
                $this->unit($data, $portfolioId, $floor, $prefix, $floorNumber, $position);
            }
        }

        return $building;
    }

    /** @param array<string, mixed> $data */
    private function building(array $data, int $portfolioId, string $prefix): Asset
    {
        return $this->persist([
            'portfolio_id' => $portfolioId,
            'parent_id' => null,
            'asset_type' => 'building',
            'usage_type' => $data['usage_type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'code' => $prefix,
            'status' => 'active',
            'occupancy_status' => 'vacant',
            'rentable' => false,
            'valuation_amount' => $data['valuation_amount'] ?? 0,
            'currency' => $data['currency'],
            'area' => $data['area'] ?? null,
            'address' => $data['address'] ?? null,
            'address_ar' => $data['address_ar'] ?? null,
            'sort_order' => 0,
            'meta_json' => $this->metadata->merge($data),
        ], $data);
    }

    /** @param array<string, mixed> $data */
    private function floor(
        array $data,
        int $portfolioId,
        Asset $building,
        string $prefix,
        int $number,
        int $index,
        int $unitsPerFloor,
    ): Asset {
        return $this->persist([
            'portfolio_id' => $portfolioId,
            'parent_id' => $building->id,
            'asset_type' => 'floor',
            'usage_type' => $data['usage_type'],
            'title_en' => $this->plan->floorTitle($number, 'en'),
            'title_ar' => $this->plan->floorTitle($number, 'ar'),
            'code' => $this->plan->floorCode($prefix, $number),
            'status' => 'active',
            'occupancy_status' => 'vacant',
            'rentable' => false,
            'valuation_amount' => 0,
            'currency' => $data['currency'],
            'area' => $this->floorArea($data, $unitsPerFloor),
            'level_label' => $this->plan->floorLabel($number),
            'sort_order' => $index + 1,
        ], $data);
    }

    /** @param array<string, mixed> $data */
    private function unit(
        array $data,
        int $portfolioId,
        Asset $floor,
        string $prefix,
        int $floorNumber,
        int $position,
    ): void {
        $unitLabel = $this->plan->unitLabel($floorNumber, $position);
        $type = (string) $data['unit_type'];

        $this->persist([
            'portfolio_id' => $portfolioId,
            'parent_id' => $floor->id,
            'asset_type' => $type,
            'usage_type' => $data['usage_type'],
            'title_en' => $this->plan->unitTitle($type, $unitLabel, 'en'),
            'title_ar' => $this->plan->unitTitle($type, $unitLabel, 'ar'),
            'code' => $this->plan->unitCode($prefix, $floorNumber, $position),
            'status' => 'active',
            'occupancy_status' => 'vacant',
            'rentable' => true,
            'valuation_amount' => 0,
            'currency' => $data['currency'],
            'area' => $data['unit_area'] ?? null,
            'level_label' => $this->plan->floorLabel($floorNumber),
            'unit_label' => $unitLabel,
            'sort_order' => $position,
        ], $data);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $input
     */
    private function persist(array $attributes, array $input): Asset
    {
        $asset = Asset::query()->create([
            ...$attributes,
            'slug' => Str::slug((string) $attributes['title_en']).'-'.Str::lower(Str::random(4)),
        ]);

        $this->stakeholders->sync(
            $asset,
            (int) $input['primary_owner_user_id'],
            (int) $input['primary_manager_user_id'],
        );

        return $asset;
    }

    /** @param array<string, mixed> $data */
    private function floorArea(array $data, int $unitsPerFloor): ?float
    {
        return filled($data['unit_area'] ?? null)
            ? (float) $data['unit_area'] * $unitsPerFloor
            : null;
    }
}
