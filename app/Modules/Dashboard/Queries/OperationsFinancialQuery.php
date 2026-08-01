<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Dashboard\Support\OperationsCurrencySummary;
use App\Modules\Shared\PortfolioScope;

final readonly class OperationsFinancialQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private OperationsCurrencySummary $currencies,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(User $actor, DashboardPropertyContext $context): array
    {
        $leases = $context
            ->leases($this->portfolios->apply(Lease::query(), $actor))
            ->whereIn('status', ['active', 'expired'])
            ->get(['id', 'currency']);
        $installments = LeaseInstallment::query()
            ->whereIn('lease_id', $leases->pluck('id'))
            ->whereDate('due_date', '<=', now()->endOfMonth())
            ->get(['lease_id', 'due_date', 'amount_due', 'amount_paid']);
        $payments = $context->leaseRecords(
            $this->portfolios->apply(Payment::query(), $actor),
        )
            ->where('status', 'posted')
            ->whereDate('received_on', '>=', now()->startOfMonth())
            ->whereDate('received_on', '<=', now()->endOfMonth())
            ->get(['currency', 'amount']);
        $expenses = $context->assetRecords(
            $this->portfolios->apply(ExpenseEntry::query(), $actor),
        )
            ->where('status', 'posted')
            ->whereDate('incurred_on', '>=', now()->startOfMonth())
            ->whereDate('incurred_on', '<=', now()->endOfMonth())
            ->get(['currency', 'amount']);
        $currencyTotals = $this->currencies->summarize(
            $actor,
            $leases,
            $installments,
            $payments,
            $expenses,
        );
        $singleCurrency = count($currencyTotals) === 1
            ? $currencyTotals[0]
            : null;

        return [
            'currency' => $singleCurrency['currency'] ?? null,
            'currencyCount' => count($currencyTotals),
            'currencyTotals' => $currencyTotals,
            'scheduledDue' => $singleCurrency['scheduledDue'] ?? null,
            'scheduledPaid' => $singleCurrency['scheduledPaid'] ?? null,
            'collectionRate' => $singleCurrency['collectionRate'] ?? null,
            'revenue' => $singleCurrency['revenue'] ?? null,
            'expenses' => $singleCurrency['expenses'] ?? null,
            'net' => $singleCurrency['net'] ?? null,
            'arrears' => $singleCurrency['arrears'] ?? null,
            'hasArrears' => collect($currencyTotals)->contains(
                fn (array $total): bool => $total['arrears'] > 0,
            ),
        ];
    }
}
