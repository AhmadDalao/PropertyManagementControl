<?php

namespace App\Modules\Leases\Requests;

trait HasLeaseValidationAttributes
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.leases.portfolio'),
            'renewed_from_lease_id' => trans('app.leases.previous_contract'),
            'tenant_profile_id' => trans('app.leases.tenant'),
            'asset_id' => trans('app.leases.asset'),
            'status' => trans('app.leases.status'),
            'payment_frequency' => trans('app.leases.payment_frequency'),
            'started_at' => trans('app.leases.start_date'),
            'ends_at' => trans('app.leases.end_date'),
            'signed_at' => trans('app.leases.signed_date'),
            'rent_amount' => trans('app.leases.rent_amount'),
            'deposit_amount' => trans('app.leases.deposit'),
            'tax_amount' => trans('app.leases.tax'),
            'discount_amount' => trans('app.leases.discount'),
            'currency' => trans('app.leases.currency'),
            'billing_day' => trans('app.leases.billing_day'),
            'terms_en' => trans('app.leases.terms_en'),
            'terms_ar' => trans('app.leases.terms_ar'),
            'notes' => trans('app.leases.notes'),
            'signed_contract' => trans('app.leases.signed_contract'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach (['currency', 'terms_en', 'terms_ar', 'notes'] as $field) {
            if (is_string($this->input($field))) {
                $updates[$field] = trim((string) $this->input($field));
            }
        }

        if (isset($updates['currency'])) {
            $updates['currency'] = mb_strtoupper($updates['currency']);
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
