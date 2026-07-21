<?php

namespace App\Modules\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'asset_type' => ['required', 'string'],
            'usage_type' => ['required', 'string'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:assets,code'],
            'status' => ['required', 'string'],
            'occupancy_status' => ['required', 'string'],
            'rentable' => ['nullable', 'boolean'],
            'valuation_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'area' => ['nullable', 'numeric'],
            'level_label' => ['nullable', 'string', 'max:255'],
            'unit_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'address_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
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
}
