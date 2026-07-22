<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseDetailData;

final class ExpenseDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(ExpenseDetailData $data): array
    {
        $expense = $data->expense;
        $actions = [];

        if ($expense->status === 'posted') {
            $actions[] = [
                'label' => trans('app.expenses.edit_expense'),
                'href' => route('expenses.edit', $expense),
                'variant' => 'primary',
            ];
        }

        return [
            'eyebrow' => trans('app.expenses.detail_eyebrow'),
            'title' => $expense->title,
            'description' => trans('app.expenses.detail_description', [
                'category' => $data->category,
                'status' => $data->status,
                'vendor' => $expense->vendor_name ?: trans('app.expenses.no_vendor'),
            ]),
            'backHref' => route('expenses.index'),
            'backLabel' => trans('app.expenses.all_expenses'),
            'actions' => $actions,
        ];
    }
}
