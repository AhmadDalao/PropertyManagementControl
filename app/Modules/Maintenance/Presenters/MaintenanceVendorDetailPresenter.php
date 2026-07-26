<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceVendorAccess;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Support\Collection;

final class MaintenanceVendorDetailPresenter
{
    public function __construct(
        private readonly MaintenanceVendorAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceVendor $vendor, User $actor): array
    {
        $this->access->ensureCanAccess($actor, $vendor);
        $vendor->loadMissing('portfolio')->loadCount([
            'workOrders',
            'workOrders as active_work_orders_count' => fn ($query) => $query
                ->whereIn('status', ['scheduled', 'in_progress']),
        ]);
        $workOrders = $vendor->workOrders()
            ->with('maintenanceRequest.asset')
            ->latest()
            ->limit(25)
            ->get();

        return [
            'header' => [
                'eyebrow' => trans('app.maintenance_vendors.detail_eyebrow'),
                'title' => $vendor->name,
                'description' => implode(' · ', [
                    trans("app.status.{$vendor->service_category}"),
                    trans("app.status.{$vendor->status}"),
                ]),
                'backHref' => route('maintenance-vendors.index'),
                'backLabel' => trans('app.maintenance_vendors.directory'),
                'actions' => [[
                    'label' => trans('app.maintenance_vendors.edit'),
                    'href' => route('maintenance-vendors.edit', $vendor),
                    'variant' => 'primary',
                ]],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.maintenance_vendors.status'), 'value' => trans("app.status.{$vendor->status}"), 'tone' => $vendor->status === 'active' ? 'teal' : 'muted'],
                ['label' => trans('app.maintenance_vendors.category'), 'value' => trans("app.status.{$vendor->service_category}")],
                ['label' => trans('app.maintenance_vendors.total_work_orders'), 'value' => (int) $vendor->work_orders_count],
                ['label' => trans('app.maintenance_vendors.active_work_orders'), 'value' => (int) $vendor->active_work_orders_count, 'tone' => 'primary'],
            ]),
            'sections' => [[
                'title' => trans('app.maintenance_vendors.contact_section'),
                'description' => trans('app.maintenance_vendors.contact_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.maintenance_vendors.contact_name'), 'value' => $vendor->contact_name],
                    ['label' => trans('app.maintenance_vendors.phone'), 'value' => $vendor->phone],
                    ['label' => trans('app.maintenance_vendors.email'), 'value' => $vendor->email],
                    ['label' => trans('app.maintenance_vendors.portfolio'), 'value' => $this->resources->localized($vendor->portfolio?->name_en, $vendor->portfolio?->name_ar)],
                    ['label' => trans('app.maintenance_vendors.notes'), 'value' => $vendor->notes],
                ]),
            ]],
            'related' => [$this->workOrders($workOrders)],
            'timeline' => $this->resources->activityTimeline($vendor),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceWorkOrder>  $workOrders
     * @return array<string, mixed>
     */
    private function workOrders(Collection $workOrders): array
    {
        $reference = trans('app.work_orders.reference');
        $request = trans('app.work_orders.request');
        $schedule = trans('app.work_orders.schedule');
        $status = trans('app.work_orders.status');

        return [
            'title' => trans('app.maintenance_vendors.work_orders'),
            'description' => trans('app.maintenance_vendors.work_orders_help'),
            'columns' => [$reference, $request, $schedule, $status],
            'rows' => $workOrders->map(fn (MaintenanceWorkOrder $workOrder): array => [
                $reference => [
                    'label' => $workOrder->reference_code,
                    'href' => route('maintenance-work-orders.show', $workOrder),
                ],
                $request => $workOrder->maintenanceRequest?->title ?: '-',
                $schedule => $workOrder->scheduled_at?->toDateTimeString() ?: '-',
                $status => trans("app.status.{$workOrder->status}"),
            ])->all(),
            'emptyText' => trans('app.maintenance_vendors.no_work_orders'),
        ];
    }
}
