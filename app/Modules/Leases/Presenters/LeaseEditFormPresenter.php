<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Lease;
use App\Modules\Leases\Support\LeaseTransitionGuard;
use App\Modules\Shared\ResourcePresenter;

final class LeaseEditFormPresenter
{
    public function __construct(
        private readonly LeaseFormFieldsPresenter $fields,
        private readonly LeaseTransitionGuard $transitions,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Lease $lease): array
    {
        return [
            'layout' => 'lease',
            'mode' => 'edit',
            'title' => trans('app.leases.edit_lease', ['code' => $lease->code]),
            'description' => trans('app.leases.edit_description'),
            'backHref' => route('leases.show', $lease),
            'backLabel' => trans('app.leases.lease_detail'),
            'action' => route('leases.update', $lease),
            'method' => 'put',
            'submitLabel' => trans('app.leases.update_lease'),
            'fields' => $this->resources->sectionFields([
                ['name' => 'status', 'label' => trans('app.leases.status'), 'type' => 'select', 'required' => true, 'options' => $this->fields->statusOptions($this->transitions->allowedStatuses($lease->status))],
                ['name' => 'signed_at', 'label' => trans('app.leases.signed_date'), 'type' => 'date'],
                ['name' => 'renewal_notice_days', 'label' => trans('app.leases.renewal_notice_days'), 'type' => 'number', 'min' => 0, 'max' => 365, 'required' => true, 'help' => trans('app.leases.renewal_notice_days_help')],
                ['name' => 'notes', 'label' => trans('app.leases.notes'), 'type' => 'textarea', 'rows' => 4],
                ['name' => 'terms_en', 'label' => trans('app.leases.terms_en'), 'type' => 'textarea', 'rows' => 6, 'help' => trans('app.leases.terms_help')],
                ['name' => 'terms_ar', 'label' => trans('app.leases.terms_ar'), 'type' => 'textarea', 'rows' => 6, 'help' => trans('app.leases.terms_help')],
            ], $this->sections()),
            'initialValues' => [
                'status' => $lease->status,
                'signed_at' => $lease->signed_at?->toDateString() ?? '',
                'renewal_notice_days' => (int) $lease->renewal_notice_days,
                'notes' => $lease->notes ?? '',
                'terms_en' => data_get($lease->terms_json, 'en', ''),
                'terms_ar' => data_get($lease->terms_json, 'ar', ''),
            ],
        ];
    }

    /** @return array<string, array{description:string,fields:array<int, string>}> */
    private function sections(): array
    {
        return [
            trans('app.leases.contract_controls') => [
                'description' => trans('app.leases.contract_controls_help'),
                'fields' => ['status', 'signed_at', 'renewal_notice_days'],
            ],
            trans('app.leases.internal_notes') => [
                'description' => trans('app.leases.internal_notes_help'),
                'fields' => ['notes'],
            ],
            trans('app.leases.approved_terms') => [
                'description' => trans('app.leases.approved_terms_help'),
                'fields' => ['terms_en', 'terms_ar'],
            ],
        ];
    }
}
