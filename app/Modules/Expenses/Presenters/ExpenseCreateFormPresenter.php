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
            'layout' => 'expense',
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
                'maintenance_work_order_id' => (string) ($this->id($data->defaults['maintenance_work_order_id'] ?? null) ?? ''),
                'category' => 'maintenance',
                'title' => $this->text($data->defaults['title'] ?? null, 255),
                'description' => $this->text($data->defaults['description'] ?? null, 5000),
                'incurred_on' => now()->toDateString(),
                'amount' => $this->amount($data->defaults['amount'] ?? null),
                'currency' => $data->currency,
                'vendor_name' => $this->text($data->defaults['vendor_name'] ?? null, 255),
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

    private function text(mixed $value, int $max): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, $max) : '';
    }

    private function amount(mixed $value): float|string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $amount = round((float) $value, 2);

        return $amount >= 0.01 && $amount <= 999999999999.99 ? $amount : '';
    }

    private function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id ? (int) $id : null;
    }
}
