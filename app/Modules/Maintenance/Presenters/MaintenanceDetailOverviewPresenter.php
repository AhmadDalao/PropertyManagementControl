<?php

namespace App\Modules\Maintenance\Presenters;

use App\Modules\Maintenance\Data\MaintenanceDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Support\PortfolioModules;

class MaintenanceDetailOverviewPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceDetailData $data): array
    {
        $request = $data->request;

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
            'stats' => $this->resources->detailItems([
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
                [
                    'label' => trans('app.maintenance.cost'),
                    'value' => $data->tenantMode ? null : number_format($data->postedExpenseTotal, 2),
                    'tone' => 'primary',
                ],
            ]),
            'sections' => [[
                'title' => trans('app.maintenance.request_context'),
                'description' => trans('app.maintenance.request_context_help'),
                'items' => $this->context($data),
            ]],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function actions(MaintenanceDetailData $data): array
    {
        $request = $data->request;
        $actions = [];

        if (
            ! $data->tenantMode
            && $request->asset
            && PortfolioModules::enabledForUser($data->actor, 'assets')
        ) {
            $actions[] = [
                'label' => trans('app.maintenance.open_asset'),
                'href' => route('assets.show', $request->asset),
                'variant' => 'secondary',
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
            ['label' => trans('app.maintenance.issue_description'), 'value' => $request->description],
            [
                'label' => trans('app.maintenance.internal_notes'),
                'value' => $data->tenantMode ? null : $request->internal_notes,
            ],
        ]);
    }
}
