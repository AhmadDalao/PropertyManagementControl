<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\Leases\Data\LeaseFormData;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\ResourcePresenter;

final class LeaseFormFieldsPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function create(LeaseFormData $data): array
    {
        $fields = [];

        if ($data->actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.leases.portfolio'),
                'type' => 'select',
                'required' => true,
                'options' => $data->portfolios,
                'reloadOnChange' => ['queryKey' => 'portfolio_id'],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'tenant_profile_id', 'label' => trans('app.leases.tenant'), 'type' => 'select', 'required' => true, 'options' => $this->availableOptions($data->tenants, trans('app.leases.no_active_tenants'))],
            ['name' => 'asset_id', 'label' => trans('app.leases.rentable_asset'), 'type' => 'select', 'required' => true, 'options' => $this->availableOptions($data->assets, trans('app.leases.no_available_assets'))],
            ['name' => 'status', 'label' => trans('app.leases.status'), 'type' => 'select', 'required' => true, 'options' => $this->statusOptions(LeaseOptions::CREATE_STATUSES)],
            ['name' => 'payment_frequency', 'label' => trans('app.leases.payment_frequency'), 'type' => 'select', 'required' => true, 'options' => $this->frequencyOptions()],
            ['name' => 'started_at', 'label' => trans('app.leases.start_date'), 'type' => 'date', 'required' => true],
            ['name' => 'ends_at', 'label' => trans('app.leases.end_date'), 'type' => 'date', 'required' => true],
            ['name' => 'signed_at', 'label' => trans('app.leases.signed_date'), 'type' => 'date'],
            ['name' => 'rent_amount', 'label' => trans('app.leases.rent_amount'), 'type' => 'number', 'min' => 0, 'step' => '0.01', 'required' => true],
            ['name' => 'deposit_amount', 'label' => trans('app.leases.deposit'), 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'tax_amount', 'label' => trans('app.leases.tax'), 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'discount_amount', 'label' => trans('app.leases.discount'), 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'currency', 'label' => trans('app.leases.currency'), 'required' => true, 'max' => 3],
            ['name' => 'billing_day', 'label' => trans('app.leases.billing_day'), 'type' => 'number', 'min' => 1, 'max' => 31],
            ['name' => 'notes', 'label' => trans('app.leases.notes'), 'type' => 'textarea', 'rows' => 4],
            ['name' => 'terms_en', 'label' => trans('app.leases.terms_en'), 'type' => 'textarea', 'rows' => 6, 'help' => trans('app.leases.terms_help')],
            ['name' => 'terms_ar', 'label' => trans('app.leases.terms_ar'), 'type' => 'textarea', 'rows' => 6, 'help' => trans('app.leases.terms_help')],
        ];

        return $this->resources->sectionFields($fields, $this->sections());
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, array{value:string,label:string}>
     */
    public function statusOptions(array $statuses): array
    {
        return collect($statuses)->map(fn (string $status): array => [
            'value' => $status,
            'label' => trans("app.status.{$status}"),
        ])->all();
    }

    /** @return array<int, array{value:string,label:string}> */
    private function frequencyOptions(): array
    {
        return collect(LeaseOptions::PAYMENT_FREQUENCIES)->map(fn (string $frequency): array => [
            'value' => $frequency,
            'label' => trans("app.leases.frequency_{$frequency}"),
        ])->all();
    }

    /**
     * @param  array<int, array{value:int,label:string}>  $options
     * @return array<int, array{value:int|string,label:string}>
     */
    private function availableOptions(array $options, string $emptyLabel): array
    {
        return $options !== [] ? $options : [['value' => '', 'label' => $emptyLabel]];
    }

    /** @return array<string, array{description:string,fields:array<int, string>}> */
    private function sections(): array
    {
        return [
            trans('app.leases.contract_scope') => [
                'description' => trans('app.leases.contract_scope_help'),
                'fields' => ['portfolio_id', 'tenant_profile_id', 'asset_id', 'status'],
            ],
            trans('app.leases.contract_period') => [
                'description' => trans('app.leases.contract_period_help'),
                'fields' => ['payment_frequency', 'started_at', 'ends_at', 'signed_at'],
            ],
            trans('app.leases.rent_schedule') => [
                'description' => trans('app.leases.rent_schedule_help'),
                'fields' => ['rent_amount', 'deposit_amount', 'tax_amount', 'discount_amount', 'currency', 'billing_day'],
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
