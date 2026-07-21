<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class OperationsStatsQuery
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    /** @return array<string, int|float> */
    public function forUser(User $user): array
    {
        $assets = $this->portfolios->apply(Asset::query(), $user);
        $leases = $this->portfolios->apply(Lease::query(), $user);
        $payments = $this->portfolios->apply(Payment::query(), $user);
        $maintenance = $this->portfolios->apply(MaintenanceRequest::query(), $user);
        $expenses = $this->portfolios->apply(ExpenseEntry::query(), $user);

        return [
            'totalUsers' => $this->userCount($user),
            'totalPortfolios' => $user->hasRole('superadmin')
                ? Portfolio::query()->count()
                : (int) ($user->portfolio_id !== null),
            'totalAssets' => (clone $assets)->count(),
            'totalValue' => (float) (clone $assets)->sum('valuation_amount'),
            'activeLeases' => (clone $leases)->where('status', 'active')->count(),
            'monthlyRevenue' => $this->monthlyTotal($payments, 'received_on'),
            'monthlyExpenses' => $this->monthlyTotal($expenses, 'incurred_on'),
            'openRequests' => (clone $maintenance)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'arrears' => $this->arrearsTotal($user),
            'vacantUnits' => (clone $assets)
                ->where('rentable', true)
                ->where('occupancy_status', 'vacant')
                ->count(),
        ];
    }

    private function userCount(User $user): int
    {
        return $user->hasRole('superadmin')
            ? User::query()->count()
            : User::query()->where('portfolio_id', $user->portfolio_id)->count();
    }

    /** @param Builder<Payment>|Builder<ExpenseEntry> $query */
    private function monthlyTotal(Builder $query, string $dateColumn): float
    {
        return (float) (clone $query)
            ->where('status', 'posted')
            ->whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year)
            ->sum('amount');
    }

    private function arrearsTotal(User $user): float
    {
        $leaseIds = $this->portfolios
            ->apply(Lease::query(), $user)
            ->whereIn('status', ['active', 'expired'])
            ->select('id');

        return (float) LeaseInstallment::query()
            ->whereIn('lease_id', $leaseIds)
            ->whereDate('due_date', '<', today())
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN amount_due > amount_paid THEN amount_due - amount_paid ELSE 0 END), 0) AS total'
            )
            ->value('total');
    }
}
