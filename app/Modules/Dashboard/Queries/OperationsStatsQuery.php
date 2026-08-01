<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
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

    /** @return array<string, mixed> */
    public function forUser(User $user, DashboardPropertyContext $context): array
    {
        $assets = $context->assets(
            $this->portfolios->apply(Asset::query(), $user),
        );
        $leases = $context->leases(
            $this->portfolios->apply(Lease::query(), $user),
        );
        $maintenance = $context->assetRecords(
            $this->portfolios->apply(MaintenanceRequest::query(), $user),
        );
        $valuationTotals = $this->valuationTotals(clone $assets);
        $singleValuation = count($valuationTotals) === 1
            ? $valuationTotals[0]
            : null;

        return [
            'totalUsers' => $this->userCount($user),
            'totalPortfolios' => $user->hasRole('superadmin')
                ? Portfolio::query()->count()
                : (int) ($user->portfolio_id !== null),
            'totalAssets' => (clone $assets)->count(),
            'totalValue' => $singleValuation['amount'] ?? null,
            'valuationCurrency' => $singleValuation['currency'] ?? null,
            'valuationTotals' => $valuationTotals,
            'activeLeases' => (clone $leases)->where('status', 'active')->count(),
            'openRequests' => (clone $maintenance)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
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

    /**
     * @param  Builder<Asset>  $assets
     * @return list<array{currency:string,amount:float}>
     */
    private function valuationTotals(Builder $assets): array
    {
        $grouped = $assets
            ->get(['currency', 'valuation_amount'])
            ->groupBy(fn (Asset $asset): string => $this->currency($asset->currency))
            ->sortKeys();
        $totals = [];

        foreach ($grouped as $currency => $records) {
            $totals[] = [
                'currency' => $currency,
                'amount' => (float) $records->sum('valuation_amount'),
            ];
        }

        return $totals;
    }

    private function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }
}
