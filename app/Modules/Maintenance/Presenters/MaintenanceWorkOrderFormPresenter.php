<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Queries\MaintenanceFormOptionsQuery;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderAccess;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderOptions;
use App\Modules\Shared\ResourcePresenter;

final class MaintenanceWorkOrderFormPresenter
{
    public function __construct(
        private readonly MaintenanceWorkOrderAccess $access,
        private readonly MaintenanceFormOptionsQuery $maintenanceOptions,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function forCreate(MaintenanceRequest $request, User $actor): array
    {
        $this->access->ensureCanManageRequest($actor, $request);
        abort_if(
            in_array($request->status, ['resolved', 'cancelled'], true),
            409,
            trans('app.errors.work_order_request_closed'),
        );
        abort_if(
            $request->workOrders()
                ->whereIn('status', ['draft', 'scheduled', 'in_progress'])
                ->exists(),
            409,
            trans('app.errors.work_order_active_exists'),
        );

        return $this->present($actor, $request);
    }

    /** @return array<string, mixed> */
    public function forEdit(MaintenanceWorkOrder $workOrder, User $actor): array
    {
        $this->access->ensureCanManage($actor, $workOrder);
        $workOrder->loadMissing('maintenanceRequest');

        if ($workOrder->maintenanceRequest === null) {
            abort(404);
        }

        return $this->present($actor, $workOrder->maintenanceRequest, $workOrder);
    }

    /** @return array<string, mixed> */
    private function present(
        User $actor,
        MaintenanceRequest $request,
        ?MaintenanceWorkOrder $workOrder = null,
    ): array {
        $creating = $workOrder === null;
        $vendors = MaintenanceVendor::query()
            ->where('portfolio_id', $request->portfolio_id)
            ->where(function ($query) use ($workOrder): void {
                $query->where('status', 'active');

                if ($workOrder && $workOrder->vendor_id) {
                    $query->orWhere('id', $workOrder->vendor_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'service_category', 'status']);
        $managers = $this->maintenanceOptions->managers($actor);
        $statuses = $creating
            ? ['draft', 'scheduled']
            : (MaintenanceWorkOrderOptions::TRANSITIONS[$workOrder->status] ?? [$workOrder->status]);
        $initial = $workOrder ? [
            'vendor_id' => (string) ($workOrder->vendor_id ?? ''),
            'assigned_to_user_id' => (string) ($workOrder->assigned_to_user_id
                ?: $request->assigned_to_user_id
                ?: ''),
            'status' => $workOrder->status,
            'scheduled_at' => $workOrder->scheduled_at?->format('Y-m-d\TH:i') ?? '',
            'estimated_amount' => $workOrder->estimated_amount ?? '',
            'final_amount' => $workOrder->final_amount ?? '',
            'scope' => $workOrder->scope,
            'completion_notes' => $workOrder->completion_notes ?? '',
            'tenant_access_required' => $workOrder->tenant_access_required,
        ] : [
            'vendor_id' => '',
            'assigned_to_user_id' => (string) ($request->assigned_to_user_id ?: ''),
            'status' => 'draft',
            'scheduled_at' => '',
            'estimated_amount' => '',
            'final_amount' => '',
            'scope' => $request->description,
            'completion_notes' => '',
            'tenant_access_required' => false,
        ];
        $fields = [
            [
                'name' => 'vendor_id',
                'label' => trans('app.work_orders.vendor'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.work_orders.vendor_help'),
                'options' => [
                    ['value' => '', 'label' => trans('app.work_orders.choose_vendor')],
                    ...$vendors->map(fn (MaintenanceVendor $vendor): array => [
                        'value' => $vendor->id,
                        'label' => $vendor->name.' · '.trans("app.status.{$vendor->service_category}"),
                    ])->all(),
                ],
            ],
            [
                'name' => 'assigned_to_user_id',
                'label' => trans('app.work_orders.internal_owner'),
                'type' => 'select',
                'help' => trans('app.work_orders.internal_owner_help'),
                'options' => [
                    ['value' => '', 'label' => trans('app.work_orders.no_internal_owner')],
                    ...$managers->map(fn (User $manager): array => [
                        'value' => $manager->id,
                        'label' => $manager->name,
                    ])->all(),
                ],
            ],
            [
                'name' => 'status',
                'label' => trans('app.work_orders.status'),
                'type' => 'select',
                'required' => true,
                'options' => collect($statuses)->map(fn (string $status): array => [
                    'value' => $status,
                    'label' => trans("app.status.{$status}"),
                ])->all(),
            ],
            [
                'name' => 'scheduled_at',
                'label' => trans('app.work_orders.scheduled_at'),
                'type' => 'datetime-local',
                'help' => trans('app.work_orders.scheduled_at_help'),
            ],
            [
                'name' => 'estimated_amount',
                'label' => trans('app.work_orders.estimated_amount'),
                'type' => 'number',
                'step' => '0.01',
                'min' => 0,
                'max' => 999999999999.99,
            ],
            [
                'name' => 'scope',
                'label' => trans('app.work_orders.scope'),
                'type' => 'textarea',
                'rows' => 5,
                'required' => true,
                'help' => trans('app.work_orders.scope_help'),
            ],
            [
                'name' => 'tenant_access_required',
                'label' => trans('app.work_orders.tenant_access_required'),
                'type' => 'checkbox',
                'help' => trans('app.work_orders.tenant_access_help'),
            ],
        ];

        if (! $creating) {
            $fields[] = [
                'name' => 'final_amount',
                'label' => trans('app.work_orders.final_amount'),
                'type' => 'number',
                'step' => '0.01',
                'min' => 0,
                'max' => 999999999999.99,
                'help' => trans('app.work_orders.final_amount_help'),
            ];
            $fields[] = [
                'name' => 'completion_notes',
                'label' => trans('app.work_orders.completion_notes'),
                'type' => 'textarea',
                'rows' => 4,
                'help' => trans('app.work_orders.completion_notes_help'),
            ];
        }

        return [
            'title' => trans($creating ? 'app.work_orders.create' : 'app.work_orders.edit', [
                'reference' => $workOrder?->reference_code,
            ]),
            'description' => trans($creating
                ? 'app.work_orders.create_description'
                : 'app.work_orders.edit_description'),
            'backHref' => $workOrder
                ? route('maintenance-work-orders.show', $workOrder)
                : route('maintenance-requests.show', [$request, 'tab' => 'related']),
            'backLabel' => trans('app.work_orders.back_to_request'),
            'headerActions' => $vendors->isEmpty() ? [[
                'label' => trans('app.maintenance_vendors.create'),
                'href' => route('maintenance-vendors.create', [
                    'portfolio_id' => $request->portfolio_id,
                ]),
                'variant' => 'light',
            ]] : [],
            'action' => $workOrder
                ? route('maintenance-work-orders.update', $workOrder)
                : route('maintenance-requests.work-orders.store', $request),
            'method' => $workOrder ? 'put' : 'post',
            'submitLabel' => trans($workOrder
                ? 'app.work_orders.update'
                : 'app.work_orders.create'),
            'fields' => $this->resources->sectionFields($fields, [
                trans('app.work_orders.assignment_section') => [
                    'description' => trans('app.work_orders.assignment_section_help'),
                    'fields' => ['vendor_id', 'assigned_to_user_id', 'status'],
                ],
                trans('app.work_orders.schedule_section') => [
                    'description' => trans('app.work_orders.schedule_section_help'),
                    'fields' => ['scheduled_at', 'tenant_access_required'],
                ],
                trans('app.work_orders.work_section') => [
                    'description' => trans('app.work_orders.work_section_help'),
                    'fields' => ['scope', 'estimated_amount', 'final_amount', 'completion_notes'],
                ],
            ]),
            'initialValues' => $initial,
        ];
    }
}
