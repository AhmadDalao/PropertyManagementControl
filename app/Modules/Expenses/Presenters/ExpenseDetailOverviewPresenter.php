<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class ExpenseDetailOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(ExpenseDetailData $data): array
    {
        $expense = $data->expense;

        return $this->resources->detailItems([
            ['label' => trans('app.expenses.amount'), 'value' => $data->amount, 'tone' => 'primary'],
            ['label' => trans('app.expenses.status'), 'value' => $data->status, 'tone' => $expense->status === 'void' ? 'danger' : 'teal'],
            ['label' => trans('app.expenses.incurred_on'), 'value' => $expense->incurred_on?->toDateString()],
            ['label' => trans('app.expenses.evidence'), 'value' => $data->documentsEnabled ? (string) ($expense->documents_count ?? 0) : trans('app.expenses.not_available')],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(ExpenseDetailData $data): array
    {
        $expense = $data->expense;
        $maintenance = $expense->maintenanceRequest;

        return [
            [
                'key' => 'context',
                'title' => trans('app.expenses.context_section'),
                'description' => trans('app.expenses.detail_context_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.expenses.asset'), 'value' => $this->assetName($data), 'href' => $expense->asset ? route('assets.show', $expense->asset) : null],
                    ['label' => trans('app.expenses.maintenance_request'), 'value' => $maintenance?->title, 'href' => $maintenance ? route('maintenance-requests.show', $maintenance) : null],
                    ['label' => trans('app.expenses.lease'), 'value' => $expense->lease?->code, 'href' => $expense->lease ? route('leases.show', $expense->lease) : null],
                    ['label' => trans('app.expenses.portfolio'), 'value' => $this->resources->localized($expense->portfolio?->name_en, $expense->portfolio?->name_ar), 'href' => $expense->portfolio ? route('portfolios.show', $expense->portfolio) : null],
                    ['label' => trans('app.expenses.created_by'), 'value' => $expense->createdBy?->name, 'href' => $this->userAccess->recordHref($data->actor, $expense->createdBy)],
                    ['label' => trans('app.expenses.description'), 'value' => $expense->description],
                ]),
            ],
            [
                'key' => 'financial',
                'title' => trans('app.expenses.financial_section'),
                'description' => trans('app.expenses.detail_financial_help'),
                'tab' => 'financial',
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.expenses.amount'), 'value' => $data->amount],
                    ['label' => trans('app.expenses.currency'), 'value' => $expense->currency],
                    ['label' => trans('app.expenses.status'), 'value' => $data->status],
                    ['label' => trans('app.expenses.category'), 'value' => $data->category],
                    ['label' => trans('app.expenses.vendor'), 'value' => $expense->vendor_name],
                    ['label' => trans('app.expenses.incurred_on'), 'value' => $expense->incurred_on?->toDateString()],
                ]),
            ],
        ];
    }

    private function assetName(ExpenseDetailData $data): string
    {
        return $this->resources->localized(
            $data->expense->asset?->title_en,
            $data->expense->asset?->title_ar,
        ) ?? trans('app.expenses.no_asset');
    }
}
