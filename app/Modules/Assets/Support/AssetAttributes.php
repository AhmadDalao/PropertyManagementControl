<?php

namespace App\Modules\Assets\Support;

class AssetAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function from(array $data): array
    {
        return [
            'parent_id' => $data['parent_id'] ?? null,
            'asset_type' => $data['asset_type'],
            'usage_type' => $data['usage_type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'status' => $data['status'],
            'occupancy_status' => $data['occupancy_status'],
            'rentable' => (bool) ($data['rentable'] ?? false),
            'valuation_amount' => $data['valuation_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'SAR',
            'area' => $data['area'] ?? null,
            'level_label' => $data['level_label'] ?? null,
            'unit_label' => $data['unit_label'] ?? null,
            'address' => $data['address'] ?? null,
            'address_ar' => $data['address_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
        ];
    }
}
