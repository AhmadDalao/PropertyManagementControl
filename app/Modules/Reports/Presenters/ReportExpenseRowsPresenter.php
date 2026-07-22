<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ExpenseEntry;
use App\Modules\Reports\Data\PortfolioReportData;
use App\Modules\Shared\PortfolioScope;

final readonly class ReportExpenseRowsPresenter
{
    public function __construct(private PortfolioScope $portfolios) {}

    /** @return array<int, array<string, mixed>> */
    public function present(PortfolioReportData $data): array
    {
        return $data->expenses
            ->sortByDesc('incurred_on')
            ->take(8)
            ->map(fn (ExpenseEntry $expense): array => [
                'id' => $expense->id,
                'title' => $expense->title,
                'category' => $expense->category,
                'asset' => $this->portfolios->localized(
                    $expense->asset?->title_en,
                    $expense->asset?->title_ar,
                ),
                'amount' => (float) $expense->amount,
                'currency' => $expense->currency,
                'incurred_on' => $expense->incurred_on?->toDateString(),
            ])
            ->values()
            ->all();
    }
}
