<?php

namespace App\Modules\Payments\Presenters;

use App\Modules\Payments\Data\PaymentFormData;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\ResourcePresenter;

final class PaymentFormFieldsPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function create(PaymentFormData $data): array
    {
        $fields = [];

        if ($data->actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.payments.portfolio'),
                'type' => 'select',
                'required' => true,
                'options' => $data->portfolios,
                'reloadOnChange' => ['queryKey' => 'portfolio_id'],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'lease_id', 'label' => trans('app.payments.lease_id'), 'type' => 'select', 'required' => true, 'options' => $this->availableLeases($data->leases), 'help' => trans('app.payments.lease_help')],
            ['name' => 'type', 'label' => trans('app.payments.type'), 'type' => 'select', 'required' => true, 'options' => $this->options(PaymentOptions::TYPES, 'type')],
            ['name' => 'method', 'label' => trans('app.payments.method'), 'type' => 'select', 'required' => true, 'options' => $this->options(PaymentOptions::METHODS, 'method')],
            ['name' => 'status', 'label' => trans('app.payments.status'), 'type' => 'select', 'required' => true, 'options' => $this->statusOptions(PaymentOptions::CREATE_STATUSES)],
            ['name' => 'reference', 'label' => trans('app.payments.reference'), 'help' => trans('app.payments.reference_help'), 'max' => 255],
            ['name' => 'received_on', 'label' => trans('app.payments.received_on'), 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => trans('app.payments.amount'), 'type' => 'number', 'min' => 0.01, 'max' => 999999999999.99, 'step' => '0.01', 'required' => true],
            ['name' => 'notes', 'label' => trans('app.payments.notes'), 'type' => 'textarea', 'rows' => 4],
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

    /**
     * @param  array<int, string>  $values
     * @return array<int, array{value:string,label:string}>
     */
    public function options(array $values, string $prefix): array
    {
        return collect($values)->map(fn (string $value): array => [
            'value' => $value,
            'label' => trans("app.payments.{$prefix}_{$value}"),
        ])->all();
    }

    /**
     * @param  array<int, array{value:int,label:string}>  $leases
     * @return array<int, array{value:int|string,label:string}>
     */
    private function availableLeases(array $leases): array
    {
        return $leases !== []
            ? $leases
            : [['value' => '', 'label' => trans('app.payments.no_payable_leases')]];
    }

    /** @return array<string, array{description:string,fields:array<int, string>}> */
    private function sections(): array
    {
        return [
            trans('app.payments.payment_target') => [
                'description' => trans('app.payments.payment_target_help'),
                'fields' => ['portfolio_id', 'lease_id', 'type'],
            ],
            trans('app.payments.payment_evidence') => [
                'description' => trans('app.payments.payment_evidence_help'),
                'fields' => ['method', 'status', 'reference', 'received_on', 'amount'],
            ],
            trans('app.payments.internal_context') => [
                'description' => trans('app.payments.internal_context_help'),
                'fields' => ['notes'],
            ],
        ];
    }
}
