<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Lease;
use App\Modules\Leases\Support\LeaseTransitionGuard;

final class LeaseEditFormPresenter
{
    public function __construct(
        private readonly LeaseFormFieldsPresenter $fields,
        private readonly LeaseTransitionGuard $transitions,
    ) {}

    /** @return array<string, mixed> */
    public function present(Lease $lease): array
    {
        return [
            'title' => trans('app.leases.edit_lease', ['code' => $lease->code]),
            'description' => trans('app.leases.edit_description'),
            'backHref' => route('leases.show', $lease),
            'backLabel' => trans('app.leases.lease_detail'),
            'action' => route('leases.update', $lease),
            'method' => 'put',
            'submitLabel' => trans('app.leases.update_lease'),
            'fields' => [
                ['name' => 'status', 'label' => trans('app.leases.status'), 'type' => 'select', 'required' => true, 'options' => $this->fields->statusOptions($this->transitions->allowedStatuses($lease->status))],
                ['name' => 'signed_at', 'label' => trans('app.leases.signed_date'), 'type' => 'date'],
                ['name' => 'notes', 'label' => trans('app.leases.notes'), 'type' => 'textarea', 'rows' => 4],
                ['name' => 'terms_en', 'label' => trans('app.leases.terms_en'), 'type' => 'textarea', 'rows' => 6, 'help' => trans('app.leases.terms_help')],
                ['name' => 'terms_ar', 'label' => trans('app.leases.terms_ar'), 'type' => 'textarea', 'rows' => 6, 'help' => trans('app.leases.terms_help')],
            ],
            'initialValues' => [
                'status' => $lease->status,
                'signed_at' => $lease->signed_at?->toDateString() ?? '',
                'notes' => $lease->notes ?? '',
                'terms_en' => data_get($lease->terms_json, 'en', ''),
                'terms_ar' => data_get($lease->terms_json, 'ar', ''),
            ],
        ];
    }
}
