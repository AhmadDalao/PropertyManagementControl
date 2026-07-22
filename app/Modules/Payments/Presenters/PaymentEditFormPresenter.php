<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Payment;
use App\Modules\Payments\Support\PaymentTransitionGuard;

final class PaymentEditFormPresenter
{
    public function __construct(
        private readonly PaymentFormFieldsPresenter $fields,
        private readonly PaymentTransitionGuard $transitions,
    ) {}

    /** @return array<string, mixed> */
    public function present(Payment $payment): array
    {
        return [
            'title' => trans('app.payments.edit_payment', [
                'reference' => $payment->reference ?: '#'.$payment->id,
            ]),
            'description' => trans('app.payments.edit_description'),
            'backHref' => route('payments.show', $payment),
            'backLabel' => trans('app.payments.payment_detail'),
            'action' => route('payments.update', $payment),
            'method' => 'put',
            'submitLabel' => trans('app.payments.update_payment'),
            'fields' => [
                ['name' => 'status', 'label' => trans('app.payments.status'), 'type' => 'select', 'required' => true, 'options' => $this->fields->statusOptions($this->transitions->allowedStatuses((string) $payment->status))],
                ['name' => 'notes', 'label' => trans('app.payments.notes'), 'type' => 'textarea', 'rows' => 4],
            ],
            'initialValues' => [
                'status' => $payment->status,
                'notes' => $payment->notes ?? '',
            ],
        ];
    }
}
