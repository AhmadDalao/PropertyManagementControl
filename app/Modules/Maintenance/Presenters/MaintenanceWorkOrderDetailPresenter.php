<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderAccess;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final class MaintenanceWorkOrderDetailPresenter
{
    public function __construct(
        private readonly MaintenanceWorkOrderAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceWorkOrder $workOrder, User $actor): array
    {
        $this->access->ensureCanManage($actor, $workOrder);
        $workOrder->loadMissing([
            'portfolio',
            'maintenanceRequest.asset',
            'maintenanceRequest.tenantProfile.user',
            'vendor',
            'assignedTo',
            'createdBy',
        ]);
        $request = $workOrder->maintenanceRequest;

        if ($request === null) {
            abort(404);
        }

        return [
            'header' => [
                'eyebrow' => trans('app.work_orders.detail_eyebrow'),
                'title' => $workOrder->reference_code,
                'description' => implode(' · ', [
                    $workOrder->vendor_name,
                    trans("app.status.{$workOrder->status}"),
                ]),
                'backHref' => route('maintenance-requests.show', [$request, 'tab' => 'related']),
                'backLabel' => trans('app.work_orders.back_to_request'),
                'actions' => [[
                    'label' => trans('app.work_orders.edit'),
                    'href' => route('maintenance-work-orders.edit', $workOrder),
                    'variant' => 'primary',
                ]],
            ],
            'workflow' => $this->workflow($workOrder, $actor),
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.work_orders.status'), 'value' => trans("app.status.{$workOrder->status}"), 'tone' => $this->tone($workOrder->status)],
                ['label' => trans('app.work_orders.schedule'), 'value' => $workOrder->scheduled_at?->toDateTimeString()],
                ['label' => trans('app.work_orders.estimated_amount'), 'value' => $this->money($workOrder->estimated_amount, $workOrder->currency)],
                ['label' => trans('app.work_orders.final_amount'), 'value' => $this->money($workOrder->final_amount, $workOrder->currency), 'tone' => 'primary'],
            ]),
            'sections' => [
                [
                    'title' => trans('app.work_orders.assignment_section'),
                    'description' => trans('app.work_orders.assignment_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.work_orders.vendor'), 'value' => $workOrder->vendor_name, 'href' => $workOrder->vendor ? route('maintenance-vendors.show', $workOrder->vendor) : null],
                        ['label' => trans('app.maintenance_vendors.phone'), 'value' => $workOrder->vendor_phone],
                        ['label' => trans('app.work_orders.internal_owner'), 'value' => $workOrder->assignedTo?->name],
                        ['label' => trans('app.work_orders.created_by'), 'value' => $workOrder->createdBy?->name],
                        ['label' => trans('app.work_orders.request'), 'value' => '#'.$request->id.' '.$request->title, 'href' => route('maintenance-requests.show', $request)],
                    ]),
                ],
                [
                    'title' => trans('app.work_orders.work_section'),
                    'description' => trans('app.work_orders.work_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.work_orders.scope'), 'value' => $workOrder->scope],
                        ['label' => trans('app.work_orders.tenant_access_required'), 'value' => trans($workOrder->tenant_access_required ? 'app.common.yes' : 'app.common.no')],
                        ['label' => trans('app.work_orders.completed_at'), 'value' => $workOrder->completed_at?->toDateTimeString()],
                        ['label' => trans('app.work_orders.completion_notes'), 'value' => $workOrder->completion_notes],
                    ]),
                ],
            ],
            'timeline' => $this->resources->activityTimeline($workOrder),
        ];
    }

    /** @return array<string, mixed> */
    private function workflow(MaintenanceWorkOrder $workOrder, User $actor): array
    {
        $actions = [[
            'label' => trans('app.work_orders.update'),
            'href' => route('maintenance-work-orders.edit', $workOrder),
            'variant' => 'primary',
        ]];

        if (
            $workOrder->status === 'completed'
            && (float) $workOrder->final_amount > 0
            && PortfolioModules::enabledForUser($actor, 'expenses')
        ) {
            $actions[] = [
                'label' => trans('app.work_orders.record_expense'),
                'href' => route('expenses.create', [
                    'portfolio_id' => $workOrder->portfolio_id,
                    'asset_id' => $workOrder->maintenanceRequest?->asset_id,
                    'maintenance_request_id' => $workOrder->maintenance_request_id,
                    'vendor_name' => $workOrder->vendor_name,
                    'amount' => $workOrder->final_amount,
                    'title' => trans('app.work_orders.expense_title', [
                        'reference' => $workOrder->reference_code,
                    ]),
                    'description' => $workOrder->completion_notes,
                ]),
                'variant' => 'secondary',
            ];
        }

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.work_orders.workflow_{$workOrder->status}_title"),
            'description' => trans("app.work_orders.workflow_{$workOrder->status}_description"),
            'status' => trans("app.status.{$workOrder->status}"),
            'tone' => $this->tone($workOrder->status),
            'icon' => 'bi-clipboard2-check',
            'actions' => $actions,
        ];
    }

    private function tone(string $status): string
    {
        return match ($status) {
            'completed' => 'teal',
            'cancelled' => 'danger',
            'scheduled', 'in_progress' => 'primary',
            default => 'muted',
        };
    }

    private function money(mixed $amount, string $currency): ?string
    {
        return $amount === null ? null : number_format((float) $amount, 2).' '.$currency;
    }
}
