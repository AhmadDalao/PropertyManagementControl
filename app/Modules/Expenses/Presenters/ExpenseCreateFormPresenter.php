<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseFormData;

final class ExpenseCreateFormPresenter
{
    public function __construct(private readonly ExpenseFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(ExpenseFormData $data): array
    {
        return [
            'title' => trans('app.expenses.record_expense'),
            'description' => trans('app.expenses.create_description'),
            'backHref' => route('expenses.index'),
            'backLabel' => trans('app.expenses.all_expenses'),
            'action' => route('expenses.store'),
            'method' => 'post',
            'submitLabel' => trans('app.expenses.record_expense'),
            'fields' => $this->fields->present($data, true),
            'initialValues' => [
                'portfolio_id' => (string) ($data->portfolioId ?? ''),
                'asset_id' => (string) $this->selected($data->defaults['asset_id'] ?? null, $data->assets),
                'maintenance_request_id' => (string) $this->selected($data->defaults['maintenance_request_id'] ?? null, $data->maintenanceRequests),
                'category' => 'maintenance',
                'title' => '',
                'description' => '',
                'incurred_on' => now()->toDateString(),
                'amount' => '',
                'currency' => $data->currency,
                'vendor_name' => '',
                'status' => 'posted',
            ],
        ];
    }

    /** @param array<int, array{value:int,label:string}> $options */
    private function selected(mixed $requested, array $options): int|string
    {
        $id = filter_var($requested, FILTER_VALIDATE_INT);

        return $id && collect($options)->contains('value', (int) $id) ? (int) $id : '';
    }
}
