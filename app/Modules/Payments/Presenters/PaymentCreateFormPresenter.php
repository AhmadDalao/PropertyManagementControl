<?php

namespace App\Modules\Payments\Presenters;

use App\Modules\Payments\Data\PaymentFormData;

final class PaymentCreateFormPresenter
{
    public function __construct(private readonly PaymentFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(PaymentFormData $data): array
    {
        return [
            'title' => trans('app.payments.record_payment'),
            'description' => trans('app.payments.create_description'),
            'backHref' => route('payments.index'),
            'backLabel' => trans('app.payments.all_payments'),
            'action' => route('payments.store'),
            'method' => 'post',
            'submitLabel' => trans('app.payments.record_payment'),
            'fields' => $this->fields->create($data),
            'initialValues' => [
                'portfolio_id' => (string) ($data->portfolioId ?? ''),
                'lease_id' => (string) $this->selected($data->defaults['lease_id'] ?? null, $data->leases),
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'reference' => '',
                'received_on' => now()->toDateString(),
                'amount' => 0,
                'notes' => '',
            ],
        ];
    }

    /** @param array<int, array{value:int,label:string}> $options */
    private function selected(mixed $requested, array $options): int|string
    {
        $id = filter_var($requested, FILTER_VALIDATE_INT);

        return $id && collect($options)->contains('value', (int) $id)
            ? (int) $id
            : ($options[0]['value'] ?? '');
    }
}
