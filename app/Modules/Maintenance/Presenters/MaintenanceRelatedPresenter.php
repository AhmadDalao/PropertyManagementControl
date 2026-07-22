<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceUpdate;
use App\Modules\Maintenance\Data\MaintenanceDetailData;

class MaintenanceRelatedPresenter
{
    /** @return array<int, array<string, mixed>> */
    public function present(MaintenanceDetailData $data): array
    {
        $panels = [$this->updates($data)];

        if (! $data->tenantMode) {
            $panels[] = $this->expenses($data);
        }

        return $panels;
    }

    /** @return array<string, mixed> */
    private function updates(MaintenanceDetailData $data): array
    {
        $by = trans('app.maintenance.by');
        $from = trans('app.maintenance.from');
        $to = trans('app.maintenance.to');
        $comment = trans('app.maintenance.comment');

        return [
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
