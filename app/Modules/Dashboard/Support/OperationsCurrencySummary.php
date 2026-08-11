<?php

namespace App\Modules\Dashboard\Support;

use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class OperationsCurrencySummary
{
    /**
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, LeaseInstallment>  $installments
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @return list<array{
     *     currency:string,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     collectionRate:float,
     *     revenue:float,
     *     expenses:float,
     *     net:float,
     *     arrears:float
     * }>
     */
    public function summarize(
        User $actor,
        Collection $leases,
        Collection $installments,
        Collection $payments,
        Collection $expenses,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): array {
        $periodStart ??= now()->startOfMonth();
        $periodEnd ??= now()->endOfMonth();
        $totals = [];
        $leaseCurrencies = $leases->mapWithKeys(
            fn (Lease $lease): array => [
                $lease->id => $this->currency($lease->currency),
            ],
        );

        foreach ($leases as $lease) {
            $currency = $this->currency($lease->currency);
            $totals[$currency] ??= $this->emptyTotal($currency);
        }

        foreach ($installments as $installment) {
            $currency = $leaseCurrencies->get(
                (int) $installment->lease_id,
                $this->defaultCurrency($actor),
            );
            $totals[$currency] ??= $this->emptyTotal($currency);

            if ($installment->due_date?->betweenIncluded($periodStart, $periodEnd)) {
                $totals[$currency]['scheduledDue'] += (float) $installment->amount_due;
                $totals[$currency]['scheduledPaid'] += min(
                    (float) $installment->amount_due,
                    (float) $installment->amount_paid,
                );
            }

            if ($installment->due_date?->isBefore(today())) {
                $totals[$currency]['arrears'] += max(
                    0,
                    (float) $installment->amount_due - (float) $installment->amount_paid,
                );
            }
        }

        foreach ($payments as $payment) {
            $currency = $this->currency($payment->currency);
            $totals[$currency] ??= $this->emptyTotal($currency);
            $totals[$currency]['revenue'] += (float) $payment->amount;
        }

        foreach ($expenses as $expense) {
            $currency = $this->currency($expense->currency);
            $totals[$currency] ??= $this->emptyTotal($currency);
            $totals[$currency]['expenses'] += (float) $expense->amount;
        }

        if ($totals === []) {
            $currency = $this->defaultCurrency($actor);
            $totals[$currency] = $this->emptyTotal($currency);
        }

        ksort($totals);
        $result = [];

        foreach ($totals as $total) {
            $scheduledDue = (float) $total['scheduledDue'];
            $scheduledPaid = (float) $total['scheduledPaid'];
            $revenue = (float) $total['revenue'];
            $expenses = (float) $total['expenses'];
            $result[] = [
                ...$total,
                'collectionRate' => $scheduledDue > 0
                    ? round(min(100, ($scheduledPaid / $scheduledDue) * 100), 2)
                    : 0.0,
                'net' => $revenue - $expenses,
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *     currency:string,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     collectionRate:float,
     *     revenue:float,
     *     expenses:float,
     *     net:float,
     *     arrears:float
     * }
     */
    private function emptyTotal(string $currency): array
    {
        return [
            'currency' => $currency,
            'scheduledDue' => 0.0,
            'scheduledPaid' => 0.0,
            'collectionRate' => 0.0,
            'revenue' => 0.0,
            'expenses' => 0.0,
            'net' => 0.0,
            'arrears' => 0.0,
        ];
    }

    private function defaultCurrency(User $actor): string
    {
        if ($actor->portfolio_id) {
            return $this->currency((string) (Portfolio::query()
                ->whereKey($actor->portfolio_id)
                ->value('default_currency') ?: 'SAR'));
        }

        return 'SAR';
    }

    private function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }
}
