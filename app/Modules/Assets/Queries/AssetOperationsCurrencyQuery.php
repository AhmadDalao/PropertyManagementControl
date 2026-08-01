<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use Illuminate\Support\Collection;

final class AssetOperationsCurrencyQuery
{
    /**
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, LeaseInstallment>  $installments
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @return list<array{
     *     currency:string,
     *     monthlyScheduledDue:float,
     *     monthlyScheduledPaid:float,
     *     collectionRate:float,
     *     arrears:float,
     *     monthlyRevenue:float,
     *     monthlyExpenses:float,
     *     monthlyNet:float,
     *     postedExpenseTotal:float
     * }>
     */
    public function summarize(
        Asset $asset,
        Collection $leases,
        Collection $installments,
        Collection $payments,
        Collection $expenses,
    ): array {
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
                $this->currency($asset->currency),
            );
            $totals[$currency] ??= $this->emptyTotal($currency);

            if ($installment->due_date?->isCurrentMonth()) {
                $totals[$currency]['monthlyScheduledDue'] += (float) $installment->amount_due;
                $totals[$currency]['monthlyScheduledPaid'] += min(
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
            $totals[$currency]['monthlyRevenue'] += (float) $payment->amount;
        }

        foreach ($expenses as $expense) {
            $currency = $this->currency($expense->currency);
            $totals[$currency] ??= $this->emptyTotal($currency);
            $totals[$currency]['postedExpenseTotal'] += (float) $expense->amount;

            if ($expense->incurred_on?->isCurrentMonth()) {
                $totals[$currency]['monthlyExpenses'] += (float) $expense->amount;
            }
        }

        if ($totals === []) {
            $currency = $this->currency($asset->currency);
            $totals[$currency] = $this->emptyTotal($currency);
        }

        ksort($totals);
        $result = [];

        foreach ($totals as $total) {
            $scheduledDue = (float) $total['monthlyScheduledDue'];
            $scheduledPaid = (float) $total['monthlyScheduledPaid'];
            $revenue = (float) $total['monthlyRevenue'];
            $expenses = (float) $total['monthlyExpenses'];
            $result[] = [
                ...$total,
                'collectionRate' => $scheduledDue > 0
                    ? round(min(100, ($scheduledPaid / $scheduledDue) * 100), 1)
                    : 0.0,
                'monthlyNet' => $revenue - $expenses,
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *     currency:string,
     *     monthlyScheduledDue:float,
     *     monthlyScheduledPaid:float,
     *     collectionRate:float,
     *     arrears:float,
     *     monthlyRevenue:float,
     *     monthlyExpenses:float,
     *     monthlyNet:float,
     *     postedExpenseTotal:float
     * }
     */
    private function emptyTotal(string $currency): array
    {
        return [
            'currency' => $currency,
            'monthlyScheduledDue' => 0.0,
            'monthlyScheduledPaid' => 0.0,
            'collectionRate' => 0.0,
            'arrears' => 0.0,
            'monthlyRevenue' => 0.0,
            'monthlyExpenses' => 0.0,
            'monthlyNet' => 0.0,
            'postedExpenseTotal' => 0.0,
        ];
    }

    private function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }
}
