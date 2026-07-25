import type { BuildingSetupValues } from './types';

export function positiveInteger(value: number | string, fallback = 0) {
    const number = Number(value);

    return Number.isInteger(number) && number > 0 ? number : fallback;
}

export function floorStart(value: number | string) {
    return Number(value) === 0 ? 0 : 1;
}

export function structureTotals(values: BuildingSetupValues) {
    const floors = positiveInteger(values.floor_count);
    const unitsPerFloor = positiveInteger(values.units_per_floor);
    const rentable = floors * unitsPerFloor;

    return {
        floors,
        unitsPerFloor,
        rentable,
        total: 1 + floors + rentable,
    };
}

export function previewFloorNumbers(values: BuildingSetupValues) {
    const totals = structureTotals(values);
    const start = floorStart(values.floor_start);

    return Array.from(
        { length: Math.min(totals.floors, 3) },
        (_, index) => start + index,
    );
}

export function generatedUnitLabel(floor: number, position: number) {
    return floor === 0
        ? String(position).padStart(3, '0')
        : `${floor}${String(position).padStart(2, '0')}`;
}
