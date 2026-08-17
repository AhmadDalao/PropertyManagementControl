<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceWorkOrderDetailData;

final class MaintenanceWorkOrderNoticesPresenter
{
    /** @return array<string, array<string, mixed>> */
    public function present(MaintenanceWorkOrderDetailData $data): array
    {
        $workOrder = $data->workOrder;

        return [
            'assignment' => $this->notice(
                $workOrder->assigned_to_user_id ? 'teal' : 'primary',
                'bi-person-check',
                trans($workOrder->assigned_to_user_id
                    ? 'app.work_orders.assignment_ready_title'
                    : 'app.work_orders.assignment_missing_title'),
                trans($workOrder->assigned_to_user_id
                    ? 'app.work_orders.assignment_ready_description'
                    : 'app.work_orders.assignment_missing_description'),
                $workOrder->vendor ? [[
                    'label' => trans('app.work_orders.open_contractor'),
                    'href' => route('maintenance-vendors.show', $workOrder->vendor),
                    'variant' => 'secondary',
                ]] : [],
            ),
            'schedule' => $this->notice(
                $data->scheduleTone,
                $data->scheduleCode === 'overdue' ? 'bi-exclamation-triangle' : 'bi-calendar3',
                trans("app.work_orders.schedule_{$data->scheduleCode}_title"),
                trans("app.work_orders.schedule_{$data->scheduleCode}_description"),
                [[
                    'label' => trans('app.work_orders.update_schedule'),
                    'href' => route('maintenance-work-orders.edit', $workOrder),
                    'variant' => 'secondary',
                ]],
            ),
            'cost' => $this->cost($data),
            'completion' => $this->notice(
                $data->statusTone,
                $workOrder->status === 'completed' ? 'bi-check2-circle' : 'bi-clipboard-check',
                trans("app.work_orders.completion_{$workOrder->status}_title"),
                trans("app.work_orders.completion_{$workOrder->status}_description"),
                [[
                    'label' => trans('app.work_orders.open_request'),
                    'href' => route('maintenance-requests.show', $data->request),
                    'variant' => 'secondary',
                ]],
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function cost(MaintenanceWorkOrderDetailData $data): array
    {
        $workOrder = $data->workOrder;
        $finalized = $workOrder->status === 'completed' && $workOrder->final_amount !== null;
        $actions = $data->recordedExpenseUrl ? [[
            'label' => trans('app.work_orders.open_recorded_expense'),
            'href' => $data->recordedExpenseUrl,
            'variant' => 'secondary',
        ]] : ($data->expenseCreateUrl ? [[
            'label' => trans('app.work_orders.record_expense'),
            'href' => $data->expenseCreateUrl,
            'variant' => 'primary',
        ]] : []);

        return $this->notice(
            $finalized ? $data->varianceTone : 'primary',
            'bi-cash-stack',
            trans($data->recordedExpenseUrl
                ? 'app.work_orders.cost_expense_recorded_title'
                : ($finalized ? 'app.work_orders.cost_final_title' : 'app.work_orders.cost_pending_title')),
            trans($data->recordedExpenseUrl
                ? 'app.work_orders.cost_expense_recorded_description'
                : $this->costDescriptionKey($data)),
            $actions,
        );
    }

    private function costDescriptionKey(MaintenanceWorkOrderDetailData $data): string
    {
        if ($data->workOrder->status !== 'completed' || $data->workOrder->final_amount === null) {
            return 'app.work_orders.cost_pending_description';
        }

        if ((float) $data->workOrder->final_amount === 0.0) {
            return 'app.work_orders.cost_zero_description';
        }

        return $data->expensesEnabled
            ? 'app.work_orders.cost_final_description'
            : 'app.work_orders.cost_module_disabled_description';
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array{tone:string,icon:string,title:string,description:string,actions:array<int, array<string, mixed>>}
     */
    private function notice(
        string $tone,
        string $icon,
        string $title,
        string $description,
        array $actions,
    ): array {
        return compact('tone', 'icon', 'title', 'description', 'actions');
    }
}
