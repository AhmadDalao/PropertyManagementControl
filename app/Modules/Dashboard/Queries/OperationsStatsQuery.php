<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class OperationsStatsQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    /** @return array<string, int|float> */
    public function forUser(User $user, DashboardPropertyContext $context): array
    {
        $assets = $context->assets(
            $this->portfolios->apply(Asset::query(), $user),
        );
        $leases = $context->leases(
            $this->portfolios->apply(Lease::query(), $user),
        );
        $payments = $context->leaseRecords(
            $this->portfolios->apply(Payment::query(), $user),
        );
        $maintenance = $context->assetRecords(
            $this->portfolios->apply(MaintenanceRequest::query(), $user),
        );
        $expenses = $context->assetRecords(
            $this->portfolios->apply(ExpenseEntry::query(), $user),
        );

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
            'arrears' => $this->arrearsTotal($user, $context),
            'vacantUnits' => (clone $assets)
                ->where('rentable', true)
                ->where('occupancy_status', 'vacant')
                ->count(),
        ];
    }

    private function userCount(User $user): int
    {
        if ($user->hasRole('superadmin')) {
            return User::query()->count();
        }

        $users = User::query()->where('portfolio_id', $user->portfolio_id);

        if ($this->assignments->restricts($user)) {
            $tenantIds = $this->assignments
                ->tenants(TenantProfile::query(), $user)
                ->select('id');
            $users->where(function (Builder $visible) use ($user, $tenantIds): void {
                $visible
                    ->whereKey($user->id)
                    ->orWhereHas(
                        'tenantProfile',
                        fn (Builder $tenants) => $tenants->whereIn('id', clone $tenantIds),
                    );
            });
        }

        return $users->count();
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

    private function arrearsTotal(User $user, DashboardPropertyContext $context): float
    {
        $leaseIds = $context
            ->leases($this->portfolios->apply(Lease::query(), $user))
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
