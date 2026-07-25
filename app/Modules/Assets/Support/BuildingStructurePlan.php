<?php

namespace App\Modules\Assets\Support;

final class BuildingStructurePlan
{
    public const MAX_FLOORS = 30;

    public const MAX_UNITS_PER_FLOOR = 20;

    public const MAX_RECORDS = 250;

    public function totalRecords(int $floors, int $unitsPerFloor): int
    {
        return 1 + $floors + ($floors * $unitsPerFloor);
    }

    /** @return array<int, int> */
    public function floorNumbers(int $start, int $count): array
    {
        return range($start, $start + $count - 1);
    }

    /** @return array<int, string> */
    public function codes(string $prefix, int $floorStart, int $floors, int $unitsPerFloor): array
    {
        $codes = [$prefix];

        foreach ($this->floorNumbers($floorStart, $floors) as $floor) {
            $codes[] = $this->floorCode($prefix, $floor);

            for ($position = 1; $position <= $unitsPerFloor; $position++) {
                $codes[] = $this->unitCode($prefix, $floor, $position);
            }
        }

        return $codes;
    }

    public function floorCode(string $prefix, int $floor): string
    {
        return sprintf('%s-F%02d', $prefix, $floor);
    }

    public function unitCode(string $prefix, int $floor, int $position): string
    {
        return sprintf('%s-F%02d-U%02d', $prefix, $floor, $position);
    }

    public function floorLabel(int $floor): string
    {
        return $floor === 0 ? 'G' : (string) $floor;
    }

    public function unitLabel(int $floor, int $position): string
    {
        return $floor === 0
            ? sprintf('%03d', $position)
            : sprintf('%d%02d', $floor, $position);
    }

    public function floorTitle(int $floor, string $locale): string
    {
        return $floor === 0
            ? trans('app.assets.builder.generated_ground_floor', locale: $locale)
            : trans('app.assets.builder.generated_floor', ['number' => $floor], $locale);
    }

    public function unitTitle(string $type, string $label, string $locale): string
    {
        return trans("app.assets.builder.generated_{$type}", ['number' => $label], $locale);
    }
}
