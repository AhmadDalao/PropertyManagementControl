<?php

namespace App\Modules\Payments\Requests;

trait HasPaymentValidationAttributes
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'lease_id' => trans('app.payments.lease_id'),
            'type' => trans('app.payments.type'),
            'method' => trans('app.payments.method'),
            'status' => trans('app.payments.status'),
            'reference' => trans('app.payments.reference'),
            'received_on' => trans('app.payments.received_on'),
            'amount' => trans('app.payments.amount'),
            'notes' => trans('app.payments.notes'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach (['reference', 'notes'] as $field) {
            if (is_string($this->input($field))) {
                $updates[$field] = trim((string) $this->input($field));
            }
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
