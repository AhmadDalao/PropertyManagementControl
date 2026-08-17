<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceWorkOrderDetailData;

final class MaintenanceWorkOrderHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceWorkOrderDetailData $data): array
    {
        $workOrder = $data->workOrder;

        return [
            'eyebrow' => trans('app.work_orders.detail_eyebrow'),
            'title' => $workOrder->reference_code,
            'description' => trans('app.work_orders.detail_description', [
                'contractor' => $workOrder->vendor_name,
                'status' => $data->statusLabel,
                'request' => '#'.$data->request->id,
            ]),
            'backHref' => route('maintenance-work-orders.index'),
            'backLabel' => trans('app.work_orders.back_to_register'),
            'actions' => [[
                'label' => trans('app.work_orders.edit', [
                    'reference' => $workOrder->reference_code,
                ]),
                'href' => route('maintenance-work-orders.edit', $workOrder),
                'variant' => 'primary',
            ]],
        ];
    }
}
