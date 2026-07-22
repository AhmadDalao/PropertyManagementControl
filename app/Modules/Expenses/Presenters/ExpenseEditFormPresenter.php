<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseFormData;

final class ExpenseEditFormPresenter
{
    public function __construct(private readonly ExpenseFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(ExpenseFormData $data): array
    {
        $expense = $data->expense;

        if ($expense === null) {
            abort(404);
        }

        return [
            'title' => trans('app.expenses.edit_expense'),
            'description' => trans('app.expenses.edit_description'),
            'backHref' => route('expenses.show', $expense),
            'backLabel' => trans('app.expenses.expense_detail'),
            'action' => route('expenses.update', $expense),
            'method' => 'put',
            'submitLabel' => trans('app.expenses.update_expense'),
            'fields' => $this->fields->present($data, false),
            'initialValues' => [
                'portfolio_id' => (string) $expense->portfolio_id,
                'asset_id' => (string) ($expense->asset_id ?? ''),
                'maintenance_request_id' => (string) ($expense->maintenance_request_id ?? ''),
                'category' => $expense->category,
                'title' => $expense->title,
                'description' => $expense->description ?? '',
                'incurred_on' => $expense->incurred_on?->toDateString(),
                'amount' => (float) $expense->amount,
                'currency' => $expense->currency,
                'vendor_name' => $expense->vendor_name ?? '',
                'status' => $expense->status,
            ],
        ];
    }
}
