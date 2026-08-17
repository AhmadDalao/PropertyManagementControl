<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceVendorDetailData;

final class MaintenanceVendorWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceVendorDetailData $data): array
    {
        $vendor = $data->vendor;
        $next = $data->nextWorkOrder;
        $state = $vendor->status !== 'active'
            ? 'inactive'
            : ($data->counts['overdue'] > 0 ? 'overdue' : ($next ? 'active' : 'ready'));
        $actions = [];

        if ($next) {
            $actions[] = [
                'label' => trans('app.maintenance_vendors.open_next_work_order'),
                'href' => route('maintenance-work-orders.show', $next),
                'variant' => 'primary',
            ];
        } elseif ($vendor->status === 'active') {
            $actions[] = [
                'label' => trans('app.maintenance_vendors.open_maintenance_queue'),
                'href' => route('maintenance-requests.index', ['status' => 'open']),
                'variant' => 'primary',
            ];
        }

        $actions[] = [
            'label' => trans('app.maintenance_vendors.view_all_work_orders'),
            'href' => route('maintenance-work-orders.index', ['vendor_id' => $vendor->id]),
            'variant' => 'secondary',
        ];

        if ($vendor->status === 'active') {
            $actions[] = [
                'label' => trans('app.maintenance_vendors.archive'),
                'href' => route('maintenance-vendors.destroy', $vendor),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.maintenance_vendors.archive_confirm', ['name' => $vendor->name]),
            ];
        }

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.maintenance_vendors.workflow_{$state}_title"),
            'description' => trans("app.maintenance_vendors.workflow_{$state}_description"),
            'status' => $data->statusLabel,
            'tone' => $state === 'overdue' ? 'danger' : $data->statusTone,
            'icon' => $state === 'overdue' ? 'bi-exclamation-triangle' : 'bi-buildings',
            'actions' => $actions,
        ];
    }
}
