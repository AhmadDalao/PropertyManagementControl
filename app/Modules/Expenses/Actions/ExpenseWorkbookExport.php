<?php

namespace App\Modules\Expenses\Actions;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Queries\ExpenseIndexQuery;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly ExpenseIndexQuery $expenses,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('expenses', [
            trans('app.expenses.expense_title'),
            trans('app.expenses.asset'),
            trans('app.expenses.category'),
            trans('app.expenses.vendor'),
            trans('app.expenses.incurred_on'),
            trans('app.expenses.status'),
            trans('app.expenses.amount'),
            trans('app.expenses.currency'),
        ], $this->expenses->forExport($request, $actor), fn (ExpenseEntry $expense): array => [
            $expense->title,
            $this->workbook->localized($expense->asset, 'title_en', 'title_ar'),
            ExpenseOptions::label($expense->category),
            $expense->vendor_name,
            $this->workbook->date($expense->incurred_on),
            $this->workbook->option($expense->status),
            $expense->amount,
            $expense->currency,
        ]);
    }
}
