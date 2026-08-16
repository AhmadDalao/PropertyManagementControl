<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceUpdate;
use App\Models\MaintenanceWorkOrder;
use App\Modules\Maintenance\Data\MaintenanceDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

class MaintenanceRelatedPresenter
{
    /** @return array<int, array<string, mixed>> */
    public function present(MaintenanceDetailData $data): array
    {
        $panels = [
            $this->workOrders($data),
            $this->updates($data),
        ];

        if (
            ! $data->tenantMode
            && PortfolioModules::enabledForUser($data->actor, 'expenses')
        ) {
            $panels[] = $this->expenses($data);
        }

        return $panels;
    }

    /** @return array<string, mixed> */
    private function workOrders(MaintenanceDetailData $data): array
    {
        $reference = trans('app.work_orders.reference');
        $vendor = trans('app.work_orders.vendor');
        $schedule = trans('app.work_orders.schedule');
        $status = trans('app.work_orders.status');
        $access = trans('app.work_orders.access');

        return [
            'key' => 'work-orders',
            'title' => trans($data->tenantMode
                ? 'app.work_orders.service_visits'
                : 'app.maintenance.work_orders'),
            'description' => trans($data->tenantMode
                ? 'app.work_orders.service_visits_help'
                : 'app.work_orders.request_work_orders_help'),
            'columns' => $data->tenantMode
                ? [$schedule, $status, $access]
                : [$reference, $vendor, $schedule, $status],
            'rows' => $data->workOrders->map(
                fn (MaintenanceWorkOrder $workOrder): array => $data->tenantMode
                    ? [
                        $schedule => $workOrder->scheduled_at?->toDateTimeString() ?: '-',
                        $status => trans("app.status.{$workOrder->status}"),
                        $access => trans($workOrder->tenant_access_required
                            ? 'app.work_orders.access_required'
                            : 'app.work_orders.no_access_required'),
                    ]
                    : [
                        $reference => [
                            'label' => $workOrder->reference_code,
                            'href' => route('maintenance-work-orders.show', $workOrder),
                        ],
                        $vendor => $workOrder->vendor_name,
                        $schedule => $workOrder->scheduled_at?->toDateTimeString() ?: '-',
                        $status => trans("app.status.{$workOrder->status}"),
                    ],
            )->all(),
            'emptyText' => trans($data->tenantMode
                ? 'app.work_orders.no_service_visits'
                : 'app.work_orders.no_work_orders'),
            ...(! $data->tenantMode
                && ! in_array($data->request->status, ['resolved', 'cancelled'], true)
                && $data->workOrders
                    ->whereIn('status', ['draft', 'scheduled', 'in_progress'])
                    ->isEmpty()
                ? [
                    'actionHref' => route('maintenance-requests.work-orders.create', $data->request),
                    'actionLabel' => trans('app.work_orders.create'),
                ]
                : []),
        ];
    }

    /** @return array<string, mixed> */
    private function updates(MaintenanceDetailData $data): array
    {
        $by = trans('app.maintenance.by');
        $from = trans('app.maintenance.from');
        $to = trans('app.maintenance.to');
        $comment = trans('app.maintenance.comment');

        return [
            'key' => 'updates',
            'title' => trans('app.maintenance.updates'),
            'description' => trans($data->tenantMode
                ? 'app.maintenance.updates_help_tenant'
                : 'app.maintenance.updates_help_manager'),
            'columns' => [$by, $from, $to, $comment],
            'rows' => $data->updates->map(fn (MaintenanceUpdate $update): array => [
                $by => $update->user->name ?? trans('app.maintenance.system'),
                $from => $update->status_from ? trans("app.status.{$update->status_from}") : '-',
                $to => $update->status_to ? trans("app.status.{$update->status_to}") : '-',
                $comment => $update->comment,
            ])->all(),
            'emptyText' => trans('app.maintenance.no_updates'),
        ];
    }

    /** @return array<string, mixed> */
    private function expenses(MaintenanceDetailData $data): array
    {
        $expense = trans('app.maintenance.expense');
        $vendor = trans('app.maintenance.vendor');
        $amount = trans('app.maintenance.amount');
        $status = trans('app.maintenance.status');

        return [
            'key' => 'expenses',
            'title' => trans('app.maintenance.expenses'),
            'description' => trans('app.maintenance.expenses_help'),
            'columns' => [$expense, $vendor, $amount, $status],
            'rows' => $data->expenses->map(fn (ExpenseEntry $item): array => [
                $expense => [
                    'label' => $item->title,
                    'href' => route('expenses.show', $item),
                ],
                $vendor => $item->vendor_name ?: '-',
                $amount => number_format((float) $item->amount, 2).' '.$item->currency,
                $status => trans("app.status.{$item->status}"),
            ])->all(),
            'emptyText' => trans('app.maintenance.no_expenses'),
            'actionHref' => route('expenses.create', [
                'maintenance_request_id' => $data->request->id,
                'asset_id' => $data->request->asset_id,
                'portfolio_id' => $data->request->portfolio_id,
            ]),
            'actionLabel' => trans('app.maintenance.add_expense'),
        ];
    }
}
