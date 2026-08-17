<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceVendorDetailData;

final class MaintenanceVendorNoticesPresenter
{
    /** @return array<string, array<string, mixed>> */
    public function present(MaintenanceVendorDetailData $data): array
    {
        $allHref = route('maintenance-work-orders.index', ['vendor_id' => $data->vendor->id]);

        return [
            'workload' => $this->notice(
                $data->counts['open'] > 0 ? 'primary' : 'teal',
                'bi-clipboard2-check',
                trans($data->counts['open'] > 0
                    ? 'app.maintenance_vendors.workload_active_title'
                    : 'app.maintenance_vendors.workload_clear_title', ['count' => $data->counts['open']]),
                trans($data->counts['open'] > 0
                    ? 'app.maintenance_vendors.workload_active_description'
                    : 'app.maintenance_vendors.workload_clear_description'),
                $allHref,
            ),
            'schedule' => $this->schedule($data),
            'financial' => $this->notice(
                $data->counts['completed'] > 0 ? 'teal' : 'muted',
                'bi-cash-stack',
                trans($data->counts['completed'] > 0
                    ? 'app.maintenance_vendors.financial_history_title'
                    : 'app.maintenance_vendors.financial_empty_title'),
                trans($data->counts['completed'] > 0
                    ? 'app.maintenance_vendors.financial_history_description'
                    : 'app.maintenance_vendors.financial_empty_description'),
                $allHref,
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function schedule(MaintenanceVendorDetailData $data): array
    {
        $state = $data->counts['overdue'] > 0
            ? 'overdue'
            : ($data->counts['today'] > 0
                ? 'today'
                : ($data->counts['upcoming'] > 0 ? 'upcoming' : 'clear'));

        return $this->notice(
            $state === 'overdue' ? 'danger' : ($state === 'clear' ? 'muted' : 'primary'),
            $state === 'overdue' ? 'bi-exclamation-triangle' : 'bi-calendar3',
            trans("app.maintenance_vendors.schedule_{$state}_title", [
                'count' => $data->counts[$state] ?? 0,
            ]),
            trans("app.maintenance_vendors.schedule_{$state}_description"),
            route('maintenance-work-orders.index', [
                'vendor_id' => $data->vendor->id,
                'schedule' => $state === 'clear' ? 'all' : $state,
            ]),
        );
    }

    /** @return array{tone:string,icon:string,title:string,description:string,actions:array<int, array<string, mixed>>} */
    private function notice(
        string $tone,
        string $icon,
        string $title,
        string $description,
        string $href,
    ): array {
        return [
            'tone' => $tone,
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
            'actions' => [[
                'label' => trans('app.maintenance_vendors.view_all_work_orders'),
                'href' => $href,
                'variant' => 'secondary',
            ]],
        ];
    }
}
