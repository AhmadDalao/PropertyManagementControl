<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceVendorDetailData;

final class MaintenanceVendorHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceVendorDetailData $data): array
    {
        return [
            'eyebrow' => trans('app.maintenance_vendors.detail_eyebrow'),
            'title' => $data->vendor->name,
            'description' => implode(' · ', [$data->categoryLabel, $data->statusLabel]),
            'backHref' => route('maintenance-vendors.index'),
            'backLabel' => trans('app.maintenance_vendors.directory'),
            'actions' => [[
                'label' => trans('app.maintenance_vendors.edit'),
                'href' => route('maintenance-vendors.edit', $data->vendor),
                'variant' => 'primary',
            ]],
        ];
    }
}
