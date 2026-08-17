<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseDetailData;
use App\Modules\Expenses\Support\ExpenseEvidenceLinks;

final class ExpenseWorkflowPresenter
{
    public function __construct(private readonly ExpenseEvidenceLinks $evidence) {}

    /** @return array<string, mixed> */
    public function present(ExpenseDetailData $data): array
    {
        $expense = $data->expense;
        $actions = [];

        if ($expense->status === 'pending') {
            $actions[] = [
                'label' => trans('app.expenses.review_and_post'),
                'href' => route('expenses.edit', $expense),
                'variant' => 'primary',
            ];
        }

        if ($expense->status === 'posted'
            && $data->documentsEnabled
            && $expense->portfolio?->status === 'active') {
            $actions[] = [
                'label' => trans('app.expenses.upload_evidence'),
                'href' => $this->evidence->upload($expense),
                'variant' => 'primary',
            ];
        }

        if ($expense->status !== 'void') {
            $actions[] = [
                'label' => trans('app.expenses.void_expense'),
                'href' => route('expenses.destroy', $expense),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.expenses.void_confirm', ['title' => $expense->title]),
            ];
        }

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.expenses.workflow_{$expense->status}_title"),
            'description' => trans("app.expenses.workflow_{$expense->status}_description"),
            'status' => trans("app.status.{$expense->status}"),
            'tone' => match ($expense->status) {
                'posted' => 'teal',
                'void' => 'danger',
                default => 'primary',
            },
            'icon' => 'bi-cash-stack',
            'actions' => $actions,
        ];
    }
}
