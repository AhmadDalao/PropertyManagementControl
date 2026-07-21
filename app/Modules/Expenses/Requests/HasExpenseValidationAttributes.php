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
        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper(trim((string) $this->input('currency')))]);
        }
    }
}
