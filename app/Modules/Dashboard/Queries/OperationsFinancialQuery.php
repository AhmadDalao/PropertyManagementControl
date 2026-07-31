<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

final readonly class OperationsFinancialQuery
{
    public function __construct(private PortfolioScope $portfolios) {}

    /** @return array<string, float|string> */
    public function forUser(User $actor, DashboardPropertyContext $context): array
    {
        $leaseIds = $context
            ->leases($this->portfolios->apply(Lease::query(), $actor))
            ->whereIn('status', ['active', 'expired'])
            ->select('id');
        $installments = LeaseInstallment::query()
            ->whereIn('lease_id', $leaseIds)
            ->whereDate('due_date', '>=', now()->startOfMonth())
            ->whereDate('due_date', '<=', now()->endOfMonth());
        $scheduledDue = (float) (clone $installments)->sum('amount_due');
        $scheduledPaid = (float) (clone $installments)->sum('amount_paid');
        $revenue = $this->monthlyTotal(
            $context->leaseRecords(
                $this->portfolios->apply(Payment::query(), $actor),
            ),
            'received_on',
        );
        $expenses = $this->monthlyTotal(
            $context->assetRecords(
                $this->portfolios->apply(ExpenseEntry::query(), $actor),
            ),
            'incurred_on',
        );

        return [
            'scheduledDue' => $scheduledDue,
            'scheduledPaid' => $scheduledPaid,
            'collectionRate' => $scheduledDue > 0
                ? round(min(100, ($scheduledPaid / $scheduledDue) * 100), 2)
                : 0,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => $revenue - $expenses,
            'currency' => $this->currency($actor),
        ];
    }

    /** @param Builder<Payment>|Builder<ExpenseEntry> $query */
    private function monthlyTotal(Builder $query, string $dateColumn): float
    {
        return (float) $query
            ->where('status', 'posted')
            ->whereDate($dateColumn, '>=', now()->startOfMonth())
            ->whereDate($dateColumn, '<=', now()->endOfMonth())
            ->sum('amount');
    }

    private function currency(User $actor): string
    {
        if ($actor->portfolio_id) {
            return (string) (Portfolio::query()
                ->whereKey($actor->portfolio_id)
                ->value('default_currency') ?: 'SAR');
        }

        return 'SAR';
    }
}
