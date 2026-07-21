<?php

namespace App\Modules\Assets\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $asset = $this->route('asset');

        if (! $user?->hasAnyRole(['superadmin', 'owner', 'property_manager']) || ! $asset instanceof Asset) {
            return false;
        }

        return $user->hasRole('superadmin') || $user->portfolio_id === $asset->portfolio_id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'asset_type' => ['required', 'string'],
            'usage_type' => ['required', 'string'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
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
