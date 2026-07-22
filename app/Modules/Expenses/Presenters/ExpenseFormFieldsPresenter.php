<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseFormData;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Shared\ResourcePresenter;

final class ExpenseFormFieldsPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function present(ExpenseFormData $data, bool $creating): array
    {
        $fields = [];

        if ($creating && $data->actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.expenses.portfolio'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.expenses.portfolio_help'),
                'reloadOnChange' => ['queryKey' => 'portfolio_id'],
                'options' => [
                    ['value' => '', 'label' => trans('app.expenses.choose_portfolio')],
                    ...$data->portfolios,
                ],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'asset_id', 'label' => trans('app.expenses.asset'), 'type' => 'select', 'options' => [['value' => '', 'label' => trans('app.expenses.no_asset')], ...$data->assets]],
            ['name' => 'maintenance_request_id', 'label' => trans('app.expenses.maintenance_request'), 'type' => 'select', 'help' => trans('app.expenses.maintenance_link_help'), 'options' => [['value' => '', 'label' => trans('app.expenses.no_maintenance_request')], ...$data->maintenanceRequests]],
            ['name' => 'category', 'label' => trans('app.expenses.category'), 'type' => 'select', 'required' => true, 'options' => $this->categories()],
            ['name' => 'title', 'label' => trans('app.expenses.expense_title'), 'required' => true, 'max' => 255],
            ['name' => 'description', 'label' => trans('app.expenses.description'), 'type' => 'textarea', 'rows' => 3],
            ['name' => 'vendor_name', 'label' => trans('app.expenses.vendor'), 'max' => 255],
            ['name' => 'incurred_on', 'label' => trans('app.expenses.incurred_on'), 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => trans('app.expenses.amount'), 'type' => 'number', 'step' => '0.01', 'min' => '0.01', 'max' => 999999999999.99, 'required' => true],
            ['name' => 'currency', 'label' => trans('app.expenses.currency'), 'type' => 'select', 'required' => true, 'help' => trans('app.expenses.currency_help'), 'options' => [['value' => $data->currency, 'label' => $data->currency]]],
            ['name' => 'status', 'label' => trans('app.expenses.status'), 'type' => 'select', 'required' => true, 'help' => trans('app.expenses.status_help'), 'options' => $this->statuses()],
        ];

        return $this->resources->sectionFields($fields, $this->sections());
    }

    /** @return array<int, array{value:string,label:string}> */
    private function categories(): array
    {
        return collect(ExpenseOptions::CATEGORIES)->map(fn (string $category): array => [
            'value' => $category,
            'label' => ExpenseOptions::label($category),
        ])->all();
    }

    /** @return array<int, array{value:string,label:string}> */
    private function statuses(): array
    {
        return collect(ExpenseOptions::MUTABLE_STATUSES)->map(fn (string $status): array => [
            'value' => $status,
            'label' => trans("app.status.{$status}"),
        ])->all();
    }

    /** @return array<string, array{description:string,fields:array<int, string>}> */
    private function sections(): array
    {
        return [
            trans('app.expenses.context_section') => ['description' => trans('app.expenses.context_section_help'), 'fields' => ['portfolio_id', 'asset_id', 'maintenance_request_id']],
            trans('app.expenses.identity_section') => ['description' => trans('app.expenses.identity_section_help'), 'fields' => ['category', 'title', 'description', 'vendor_name']],
            trans('app.expenses.financial_section') => ['description' => trans('app.expenses.financial_section_help'), 'fields' => ['incurred_on', 'amount', 'currency', 'status']],
        ];
    }
}
