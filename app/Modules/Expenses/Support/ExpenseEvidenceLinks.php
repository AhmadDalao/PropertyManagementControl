<?php

namespace App\Modules\Expenses\Support;

use App\Models\ExpenseEntry;

final class ExpenseEvidenceLinks
{
    public function upload(ExpenseEntry $expense): string
    {
        return route('documents.create', [
            'documentable_type' => 'expense',
            'documentable_id' => $expense->id,
            'type' => 'other',
            'title_en' => trans('app.expenses.evidence_default_title', ['title' => $expense->title], locale: 'en'),
            'title_ar' => trans('app.expenses.evidence_default_title', ['title' => $expense->title], locale: 'ar'),
        ]);
    }
}
