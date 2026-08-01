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
        private AssetOperationsCurrencyQuery $currencies,
    ) {}

    public function get(Asset $asset): AssetOperationsData
    {
        $assetIds = $this->hierarchy->descendantIdsIncluding($asset);
        $leaseQuery = $this->leaseQuery($asset, $assetIds);
        $leaseIds = (clone $leaseQuery)->pluck('id');
        $collectibleLeases = (clone $leaseQuery)
            ->whereIn('status', ['active', 'expired'])
            ->get(['id', 'currency']);
        $collectibleLeaseIds = $collectibleLeases->pluck('id');
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
        $financialInstallments = LeaseInstallment::query()
            ->whereIn('lease_id', $collectibleLeaseIds)
            ->whereDate('due_date', '<=', $month[1])
            ->get(['lease_id', 'due_date', 'amount_due', 'amount_paid']);
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
        $monthlyPayments = Payment::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('lease_id', $leaseIds)
            ->where('status', 'posted')
            ->whereDate('received_on', '>=', $month[0])
            ->whereDate('received_on', '<=', $month[1])
            ->get(['currency', 'amount']);
        $postedExpenses = (clone $expenseQuery)
            ->where('status', 'posted')
            ->get(['currency', 'amount', 'incurred_on']);
        $currencyTotals = $this->currencies->summarize(
            $asset,
            $collectibleLeases,
            $financialInstallments,
            $monthlyPayments,
            $postedExpenses,
        );
        $singleCurrency = count($currencyTotals) === 1
            ? $currencyTotals[0]
            : null;

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
            monthlyScheduledDue: $singleCurrency['monthlyScheduledDue'] ?? null,
            monthlyScheduledPaid: $singleCurrency['monthlyScheduledPaid'] ?? null,
            arrears: $singleCurrency['arrears'] ?? null,
            monthlyRevenue: $singleCurrency['monthlyRevenue'] ?? null,
            monthlyExpenses: $singleCurrency['monthlyExpenses'] ?? null,
            postedExpenseTotal: $singleCurrency['postedExpenseTotal'] ?? null,
            currencyTotals: $currencyTotals,
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
}
