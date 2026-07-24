<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Modules\Assets\Data\AssetOperationsData;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Database\Eloquent\Builder;

final readonly class AssetOperationsQuery
{
    public function __construct(
        private AssetHierarchy $hierarchy,
        private AssetOperationsRecordsQuery $records,
    ) {}

    public function get(Asset $asset): AssetOperationsData
    {
        $assetIds = $this->hierarchy->descendantIdsIncluding($asset);
        $leaseQuery = $this->leaseQuery($asset, $assetIds);
        $leaseIds = (clone $leaseQuery)->pluck('id');
        $collectibleLeaseIds = (clone $leaseQuery)
            ->whereIn('status', ['active', 'expired'])
            ->pluck('id');
        $maintenanceQuery = MaintenanceRequest::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('asset_id', $assetIds);
        $expenseQuery = ExpenseEntry::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->where(function (Builder $expenses) use ($assetIds): void {
                $expenses
                    ->whereIn('asset_id', $assetIds)
                    ->orWhereHas('lease', fn (Builder $leases) => $leases
                        ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
                        ->whereIn('leaseable_id', $assetIds))
                    ->orWhereHas('maintenanceRequest', fn (Builder $requests) => $requests
                        ->whereIn('asset_id', $assetIds));
            });
        $month = [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ];
        $monthlyInstallments = LeaseInstallment::query()
            ->whereIn('lease_id', $collectibleLeaseIds)
            ->whereBetween('due_date', $month);
        $rentableQuery = Asset::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('id', $assetIds)
            ->where('rentable', true);
        $records = $this->records->get(
            $asset,
            $assetIds,
            $leaseIds->all(),
            $collectibleLeaseIds->all(),
        );

        return new AssetOperationsData(
            propertyRoot: $this->hierarchy->root($asset),
            assetIds: $assetIds,
            rentableAssets: $records->rentableAssets,
            leases: $records->leases,
            collectionQueue: $records->collectionQueue,
            maintenance: $records->maintenance,
            expenses: $records->expenses,
            documents: $records->documents,
            directActiveLease: $records->directActiveLease,
            descendantCount: max(0, count($assetIds) - 1),
            rentableCount: (clone $rentableQuery)->count(),
            occupiedCount: (clone $rentableQuery)
                ->whereIn('occupancy_status', ['occupied', 'partially_occupied'])
                ->count(),
            vacantCount: (clone $rentableQuery)->where('occupancy_status', 'vacant')->count(),
            leaseCount: (clone $leaseQuery)->count(),
            activeLeaseCount: (clone $leaseQuery)->where('status', 'active')->count(),
            expiringLeaseCount: (clone $leaseQuery)
                ->where('status', 'active')
                ->whereBetween('ends_at', [today(), today()->addDays(90)])
                ->count(),
            openMaintenanceCount: (clone $maintenanceQuery)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            monthlyScheduledDue: (float) (clone $monthlyInstallments)->sum('amount_due'),
            monthlyScheduledPaid: (float) (clone $monthlyInstallments)->sum('amount_paid'),
            arrears: $this->arrears($collectibleLeaseIds->all()),
            monthlyRevenue: $this->monthlyRevenue($asset, $leaseIds->all(), $month),
            monthlyExpenses: $this->monthlyExpenses(clone $expenseQuery, $month),
            postedExpenseTotal: (float) (clone $expenseQuery)
                ->where('status', 'posted')
                ->sum('amount'),
        );
    }

    /**
     * @param  array<int, int>  $assetIds
     * @return Builder<Lease>
     */
    private function leaseQuery(Asset $asset, array $assetIds): Builder
    {
        return Lease::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds);
    }

    /** @param array<int, int> $leaseIds */
    private function arrears(array $leaseIds): float
    {
        return (float) LeaseInstallment::query()
            ->whereIn('lease_id', $leaseIds)
            ->whereDate('due_date', '<', today())
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN amount_due > amount_paid THEN amount_due - amount_paid ELSE 0 END), 0) AS total'
            )
            ->value('total');
    }

    /**
     * @param  array<int, int>  $leaseIds
     * @param  array<int, string>  $month
     */
    private function monthlyRevenue(Asset $asset, array $leaseIds, array $month): float
    {
        return (float) Payment::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('lease_id', $leaseIds)
            ->where('status', 'posted')
            ->whereBetween('received_on', $month)
            ->sum('amount');
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @param  array<int, string>  $month
     */
    private function monthlyExpenses(Builder $query, array $month): float
    {
        return (float) $query
            ->where('status', 'posted')
            ->whereBetween('incurred_on', $month)
            ->sum('amount');
    }
}
