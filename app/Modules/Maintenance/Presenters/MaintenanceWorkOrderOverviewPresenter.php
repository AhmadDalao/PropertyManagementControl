<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceWorkOrderDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class MaintenanceWorkOrderOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $users,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(MaintenanceWorkOrderDetailData $data): array
    {
        return $this->resources->detailItems([
            ['label' => trans('app.work_orders.status'), 'value' => $data->statusLabel, 'tone' => $data->statusTone],
            ['label' => trans('app.work_orders.schedule_state'), 'value' => $data->scheduleLabel, 'tone' => $data->scheduleTone],
            ['label' => trans('app.work_orders.estimated_amount'), 'value' => $data->estimatedAmount],
            ['label' => trans('app.work_orders.final_amount'), 'value' => $data->finalAmount, 'tone' => $data->finalAmount === null ? 'muted' : 'primary'],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(MaintenanceWorkOrderDetailData $data): array
    {
        $workOrder = $data->workOrder;
        $request = $data->request;
        $asset = $request->asset;
        $tenant = $request->tenantProfile;

        return [[
            'key' => 'context',
            'title' => trans('app.work_orders.context_section'),
            'description' => trans('app.work_orders.context_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.request'), 'value' => '#'.$request->id.' '.$request->title, 'href' => route('maintenance-requests.show', $request)],
                ['label' => trans('app.work_orders.property'), 'value' => $this->resources->localized($asset?->title_en, $asset?->title_ar), 'href' => $asset && PortfolioModules::enabledForUser($data->actor, 'assets') ? route('assets.show', $asset) : null],
                ['label' => trans('app.work_orders.tenant'), 'value' => $tenant?->user?->name, 'href' => $tenant && PortfolioModules::enabledForUser($data->actor, 'tenants') ? route('tenants.show', $tenant) : null],
                ['label' => trans('app.work_orders.portfolio'), 'value' => $this->resources->localized($workOrder->portfolio?->name_en, $workOrder->portfolio?->name_ar)],
                ['label' => trans('app.maintenance.category'), 'value' => trans("app.status.{$request->category}")],
                ['label' => trans('app.maintenance.priority'), 'value' => trans("app.status.{$request->priority}")],
            ]),
        ], [
            'key' => 'scope',
            'title' => trans('app.work_orders.scope_section'),
            'description' => trans('app.work_orders.scope_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.scope'), 'value' => $workOrder->scope],
                ['label' => trans('app.maintenance.issue_description'), 'value' => $request->description],
                ['label' => trans('app.maintenance.due_at'), 'value' => $request->due_at?->toDateTimeString()],
                ['label' => trans('app.work_orders.tenant_access_required'), 'value' => trans($workOrder->tenant_access_required ? 'app.common.yes' : 'app.common.no')],
            ]),
        ], [
            'key' => 'assignment',
            'title' => trans('app.work_orders.assignment_section'),
            'description' => trans('app.work_orders.assignment_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.vendor'), 'value' => $workOrder->vendor_name, 'href' => $workOrder->vendor ? route('maintenance-vendors.show', $workOrder->vendor) : null],
                ['label' => trans('app.maintenance_vendors.phone'), 'value' => $workOrder->vendor_phone],
                ['label' => trans('app.work_orders.internal_owner'), 'value' => $workOrder->assignedTo?->name, 'href' => $this->users->recordHref($data->actor, $workOrder->assignedTo)],
                ['label' => trans('app.work_orders.created_by'), 'value' => $workOrder->createdBy?->name, 'href' => $this->users->recordHref($data->actor, $workOrder->createdBy)],
            ]),
        ], [
            'key' => 'schedule',
            'title' => trans('app.work_orders.schedule_section'),
            'description' => trans('app.work_orders.schedule_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.schedule_state'), 'value' => $data->scheduleLabel],
                ['label' => trans('app.work_orders.scheduled_at'), 'value' => $workOrder->scheduled_at?->toDateTimeString()],
                ['label' => trans('app.work_orders.tenant_access_required'), 'value' => trans($workOrder->tenant_access_required ? 'app.common.yes' : 'app.common.no')],
                ['label' => trans('app.maintenance.due_at'), 'value' => $request->due_at?->toDateTimeString()],
            ]),
        ], [
            'key' => 'cost',
            'title' => trans('app.work_orders.cost_section'),
            'description' => trans('app.work_orders.cost_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.estimated_amount'), 'value' => $data->estimatedAmount],
                ['label' => trans('app.work_orders.final_amount'), 'value' => $data->finalAmount],
                ['label' => trans('app.work_orders.variance'), 'value' => $data->varianceAmount],
                ['label' => trans('app.work_orders.currency'), 'value' => $workOrder->currency],
            ]),
        ], [
            'key' => 'completion',
            'title' => trans('app.work_orders.completion_section'),
            'description' => trans('app.work_orders.completion_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.status'), 'value' => $data->statusLabel],
                ['label' => trans('app.work_orders.completed_at'), 'value' => $workOrder->completed_at?->toDateTimeString()],
                ['label' => trans('app.work_orders.completion_notes'), 'value' => $workOrder->completion_notes],
                ['label' => trans('app.work_orders.request_status'), 'value' => trans("app.status.{$request->status}")],
                ['label' => trans('app.maintenance.tenant_confirmation'), 'value' => $request->tenant_confirmed_at ? trans('app.maintenance.confirmed') : ($request->status === 'resolved' ? trans('app.maintenance.pending_confirmation') : trans('app.maintenance.not_ready'))],
            ]),
        ]];
    }
}
