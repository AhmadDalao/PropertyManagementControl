<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ExpenseEntry;
use App\Models\Payment;
use App\Modules\Reports\Data\PortfolioReportData;
use Illuminate\Support\Collection;

final class ReportChartsPresenter
{
    /**
     * @return array{
     *     revenueByMonth:Collection<int|string, float>,
     *     expenseByCategory:Collection<int|string, float>,
     *     assetMix:Collection<int|string, int<0, max>>,
     *     maintenanceByStatus:Collection<int|string, int<0, max>>
     * }
     */
    public function present(PortfolioReportData $data): array
    {
        return [
            'revenueByMonth' => $data->payments
                ->groupBy(fn (Payment $payment): string => $payment->received_on?->format('Y-m') ?? trans('app.reports.unscheduled'))
                ->sortKeys()
                ->map(fn (Collection $group): float => (float) $group->sum('amount')),
            'expenseByCategory' => $data->expenses
                ->groupBy(fn (ExpenseEntry $expense): string => $expense->category ?: 'uncategorized')
                ->sortKeys()
                ->map(fn (Collection $group): float => (float) $group->sum('amount')),
            'assetMix' => $data->assets
                ->groupBy('asset_type')
                ->sortKeys()
                ->map(fn (Collection $group): int => $group->count()),
            'maintenanceByStatus' => $data->maintenanceRequests
                ->groupBy('status')
                ->sortKeys()
                ->map(fn (Collection $group): int => $group->count()),
        ];
    }
}
