<?php

namespace App\Modules\Assets\Requests;

use App\Modules\Assets\Support\AssetOptions;
use Illuminate\Validation\Rule;

trait HasAssetValidationAttributes
{
    /**
     * @param  array<int, string>  $statuses
     * @return array<string, array<int, mixed>>
     */
    protected function assetRules(array $statuses): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'asset_type' => ['required', Rule::in(AssetOptions::TYPES)],
            'usage_type' => ['required', Rule::in(AssetOptions::USAGES)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in($statuses)],
            'occupancy_status' => ['required', Rule::in(AssetOptions::OCCUPANCIES)],
            'rentable' => ['nullable', 'boolean'],
            'valuation_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'level_label' => ['nullable', 'string', 'max:255'],
            'unit_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'address_ar' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'map_zone' => ['nullable', 'string', 'max:80'],
            'map_zone_en' => ['nullable', 'string', 'max:80'],
            'map_zone_ar' => ['nullable', 'string', 'max:80'],
            'land_number' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_x' => ['nullable', 'numeric', 'between:0,100'],
            'map_y' => ['nullable', 'numeric', 'between:0,100'],
            'primary_owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'primary_manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.assets.portfolio'),
            'parent_id' => trans('app.assets.parent_asset'),
            'asset_type' => trans('app.assets.asset_type'),
            'usage_type' => trans('app.assets.usage_type'),
            'title_en' => trans('app.assets.title_en'),
            'title_ar' => trans('app.assets.title_ar'),
            'code' => trans('app.assets.code'),
            'status' => trans('app.assets.status'),
            'occupancy_status' => trans('app.assets.occupancy'),
            'valuation_amount' => trans('app.assets.valuation'),
            'currency' => trans('app.assets.currency'),
            'area' => trans('app.assets.area'),
            'primary_owner_user_id' => trans('app.assets.primary_owner'),
            'primary_manager_user_id' => trans('app.assets.primary_manager'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper(trim((string) $this->input('currency')))]);
        }
    }
}
