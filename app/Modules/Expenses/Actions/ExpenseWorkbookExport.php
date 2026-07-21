<?php

namespace App\Modules\Expenses\Actions;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Queries\ExpenseIndexQuery;
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
            'Title',
            'Asset',
            'Category',
            'Vendor',
            'Date',
            'Status',
            'Amount',
            'Currency',
        ], $this->expenses->forExport($request, $actor), fn (ExpenseEntry $expense): array => [
            $expense->title,
            $this->workbook->localized($expense->asset, 'title_en', 'title_ar'),
            $this->workbook->option($expense->category),
            $expense->vendor_name,
            $this->workbook->date($expense->incurred_on),
            $this->workbook->option($expense->status),
            $expense->amount,
            $expense->currency,
        ]);
    }
}
