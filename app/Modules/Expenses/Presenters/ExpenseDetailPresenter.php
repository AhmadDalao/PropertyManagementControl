<?php

namespace App\Modules\Expenses\Presenters;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

class ExpenseDetailPresenter
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<string, mixed> */
    public function present(ExpenseEntry $expense, User $actor): array
    {
        $this->access->ensureCanManage($actor, $expense);
        $expense->loadMissing(['portfolio', 'asset', 'lease', 'maintenanceRequest', 'createdBy']);
        $category = ExpenseOptions::label($expense->category);
        $status = trans("app.status.{$expense->status}");
        $amount = number_format((float) $expense->amount, 2).' '.$expense->currency;
        $maintenanceRequest = $expense->maintenanceRequest;
        $actions = [];

        if ($expense->status !== 'void') {
            $actions[] = [
                'label' => trans('app.expenses.edit_expense'),
                'href' => route('expenses.edit', $expense),
                'variant' => 'primary',
            ];
            $actions[] = [
                'label' => trans('app.expenses.void_expense'),
                'href' => route('expenses.destroy', $expense),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.expenses.void_confirm', ['title' => $expense->title]),
            ];
        }

        return [
            'header' => [
                'eyebrow' => trans('app.expenses.detail_eyebrow'),
                'title' => $expense->title,
                'description' => trans('app.expenses.detail_description', [
                    'category' => $category,
                    'status' => $status,
                    'vendor' => $expense->vendor_name ?: trans('app.expenses.no_vendor'),
                ]),
                'backHref' => route('expenses.index'),
                'backLabel' => trans('app.expenses.all_expenses'),
                'actions' => $actions,
            ],
            'decisionCards' => [
                [
                    'title' => trans('app.expenses.amount'),
                    'value' => $amount,
                    'detail' => $expense->incurred_on?->toDateString(),
                    'tone' => $expense->status === 'void' ? 'danger' : 'primary',
                    'icon' => 'bi-receipt',
                ],
                [
                    'title' => trans('app.expenses.asset'),
                    'value' => $this->resources->localized($expense->asset?->title_en, $expense->asset?->title_ar)
                        ?? trans('app.expenses.no_asset'),
                    'detail' => $expense->asset?->code,
                    'href' => $expense->asset ? route('assets.show', $expense->asset) : null,
                    'actionLabel' => $expense->asset ? trans('app.expenses.open_asset') : null,
                    'tone' => $expense->asset ? 'teal' : 'muted',
                    'icon' => 'bi-building',
                ],
                [
                    'title' => trans('app.expenses.maintenance_request'),
                    'value' => $maintenanceRequest ? $maintenanceRequest->title : trans('app.expenses.no_maintenance_request'),
                    'detail' => $maintenanceRequest
                        ? trans('app.expenses.request_number', ['id' => $maintenanceRequest->id])
                        : trans('app.expenses.unlinked_cost'),
                    'href' => $maintenanceRequest ? route('maintenance-requests.show', $maintenanceRequest) : null,
                    'actionLabel' => $maintenanceRequest ? trans('app.expenses.open_request') : null,
                    'tone' => $maintenanceRequest ? 'teal' : 'muted',
                    'icon' => 'bi-tools',
                ],
                [
                    'title' => trans('app.expenses.status'),
                    'value' => $status,
                    'detail' => $category,
                    'tone' => $expense->status === 'void' ? 'danger' : ($expense->status === 'pending' ? 'muted' : 'teal'),
                    'icon' => 'bi-clipboard-check',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.expenses.amount'), 'value' => $amount, 'tone' => 'primary'],
                ['label' => trans('app.expenses.status'), 'value' => $status, 'tone' => $expense->status === 'void' ? 'danger' : 'teal'],
                ['label' => trans('app.expenses.category'), 'value' => $category],
                ['label' => trans('app.expenses.incurred_on'), 'value' => $expense->incurred_on?->toDateString()],
            ]),
            'sections' => [
                [
                    'title' => trans('app.expenses.context_section'),
                    'description' => trans('app.expenses.detail_context_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.expenses.asset'), 'value' => $this->resources->localized($expense->asset?->title_en, $expense->asset?->title_ar), 'href' => $expense->asset ? route('assets.show', $expense->asset) : null],
                        ['label' => trans('app.expenses.maintenance_request'), 'value' => $maintenanceRequest?->title, 'href' => $maintenanceRequest ? route('maintenance-requests.show', $maintenanceRequest) : null],
                        ['label' => trans('app.expenses.lease'), 'value' => $expense->lease?->code, 'href' => $expense->lease ? route('leases.show', $expense->lease) : null],
                        ['label' => trans('app.expenses.portfolio'), 'value' => $this->resources->localized($expense->portfolio?->name_en, $expense->portfolio?->name_ar), 'href' => $expense->portfolio ? route('portfolios.show', $expense->portfolio) : null],
                        ['label' => trans('app.expenses.created_by'), 'value' => $expense->createdBy?->name, 'href' => $this->userAccess->recordHref($actor, $expense->createdBy)],
                        ['label' => trans('app.expenses.description'), 'value' => $expense->description],
                    ]),
                ],
                [
                    'title' => trans('app.expenses.financial_section'),
                    'description' => trans('app.expenses.detail_financial_help'),
                    'tab' => 'financial',
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.expenses.amount'), 'value' => $amount],
                        ['label' => trans('app.expenses.currency'), 'value' => $expense->currency],
                        ['label' => trans('app.expenses.status'), 'value' => $status],
                        ['label' => trans('app.expenses.category'), 'value' => $category],
                        ['label' => trans('app.expenses.vendor'), 'value' => $expense->vendor_name],
                        ['label' => trans('app.expenses.incurred_on'), 'value' => $expense->incurred_on?->toDateString()],
                    ]),
                ],
            ],
            'related' => [],
            'documents' => [],
            'timeline' => $this->resources->activityTimeline($expense),
        ];
    }
}
