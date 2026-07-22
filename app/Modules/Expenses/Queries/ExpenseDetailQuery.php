<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Data\ExpenseDetailData;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;

final class ExpenseDetailQuery
{
    public function __construct(private readonly ExpenseAccess $access) {}

    public function get(ExpenseEntry $expense, User $actor): ExpenseDetailData
    {
        $this->access->ensureCanManage($actor, $expense);
        $expense->load([
            'portfolio',
            'asset',
            'lease',
            'maintenanceRequest',
            'createdBy',
        ]);

        return new ExpenseDetailData(
            expense: $expense,
            actor: $actor,
            category: ExpenseOptions::label($expense->category),
            status: trans("app.status.{$expense->status}"),
            amount: number_format((float) $expense->amount, 2).' '.$expense->currency,
        );
    }
}
