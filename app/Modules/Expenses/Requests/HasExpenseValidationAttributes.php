<?php

namespace App\Modules\Expenses\Requests;

trait HasExpenseValidationAttributes
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.expenses.portfolio'),
            'asset_id' => trans('app.expenses.asset'),
            'maintenance_request_id' => trans('app.expenses.maintenance_request'),
            'category' => trans('app.expenses.category'),
            'title' => trans('app.expenses.expense_title'),
            'description' => trans('app.expenses.description'),
            'incurred_on' => trans('app.expenses.incurred_on'),
            'amount' => trans('app.expenses.amount'),
            'currency' => trans('app.expenses.currency'),
            'vendor_name' => trans('app.expenses.vendor'),
            'status' => trans('app.expenses.status'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach (['title', 'description', 'vendor_name'] as $field) {
            if (is_string($this->input($field))) {
                $updates[$field] = trim((string) $this->input($field));
            }
        }

        if (is_string($this->input('currency'))) {
            $updates['currency'] = strtoupper(trim((string) $this->input('currency')));
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
