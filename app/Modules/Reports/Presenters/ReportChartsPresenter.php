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
     *     revenueByMonth:list<array{label:string,currency:string,amount:float}>,
     *     expenseByCategory:list<array{label:string,currency:string,amount:float}>,
     *     assetMix:Collection<int|string, int<0, max>>,
     *     maintenanceByStatus:Collection<int|string, int<0, max>>
     * }
     */
    public function present(PortfolioReportData $data): array
    {
        return [
            'revenueByMonth' => array_values($data->payments
                ->groupBy(fn (Payment $payment): string => $this->currency($payment->currency))
                ->sortKeys()
                ->flatMap(fn (Collection $currencyPayments, string $currency): Collection => $currencyPayments
                    ->groupBy(fn (Payment $payment): string => $payment->received_on?->format('Y-m') ?? trans('app.reports.unscheduled'))
                    ->sortKeys()
                    ->map(fn (Collection $group, string $label): array => [
                        'label' => $label,
                        'currency' => $currency,
                        'amount' => (float) $group->sum('amount'),
                    ])
                    ->values())
                ->values()
                ->all()),
            'expenseByCategory' => array_values($data->expenses
                ->groupBy(fn (ExpenseEntry $expense): string => $this->currency($expense->currency))
                ->sortKeys()
                ->flatMap(fn (Collection $currencyExpenses, string $currency): Collection => $currencyExpenses
                    ->groupBy(fn (ExpenseEntry $expense): string => $expense->category ?: 'uncategorized')
                    ->sortKeys()
                    ->map(fn (Collection $group, string $label): array => [
                        'label' => $label,
                        'currency' => $currency,
                        'amount' => (float) $group->sum('amount'),
                    ])
                    ->values())
                ->values()
                ->all()),
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

    private function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }
}
