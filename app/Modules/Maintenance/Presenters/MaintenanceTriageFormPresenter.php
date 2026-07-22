<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Queries\MaintenanceFormOptionsQuery;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceOptions;

class MaintenanceTriageFormPresenter
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceFormOptionsQuery $options,
        private readonly MaintenanceFormOptionPresenter $presentOptions,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, MaintenanceRequest $request): array
    {
        if ($actor->hasRole('tenant')) {
            return [
                'title' => trans('app.maintenance.add_comment'),
                'description' => trans('app.maintenance.add_comment_help'),
                'backHref' => route('maintenance-requests.show', $request),
                'backLabel' => trans('app.maintenance.request_detail'),
                'action' => route('maintenance-requests.update', $request),
                'method' => 'put',
                'submitLabel' => trans('app.maintenance.add_comment'),
                'fields' => [[
                    'name' => 'comment',
                    'label' => trans('app.maintenance.comment'),
                    'type' => 'textarea',
                    'required' => true,
                ]],
                'initialValues' => ['comment' => ''],
            ];
        }

        $this->access->ensureManager($actor);

        return [
            'title' => trans('app.maintenance.triage_request', ['id' => $request->id]),
            'description' => trans('app.maintenance.triage_request_help'),
            'backHref' => route('maintenance-requests.show', $request),
            'backLabel' => trans('app.maintenance.request_detail'),
            'action' => route('maintenance-requests.update', $request),
            'method' => 'put',
            'submitLabel' => trans('app.maintenance.update_request'),
            'fields' => [
                ['name' => 'assigned_to_user_id', 'label' => trans('app.maintenance.assignee'), 'type' => 'select', 'options' => $this->presentOptions->managers($this->options->managers($actor))],
                ['name' => 'priority', 'label' => trans('app.maintenance.priority'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::PRIORITIES)],
                ['name' => 'status', 'label' => trans('app.maintenance.status'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::STATUSES)],
                ['name' => 'internal_notes', 'label' => trans('app.maintenance.internal_notes'), 'type' => 'textarea'],
                ['name' => 'comment', 'label' => trans('app.maintenance.update_comment'), 'type' => 'textarea'],
                ['name' => 'is_public_comment', 'label' => trans('app.maintenance.show_to_tenant'), 'type' => 'checkbox'],
            ],
            'initialValues' => [
                'assigned_to_user_id' => (string) ($request->assigned_to_user_id ?? ''),
                'priority' => $request->priority,
                'status' => $request->status,
                'internal_notes' => $request->internal_notes ?? '',
                'comment' => '',
                'is_public_comment' => false,
            ],
        ];
    }
}
