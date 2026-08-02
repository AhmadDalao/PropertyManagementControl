<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Maintenance\Queries\MaintenanceWorkOrderIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class MaintenanceWorkOrderWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly MaintenanceWorkOrderIndexQuery $workOrders,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('maintenance-work-orders', [
            trans('app.work_orders.reference'),
            trans('app.work_orders.request'),
            trans('app.work_orders.property'),
            trans('app.work_orders.tenant'),
            trans('app.work_orders.vendor'),
            trans('app.work_orders.internal_owner'),
            trans('app.work_orders.status'),
            trans('app.work_orders.scheduled_at'),
            trans('app.work_orders.estimated_amount'),
            trans('app.work_orders.final_amount'),
            trans('app.work_orders.currency'),
            trans('app.work_orders.access'),
            trans('app.work_orders.scope'),
            trans('app.work_orders.completed_at'),
        ], $this->workOrders->forExport($request, $actor), fn (MaintenanceWorkOrder $order): array => [
            $order->reference_code,
            $order->maintenanceRequest?->title,
            $this->workbook->localized($order->maintenanceRequest?->asset, 'title_en', 'title_ar'),
            $order->maintenanceRequest?->tenantProfile?->user?->name,
            $order->vendor_name,
            $order->assignedTo?->name,
            $this->workbook->option($order->status),
            $this->workbook->date($order->scheduled_at, true),
            $order->estimated_amount,
            $order->final_amount,
            $order->currency,
            $this->workbook->yesNo($order->tenant_access_required),
            $order->scope,
            $this->workbook->date($order->completed_at, true),
        ]);
    }
}
