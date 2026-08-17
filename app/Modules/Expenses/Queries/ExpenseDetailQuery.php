<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Data\ExpenseDetailData;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Portfolios\Support\PortfolioModules;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class ExpenseDetailQuery
{
    public function __construct(private readonly ExpenseAccess $access) {}

    public function get(ExpenseEntry $expense, User $actor): ExpenseDetailData
    {
        $this->access->ensureCanManage($actor, $expense);
        $documentsEnabled = PortfolioModules::enabledForUser($actor, 'documents');
        $expense->load([
            'portfolio',
            'asset',
            'lease',
            'maintenanceRequest',
            'createdBy',
        ]);

        if ($documentsEnabled) {
            $expense->load([
                'documents' => fn (MorphMany $documents) => $documents
                    ->with('uploadedBy:id,name')
                    ->latest('id')
                    ->limit(12),
            ])->loadCount('documents');
        }

        return new ExpenseDetailData(
            expense: $expense,
            actor: $actor,
            category: ExpenseOptions::label($expense->category),
            status: trans("app.status.{$expense->status}"),
            amount: number_format((float) $expense->amount, 2).' '.$expense->currency,
            documentsEnabled: $documentsEnabled,
        );
    }
}
