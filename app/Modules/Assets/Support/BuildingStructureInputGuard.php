<?php

namespace App\Modules\Assets\Support;

use Illuminate\Validation\ValidationException;

final class BuildingStructureInputGuard
{
    public function __construct(private readonly BuildingStructurePlan $plan) {}

    /** @param array<string, mixed> $data */
    public function ensure(array $data): void
    {
        $floors = filter_var($data['floor_count'] ?? null, FILTER_VALIDATE_INT);
        $units = filter_var($data['units_per_floor'] ?? null, FILTER_VALIDATE_INT);
        $floorStart = filter_var($data['floor_start'] ?? null, FILTER_VALIDATE_INT);

        $this->ensureTitles($data);
        $this->ensureCodeAndCurrency($data);
        $this->ensureOptions($data);
        $this->ensureCounts($floors, $units, $floorStart);
        $this->ensureStakeholders($data);
        $this->ensureAmounts($data);
        $this->ensureCoordinates($data);
    }

    /** @param array<string, mixed> $data */
    private function ensureTitles(array $data): void
    {
        foreach (['title_en', 'title_ar'] as $field) {
            if (! is_string($data[$field] ?? null) || trim((string) $data[$field]) === '') {
                $this->fail($field, trans('validation.required', [
                    'attribute' => trans("app.assets.{$field}"),
                ]));
            }

            if (mb_strlen((string) $data[$field]) > 255) {
                $this->fail($field, trans('validation.max.string', [
                    'attribute' => trans("app.assets.{$field}"),
                    'max' => 255,
                ]));
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureCodeAndCurrency(array $data): void
    {
        $prefix = $data['code_prefix'] ?? null;
        if (
            ! is_string($prefix)
            || mb_strlen($prefix) > 24
            || preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $prefix) !== 1
        ) {
            $this->fail('code_prefix', trans('validation.regex', [
                'attribute' => trans('app.assets.builder.code_prefix'),
            ]));
        }

        if (
            ! is_string($data['currency'] ?? null)
            || preg_match('/^[A-Z]{3}$/', (string) $data['currency']) !== 1
        ) {
            $this->fail('currency', trans('validation.regex', [
                'attribute' => trans('app.assets.currency'),
            ]));
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureOptions(array $data): void
    {
        if (! in_array($data['usage_type'] ?? null, AssetOptions::USAGES, true)) {
            $this->fail('usage_type', trans('validation.in', [
                'attribute' => trans('app.assets.usage_type'),
            ]));
        }

        if (! in_array($data['unit_type'] ?? null, ['unit', 'space'], true)) {
            $this->fail('unit_type', trans('validation.in', [
                'attribute' => trans('app.assets.builder.unit_type'),
            ]));
        }
    }

    private function ensureCounts(int|false $floors, int|false $units, int|false $floorStart): void
    {
        if ($floors === false || $floors < 1 || $floors > BuildingStructurePlan::MAX_FLOORS) {
            $this->fail('floor_count', trans('validation.between.numeric', [
                'attribute' => trans('app.assets.builder.floor_count'),
                'min' => 1,
                'max' => BuildingStructurePlan::MAX_FLOORS,
            ]));
        }

        if ($units === false || $units < 1 || $units > BuildingStructurePlan::MAX_UNITS_PER_FLOOR) {
            $this->fail('units_per_floor', trans('validation.between.numeric', [
                'attribute' => trans('app.assets.builder.units_per_floor'),
                'min' => 1,
                'max' => BuildingStructurePlan::MAX_UNITS_PER_FLOOR,
            ]));
        }

        if (! in_array($floorStart, [0, 1], true)) {
            $this->fail('floor_start', trans('validation.in', [
                'attribute' => trans('app.assets.builder.floor_start'),
            ]));
        }

        if ($this->plan->totalRecords($floors, $units) > BuildingStructurePlan::MAX_RECORDS) {
            $this->fail('units_per_floor', trans('app.assets.builder.record_limit_error', [
                'limit' => BuildingStructurePlan::MAX_RECORDS,
            ]));
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureStakeholders(array $data): void
    {
        foreach ([
            'primary_owner_user_id' => 'app.assets.primary_owner',
            'primary_manager_user_id' => 'app.assets.primary_manager',
        ] as $field => $label) {
            if (! filled($data[$field] ?? null)) {
                $this->fail($field, trans('validation.required', [
                    'attribute' => trans($label),
                ]));
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureAmounts(array $data): void
    {
        foreach (['valuation_amount', 'area', 'unit_area'] as $field) {
            if (! filled($data[$field] ?? null) || (is_numeric($data[$field]) && (float) $data[$field] >= 0)) {
                continue;
            }

            $this->fail($field, trans('validation.min.numeric', [
                'attribute' => trans(
                    $field === 'unit_area'
                        ? 'app.assets.builder.unit_area'
                        : "app.assets.{$field}",
                ),
                'min' => 0,
            ]));
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureCoordinates(array $data): void
    {
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        if (filled($latitude) !== filled($longitude)) {
            $missing = filled($latitude) ? 'longitude' : 'latitude';
            $present = filled($latitude) ? 'latitude' : 'longitude';
            $this->fail($missing, trans('validation.required_with', [
                'attribute' => trans("app.assets.{$missing}"),
                'values' => trans("app.assets.{$present}"),
            ]));
        }

        $this->ensureCoordinate('latitude', $latitude, -90, 90);
        $this->ensureCoordinate('longitude', $longitude, -180, 180);
    }

    private function ensureCoordinate(string $field, mixed $value, int $min, int $max): void
    {
        if (! filled($value)) {
            return;
        }

        if (! is_numeric($value) || (float) $value < $min || (float) $value > $max) {
            $this->fail($field, trans('validation.between.numeric', [
                'attribute' => trans("app.assets.{$field}"),
                'min' => $min,
                'max' => $max,
            ]));
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
