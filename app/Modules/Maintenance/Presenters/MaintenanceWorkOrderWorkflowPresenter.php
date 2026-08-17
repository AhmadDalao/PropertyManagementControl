<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceWorkOrderDetailData;

final class MaintenanceWorkOrderWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceWorkOrderDetailData $data): array
    {
        $workOrder = $data->workOrder;
        $actions = [[
            'label' => trans('app.work_orders.update'),
            'href' => route('maintenance-work-orders.edit', $workOrder),
            'variant' => 'primary',
        ], [
            'label' => trans('app.work_orders.open_request'),
            'href' => route('maintenance-requests.show', $data->request),
            'variant' => 'secondary',
        ]];

        if ($data->recordedExpenseUrl) {
            $actions[] = [
                'label' => trans('app.work_orders.open_recorded_expense'),
                'href' => $data->recordedExpenseUrl,
                'variant' => 'secondary',
            ];
        } elseif ($data->expenseCreateUrl) {
            $actions[] = [
                'label' => trans('app.work_orders.record_expense'),
                'href' => $data->expenseCreateUrl,
                'variant' => 'secondary',
            ];
        }

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.work_orders.workflow_{$workOrder->status}_title"),
            'description' => trans("app.work_orders.workflow_{$workOrder->status}_description"),
            'status' => $data->statusLabel,
            'tone' => $data->statusTone,
            'icon' => 'bi-clipboard2-check',
            'actions' => $actions,
        ];
    }
}
