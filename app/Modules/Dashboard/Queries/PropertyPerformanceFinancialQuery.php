<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\ExpenseEntry;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Dashboard\Support\PropertyPerformanceCurrencySummary;
use App\Modules\Shared\PortfolioScope;

final readonly class PropertyPerformanceFinancialQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private PropertyPerformanceCurrencySummary $currencies,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByLease
     * @param  array<int, string>  $currencyByLease
     * @param  array<int, int>  $rootByAsset
     */
    public function add(
        array &$rows,
        array $rootByLease,
        array $currencyByLease,
        array $rootByAsset,
        User $actor,
    ): void {
        $this->addInstallments($rows, $rootByLease, $currencyByLease);
        $this->addPayments($rows, $rootByLease, $actor);
        $this->addExpenses($rows, $rootByAsset, $actor);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByLease
     * @param  array<int, string>  $currencyByLease
     */
    private function addInstallments(
        array &$rows,
        array $rootByLease,
        array $currencyByLease,
    ): void {
        LeaseInstallment::query()
            ->whereIn('lease_id', array_keys($rootByLease))
            ->where(fn ($query) => $query
                ->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->orWhereDate('due_date', '<', today()))
            ->get(['lease_id', 'due_date', 'amount_due', 'amount_paid'])
            ->each(function (LeaseInstallment $installment) use (
                &$rows,
                $rootByLease,
                $currencyByLease,
            ): void {
                $rootId = $rootByLease[$installment->lease_id];
                $currency = $currencyByLease[$installment->lease_id]
                    ?? (string) $rows[$rootId]['currency'];
                $this->ensureCurrency($rows, $rootId, $currency);

                if ($installment->due_date?->isCurrentMonth()) {
                    $rows[$rootId]['currency_totals'][$currency]['scheduled_due'] +=
                        (float) $installment->amount_due;
                    $rows[$rootId]['currency_totals'][$currency]['scheduled_paid'] +=
                        min((float) $installment->amount_due, (float) $installment->amount_paid);
                }
                if ($installment->due_date?->isBefore(today())) {
                    $rows[$rootId]['currency_totals'][$currency]['arrears'] +=
                        $installment->remaining_amount;
                }
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByLease
     */
    private function addPayments(
        array &$rows,
        array $rootByLease,
        User $actor,
    ): void {
        $this->portfolios->apply(Payment::query(), $actor)
            ->where('status', 'posted')
            ->whereIn('lease_id', array_keys($rootByLease))
            ->whereBetween('received_on', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['lease_id', 'amount', 'currency'])
            ->each(function (Payment $payment) use (&$rows, $rootByLease): void {
                $rootId = $payment->lease_id !== null
                    ? ($rootByLease[$payment->lease_id] ?? null)
                    : null;

                if ($rootId !== null && isset($rows[$rootId])) {
                    $currency = $this->currencies->currency($payment->currency);
                    $this->ensureCurrency($rows, $rootId, $currency);
                    $rows[$rootId]['currency_totals'][$currency]['collected'] +=
                        (float) $payment->amount;
                }
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $rootByAsset
     */
    private function addExpenses(
        array &$rows,
        array $rootByAsset,
        User $actor,
    ): void {
        $this->portfolios->apply(ExpenseEntry::query(), $actor)
            ->where('status', 'posted')
            ->whereIn('asset_id', array_keys($rootByAsset))
            ->whereBetween('incurred_on', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['asset_id', 'amount', 'currency'])
            ->each(function (ExpenseEntry $expense) use (&$rows, $rootByAsset): void {
                $rootId = $expense->asset_id !== null
                    ? ($rootByAsset[$expense->asset_id] ?? null)
                    : null;

                if ($rootId !== null && isset($rows[$rootId])) {
                    $currency = $this->currencies->currency($expense->currency);
                    $this->ensureCurrency($rows, $rootId, $currency);
                    $rows[$rootId]['currency_totals'][$currency]['expenses'] +=
                        (float) $expense->amount;
                }
            });
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function ensureCurrency(
        array &$rows,
        int $rootId,
        string $currency,
    ): void {
        $rows[$rootId]['currency_totals'][$currency] ??=
            $this->currencies->empty($currency);
    }
}
