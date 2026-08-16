<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

class MaintenanceDetailOverviewPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $stats = [
            [
                'label' => trans('app.maintenance.status'),
                'value' => trans("app.status.{$request->status}"),
                'tone' => $request->status === 'resolved' ? 'teal' : 'primary',
            ],
            [
                'label' => trans('app.maintenance.priority'),
                'value' => trans("app.status.{$request->priority}"),
                'tone' => in_array($request->priority, ['high', 'urgent'], true) ? 'danger' : 'muted',
            ],
            ['label' => trans('app.maintenance.updates'), 'value' => $data->updates->count()],
            ['label' => trans('app.maintenance.photos'), 'value' => $data->attachments->count()],
            [
                'label' => trans('app.maintenance.work_orders'),
                'value' => $data->workOrders->count(),
                'tone' => $data->workOrders->whereIn('status', ['scheduled', 'in_progress'])->isNotEmpty()
                    ? 'primary'
                    : 'muted',
            ],
            [
                'label' => trans('app.maintenance.tenant_confirmation'),
                'value' => $request->status === 'resolved'
                    ? trans($request->tenant_confirmed_at
                        ? 'app.maintenance.confirmed'
                        : 'app.maintenance.pending_confirmation')
                    : trans('app.maintenance.not_ready'),
                'tone' => $request->tenant_confirmed_at ? 'teal' : 'muted',
            ],
        ];

        if (
            ! $data->tenantMode
            && PortfolioModules::enabledForUser($data->actor, 'expenses')
        ) {
            $stats[] = [
                'label' => trans('app.maintenance.cost'),
                'value' => number_format($data->postedExpenseTotal, 2),
                'tone' => 'primary',
            ];
        }

        return [
            'header' => [
                'eyebrow' => trans('app.maintenance.detail_eyebrow'),
                'title' => '#'.$request->id.' '.$request->title,
                'description' => implode(' · ', [
                    trans("app.status.{$request->category}"),
                    trans("app.status.{$request->priority}"),
                    trans("app.status.{$request->status}"),
                ]),
                'backHref' => route('maintenance-requests.index'),
                'backLabel' => trans('app.maintenance.queue_title'),
                'actions' => $this->actions($data),
            ],
            'stats' => $this->resources->detailItems($stats),
            'requestContext' => $this->requestContext($data),
            'serviceContext' => $this->serviceContext($data),
            'sections' => [[
                'title' => trans('app.maintenance.request_context'),
                'description' => trans('app.maintenance.request_context_help'),
                'items' => $this->context($data),
            ]],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function requestContext(MaintenanceDetailData $data): array
    {
        $request = $data->request;

        return $this->resources->detailItems([
            ['label' => trans('app.maintenance.request_number'), 'value' => '#'.$request->id],
            ['label' => trans('app.maintenance.issue_title'), 'value' => $request->title],
            ['label' => trans('app.maintenance.tenant'), 'value' => $request->tenantProfile?->user?->name],
            [
                'label' => trans('app.maintenance.property_unit'),
                'value' => $this->resources->localized(
                    $request->asset?->title_en,
                    $request->asset?->title_ar,
                ),
            ],
            ['label' => trans('app.maintenance.reported'), 'value' => $request->requested_at?->toDateTimeString()],
            ['label' => trans('app.maintenance.category'), 'value' => trans("app.status.{$request->category}")],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function serviceContext(MaintenanceDetailData $data): array
    {
        $request = $data->request;

        return $this->resources->detailItems([
            ['label' => trans('app.maintenance.assigned_to'), 'value' => $request->assignedTo?->name],
            ['label' => trans('app.maintenance.due_at'), 'value' => $request->due_at?->toDateTimeString()],
            ['label' => trans('app.maintenance.issue_description'), 'value' => $request->description],
            ['label' => trans('app.maintenance.resolution_summary'), 'value' => $request->resolution_summary],
            [
                'label' => trans('app.maintenance.tenant_confirmation'),
                'value' => $request->tenant_confirmed_at
                    ? trans('app.maintenance.confirmed_at', [
                        'date' => $request->tenant_confirmed_at->toDateTimeString(),
                    ])
                    : ($request->status === 'resolved'
                        ? trans('app.maintenance.pending_confirmation')
                        : null),
            ],
            ['label' => trans('app.maintenance.tenant_response_note'), 'value' => $request->tenant_confirmation_note],
            [
                'label' => trans('app.maintenance.internal_notes'),
                'value' => $data->tenantMode ? null : $request->internal_notes,
            ],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function actions(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $actions = [];
        $actions[] = [
            'label' => trans('app.maintenance.add_photos'),
            'href' => route('maintenance-requests.attachments.create', $request),
            'variant' => $data->tenantMode ? 'primary' : 'light',
        ];

        if ($request->status === 'resolved') {
            if (! $data->tenantMode) {
                $actions[] = [
                    'label' => trans('app.maintenance.download_service_report_word'),
                    'href' => route('maintenance-requests.service-report.word', $request),
                    'variant' => 'primary',
                    'external' => true,
                ];
            }

            $actions[] = [
                'label' => trans('app.maintenance.download_service_report'),
                'href' => route('maintenance-requests.service-report', $request),
                'variant' => 'light',
                'external' => true,
            ];
        }

        if (
            ! $data->tenantMode
            && ! in_array($request->status, ['resolved', 'cancelled'], true)
            && $data->workOrders
                ->whereIn('status', ['draft', 'scheduled', 'in_progress'])
                ->isEmpty()
        ) {
            $actions[] = [
                'label' => trans('app.work_orders.create'),
                'href' => route('maintenance-requests.work-orders.create', $request),
                'variant' => 'primary',
            ];
        }

        if (
            ! $data->tenantMode
            && $request->asset
            && PortfolioModules::enabledForUser($data->actor, 'assets')
        ) {
            $actions[] = [
                'label' => trans('app.maintenance.open_asset'),
                'href' => route('assets.show', $request->asset),
                'variant' => 'light',
            ];
        }

        return $actions;
    }

    /** @return array<int, array<string, mixed>> */
    private function context(MaintenanceDetailData $data): array
    {
        $request = $data->request;

        return $this->resources->detailItems([
            [
                'label' => trans('app.maintenance.asset'),
                'value' => $this->resources->localized($request->asset?->title_en, $request->asset?->title_ar),
                'href' => ! $data->tenantMode
                    && $request->asset
                    && PortfolioModules::enabledForUser($data->actor, 'assets')
                        ? route('assets.show', $request->asset)
                        : null,
            ],
            [
                'label' => trans('app.maintenance.tenant'),
                'value' => $request->tenantProfile?->user?->name,
                'href' => ! $data->tenantMode
                    && $request->tenantProfile
                    && PortfolioModules::enabledForUser($data->actor, 'tenants')
                    ? route('tenants.show', $request->tenantProfile)
                    : null,
            ],
            [
                'label' => trans('app.maintenance.lease'),
                'value' => $request->lease?->code,
                'href' => $request->lease
                    && PortfolioModules::enabledForUser($data->actor, 'leases')
                        ? route('leases.show', $request->lease)
                        : null,
            ],
            ['label' => trans('app.maintenance.submitted_by'), 'value' => $request->submittedBy?->name],
            ['label' => trans('app.maintenance.assigned_to'), 'value' => $request->assignedTo?->name],
            ['label' => trans('app.maintenance.requested_at'), 'value' => $request->requested_at?->toDateTimeString()],
            ['label' => trans('app.maintenance.due_at'), 'value' => $request->due_at?->toDateTimeString()],
            ['label' => trans('app.maintenance.resolved_at'), 'value' => $request->resolved_at?->toDateTimeString()],
            ['label' => trans('app.maintenance.resolved_by'), 'value' => $request->resolvedBy?->name],
            ['label' => trans('app.maintenance.resolution_summary'), 'value' => $request->resolution_summary],
            [
                'label' => trans('app.maintenance.tenant_confirmation'),
                'value' => $request->tenant_confirmed_at
                    ? trans('app.maintenance.confirmed_at', [
                        'date' => $request->tenant_confirmed_at->toDateTimeString(),
                    ])
                    : ($request->status === 'resolved'
                        ? trans('app.maintenance.pending_confirmation')
                        : null),
            ],
            [
                'label' => trans('app.maintenance.tenant_response_note'),
                'value' => $request->tenant_confirmation_note,
            ],
            ['label' => trans('app.maintenance.issue_description'), 'value' => $request->description],
            [
                'label' => trans('app.maintenance.internal_notes'),
                'value' => $data->tenantMode ? null : $request->internal_notes,
            ],
        ]);
    }
}
