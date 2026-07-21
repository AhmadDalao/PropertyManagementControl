<?php

namespace App\Modules\Expenses\Presenters;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Expenses\Support\ExpenseReferenceOptions;
use App\Modules\Shared\ResourcePresenter;

class ExpenseFormPresenter
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly ExpenseReferenceOptions $options,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?ExpenseEntry $expense = null, array $defaults = []): array
    {
        if ($expense) {
            $this->access->ensureCanManage($actor, $expense);
            abort_if($expense->status === 'void', 409, trans('app.errors.expense_void_locked'));
        } else {
            $this->access->ensureManager($actor);
        }

        $portfolioId = $this->options->selectedPortfolioId($actor, $expense, $defaults);
        $currency = $this->options->currency($portfolioId);
        $fields = [];

        if ($actor->hasRole('superadmin') && ! $expense) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.expenses.portfolio'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.expenses.portfolio_help'),
                'reloadOnChange' => ['queryKey' => 'portfolio_id'],
                'options' => [
                    ['value' => '', 'label' => trans('app.expenses.choose_portfolio')],
                    ...$this->options->portfolios($actor),
                ],
            ];
        }

        $fields = [
            ...$fields,
            [
                'name' => 'asset_id',
                'label' => trans('app.expenses.asset'),
                'type' => 'select',
                'options' => [
                    ['value' => '', 'label' => trans('app.expenses.no_asset')],
                    ...$this->options->assets($portfolioId),
                ],
            ],
            [
                'name' => 'maintenance_request_id',
                'label' => trans('app.expenses.maintenance_request'),
                'type' => 'select',
                'help' => trans('app.expenses.maintenance_link_help'),
                'options' => [
                    ['value' => '', 'label' => trans('app.expenses.no_maintenance_request')],
                    ...$this->options->maintenanceRequests($portfolioId),
                ],
            ],
            [
                'name' => 'category',
                'label' => trans('app.expenses.category'),
                'type' => 'select',
                'required' => true,
                'options' => collect(ExpenseOptions::CATEGORIES)
                    ->map(fn (string $category): array => [
                        'value' => $category,
                        'label' => trans("app.expenses.category_{$category}"),
                    ])
                    ->all(),
            ],
            ['name' => 'title', 'label' => trans('app.expenses.expense_title'), 'required' => true],
            ['name' => 'description', 'label' => trans('app.expenses.description'), 'type' => 'textarea', 'rows' => 3],
            ['name' => 'vendor_name', 'label' => trans('app.expenses.vendor')],
            ['name' => 'incurred_on', 'label' => trans('app.expenses.incurred_on'), 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => trans('app.expenses.amount'), 'type' => 'number', 'step' => '0.01', 'min' => '0.01', 'required' => true],
            [
                'name' => 'currency',
                'label' => trans('app.expenses.currency'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.expenses.currency_help'),
                'options' => [['value' => $currency, 'label' => $currency]],
            ],
            [
                'name' => 'status',
                'label' => trans('app.expenses.status'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.expenses.status_help'),
                'options' => collect(ExpenseOptions::MUTABLE_STATUSES)
                    ->map(fn (string $status): array => [
                        'value' => $status,
                        'label' => trans("app.status.{$status}"),
                    ])
                    ->all(),
            ],
        ];
        $fields = $this->resources->sectionFields($fields, [
            trans('app.expenses.context_section') => [
                'description' => trans('app.expenses.context_section_help'),
                'fields' => ['portfolio_id', 'asset_id', 'maintenance_request_id'],
            ],
            trans('app.expenses.identity_section') => [
                'description' => trans('app.expenses.identity_section_help'),
                'fields' => ['category', 'title', 'description', 'vendor_name'],
            ],
            trans('app.expenses.financial_section') => [
                'description' => trans('app.expenses.financial_section_help'),
                'fields' => ['incurred_on', 'amount', 'currency', 'status'],
            ],
        ]);

        return [
            'title' => $expense ? trans('app.expenses.edit_expense') : trans('app.expenses.record_expense'),
            'description' => $expense
                ? trans('app.expenses.edit_description')
                : trans('app.expenses.create_description'),
            'backHref' => $expense ? route('expenses.show', $expense) : route('expenses.index'),
            'backLabel' => $expense ? trans('app.expenses.expense_detail') : trans('app.expenses.all_expenses'),
            'action' => $expense ? route('expenses.update', $expense) : route('expenses.store'),
            'method' => $expense ? 'put' : 'post',
            'submitLabel' => $expense ? trans('app.expenses.update_expense') : trans('app.expenses.record_expense'),
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($portfolioId ?? ''),
                'asset_id' => (string) ($expense ? $expense->asset_id : ($defaults['asset_id'] ?? '')),
                'maintenance_request_id' => (string) ($expense ? $expense->maintenance_request_id : ($defaults['maintenance_request_id'] ?? '')),
                'category' => $expense ? $expense->category : 'maintenance',
                'title' => $expense ? $expense->title : '',
                'description' => $expense ? ($expense->description ?? '') : '',
                'incurred_on' => $expense ? $expense->incurred_on?->toDateString() : now()->toDateString(),
                'amount' => $expense ? (float) $expense->amount : '',
                'currency' => $currency,
                'vendor_name' => $expense ? ($expense->vendor_name ?? '') : '',
                'status' => $expense ? $expense->status : 'posted',
            ],
        ];
    }
}
