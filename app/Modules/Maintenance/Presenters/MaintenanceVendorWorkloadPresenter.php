<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceWorkOrder;
use App\Modules\Maintenance\Data\MaintenanceVendorDetailData;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Support\Collection;

final class MaintenanceVendorWorkloadPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceVendorDetailData $data): array
    {
        return [
            'open' => $this->records($data->openWorkOrders),
            'history' => $this->records($data->historyWorkOrders),
            'allHref' => route('maintenance-work-orders.index', ['vendor_id' => $data->vendor->id]),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceWorkOrder>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function records(Collection $orders): array
    {
        return $orders->map(function (MaintenanceWorkOrder $order): array {
            $request = $order->maintenanceRequest;
            $schedule = $this->schedule($order);

            return [
                'id' => $order->id,
                'reference' => $order->reference_code,
                'href' => route('maintenance-work-orders.show', $order),
                'status' => trans("app.status.{$order->status}"),
                'statusTone' => $this->statusTone($order->status),
                'request' => $request?->title ?: trans('app.maintenance_vendors.not_recorded'),
                'property' => $this->resources->localized($request?->asset?->title_en, $request?->asset?->title_ar)
                    ?: trans('app.work_orders.no_property'),
                'propertyCode' => $request?->asset?->code,
                'tenant' => $request?->tenantProfile?->user?->name ?: trans('app.work_orders.no_tenant'),
                'assignedTo' => $order->assignedTo?->name ?: trans('app.work_orders.no_internal_owner'),
                'scheduledAt' => $order->scheduled_at?->toDateTimeString(),
                'schedule' => trans("app.work_orders.schedule_state_{$schedule}"),
                'scheduleTone' => $this->scheduleTone($schedule),
                'estimated' => $this->money($order->estimated_amount, $order->currency),
                'final' => $this->money($order->final_amount, $order->currency),
                'scope' => $order->scope,
            ];
        })->all();
    }

    private function schedule(MaintenanceWorkOrder $order): string
    {
        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            return $order->status;
        }

        if ($order->scheduled_at === null) {
            return 'unscheduled';
        }

        if ($order->scheduled_at->isPast()) {
            return 'overdue';
        }

        return $order->scheduled_at->isToday() ? 'today' : 'upcoming';
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'completed' => 'teal',
            'cancelled' => 'danger',
            'scheduled', 'in_progress' => 'primary',
            default => 'muted',
        };
    }

    private function scheduleTone(string $schedule): string
    {
        return match ($schedule) {
            'completed' => 'teal',
            'overdue', 'cancelled' => 'danger',
            'today', 'upcoming' => 'primary',
            default => 'muted',
        };
    }

    private function money(mixed $amount, string $currency): ?string
    {
        return $amount === null ? null : number_format((float) $amount, 2).' '.$currency;
    }
}
