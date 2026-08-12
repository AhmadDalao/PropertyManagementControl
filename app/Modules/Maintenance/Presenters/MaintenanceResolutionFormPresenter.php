<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;

final class MaintenanceResolutionFormPresenter
{
    /** @return array<string, mixed> */
    public function present(MaintenanceRequest $request): array
    {
        return [
            'layout' => 'maintenance',
            'title' => trans('app.maintenance.review_resolution_title', ['id' => $request->id]),
            'description' => trans('app.maintenance.review_resolution_help'),
            'backHref' => route('maintenance-requests.show', $request),
            'backLabel' => trans('app.maintenance.request_detail'),
            'action' => route('maintenance-requests.resolution-response.store', $request),
            'method' => 'post',
            'submitLabel' => trans('app.maintenance.submit_resolution_response'),
            'fields' => [
                [
                    'name' => 'outcome',
                    'label' => trans('app.maintenance.resolution_outcome'),
                    'type' => 'select',
                    'required' => true,
                    'section' => trans('app.maintenance.tenant_signoff'),
                    'sectionDescription' => trans('app.maintenance.tenant_signoff_help'),
                    'options' => [
                        [
                            'value' => 'confirmed',
                            'label' => trans('app.maintenance.confirm_resolved'),
                        ],
                        [
                            'value' => 'reopen',
                            'label' => trans('app.maintenance.reopen_issue'),
                        ],
                    ],
                ],
                [
                    'name' => 'note',
                    'label' => trans('app.maintenance.tenant_response_note'),
                    'type' => 'textarea',
                    'rows' => 5,
                    'help' => trans('app.maintenance.tenant_response_note_help'),
                    'section' => trans('app.maintenance.tenant_signoff'),
                ],
            ],
            'initialValues' => [
                'outcome' => 'confirmed',
                'note' => '',
            ],
        ];
    }
}
