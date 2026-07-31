<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final class MaintenanceWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $workflowKey = $request->status;

        if ($request->status === 'resolved') {
            $workflowKey = $request->tenant_confirmed_at
                ? 'confirmed'
                : 'awaiting_confirmation';
        }

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.maintenance.workflow_{$workflowKey}_title"),
            'description' => trans("app.maintenance.workflow_{$workflowKey}_description"),
            'status' => trans("app.status.{$request->status}"),
            'tone' => match ($request->status) {
                'resolved' => 'teal',
                'cancelled' => 'danger',
                default => 'primary',
            },
            'icon' => 'bi-tools',
            'actions' => $data->tenantMode
                ? $this->tenantActions($data)
                : $this->managerActions($data),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function managerActions(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $latestWorkOrder = $data->workOrders->first();
        $actions = [];

        if (
            $latestWorkOrder
            && ! in_array($latestWorkOrder->status, ['completed', 'cancelled'], true)
        ) {
            $actions[] = [
                'label' => trans('app.work_orders.open_current'),
                'href' => route('maintenance-work-orders.show', $latestWorkOrder),
                'variant' => 'primary',
            ];
        } elseif (! in_array($request->status, ['resolved', 'cancelled'], true)) {
            $actions[] = [
                'label' => trans('app.work_orders.create'),
                'href' => route('maintenance-requests.work-orders.create', $request),
                'variant' => 'primary',
            ];
        }

        $actions[] = [
            'label' => trans(in_array($request->status, ['resolved', 'cancelled'], true)
                ? 'app.maintenance.reopen_request'
                : 'app.maintenance.triage_request_action'),
            'href' => route('maintenance-requests.edit', $request),
            'variant' => $actions === [] ? 'primary' : 'secondary',
        ];

        if (
            $request->status !== 'cancelled'
            && PortfolioModules::enabledForUser($data->actor, 'expenses')
        ) {
            $actions[] = [
                'label' => trans('app.maintenance.add_expense'),
                'href' => route('expenses.create', [
                    'portfolio_id' => $request->portfolio_id,
                    'asset_id' => $request->asset_id,
                    'maintenance_request_id' => $request->id,
                ]),
                'variant' => 'secondary',
            ];
        }

        if (in_array($request->status, ['open', 'in_progress'], true)) {
            $actions[] = [
                'label' => trans('app.maintenance.cancel'),
                'href' => route('maintenance-requests.destroy', $request),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.maintenance.cancel_confirm', ['id' => $request->id]),
            ];
        }

        return $actions;
    }

    /** @return array<int, array<string, mixed>> */
    private function tenantActions(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $actions = [];

        if ($request->status === 'resolved' && ! $request->tenant_confirmed_at) {
            $actions[] = [
                'label' => trans('app.maintenance.review_resolution'),
                'href' => route('maintenance-requests.resolution-response.create', $request),
                'variant' => 'primary',
            ];
        }

        $actions[] = [
            'label' => trans('app.maintenance.add_comment'),
            'href' => route('maintenance-requests.edit', $request),
            'variant' => $actions === [] ? 'primary' : 'secondary',
        ];

        if (in_array($request->status, ['open', 'in_progress'], true)) {
            $actions[] = [
                'label' => trans('app.maintenance.cancel'),
                'href' => route('maintenance-requests.destroy', $request),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.maintenance.cancel_confirm', ['id' => $request->id]),
            ];
        }

        return $actions;
    }
}
