<?php

namespace App\Modules\Assets\Requests;

use App\Models\User;
use App\Modules\Assets\Support\AssetOptions;
use App\Modules\Assets\Support\BuildingStructurePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBuildingStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && $actor->hasAnyRole(['superadmin', 'owner']);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => [
                Rule::requiredIf($this->user()?->hasRole('superadmin') ?? false),
                'nullable',
                'integer',
                'exists:portfolios,id',
            ],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'code_prefix' => ['required', 'string', 'max:24', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/'],
            'usage_type' => ['required', Rule::in(AssetOptions::USAGES)],
            'floor_count' => ['required', 'integer', 'min:1', 'max:'.BuildingStructurePlan::MAX_FLOORS],
            'units_per_floor' => ['required', 'integer', 'min:1', 'max:'.BuildingStructurePlan::MAX_UNITS_PER_FLOOR],
            'floor_start' => ['required', 'integer', Rule::in([0, 1])],
            'unit_type' => ['required', Rule::in(['unit', 'space'])],
            'primary_owner_user_id' => ['required', 'integer', 'exists:users,id'],
            'primary_manager_user_id' => ['required', 'integer', 'exists:users,id'],
            'valuation_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'unit_area' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:5000'],
            'address_ar' => ['nullable', 'string', 'max:5000'],
            'map_zone_en' => ['nullable', 'string', 'max:80'],
            'map_zone_ar' => ['nullable', 'string', 'max:80'],
            'land_number' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $records = app(BuildingStructurePlan::class)->totalRecords(
                    (int) $this->input('floor_count', 0),
                    (int) $this->input('units_per_floor', 0),
                );

                if ($records > BuildingStructurePlan::MAX_RECORDS) {
                    $validator->errors()->add(
                        'units_per_floor',
                        trans('app.assets.builder.record_limit_error', [
                            'limit' => BuildingStructurePlan::MAX_RECORDS,
                        ]),
                    );
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.assets.portfolio'),
            'title_en' => trans('app.assets.builder.building_name_en'),
            'title_ar' => trans('app.assets.builder.building_name_ar'),
            'code_prefix' => trans('app.assets.builder.code_prefix'),
            'usage_type' => trans('app.assets.usage_type'),
            'floor_count' => trans('app.assets.builder.floor_count'),
            'units_per_floor' => trans('app.assets.builder.units_per_floor'),
            'floor_start' => trans('app.assets.builder.floor_start'),
            'unit_type' => trans('app.assets.builder.unit_type'),
            'primary_owner_user_id' => trans('app.assets.primary_owner'),
            'primary_manager_user_id' => trans('app.assets.primary_manager'),
            'valuation_amount' => trans('app.assets.valuation'),
            'currency' => trans('app.assets.currency'),
            'area' => trans('app.assets.area'),
            'unit_area' => trans('app.assets.builder.unit_area'),
            'address' => trans('app.fields.address_en'),
            'address_ar' => trans('app.fields.address_ar'),
            'map_zone_en' => trans('app.fields.zone_en'),
            'map_zone_ar' => trans('app.fields.zone_ar'),
            'land_number' => trans('app.assets.land_number'),
            'latitude' => trans('app.assets.latitude'),
            'longitude' => trans('app.assets.longitude'),
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['code_prefix', 'currency'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtoupper(trim((string) $this->input($field)))]);
            }
        }
    }
}
