<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Modules\Assets\Data\AssetOperationsRecordsData;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class AssetOperationsRecordsQuery
{
    private const LIMIT = 8;

    public function __construct(
        private AssetHierarchy $hierarchy,
        private AssetOperationsDocumentsQuery $documents,
    ) {}

    /**
     * @param  array<int, int>  $assetIds
     * @param  array<int, int>  $leaseIds
     * @param  array<int, int>  $collectibleLeaseIds
     */
    public function get(
        Asset $asset,
        array $assetIds,
        array $leaseIds,
        array $collectibleLeaseIds,
    ): AssetOperationsRecordsData {
        return new AssetOperationsRecordsData(
            rentableAssets: $this->rentableAssets($asset, $assetIds),
            leases: $this->leases($asset, $assetIds),
            collectionQueue: $this->collections($collectibleLeaseIds),
            maintenance: $this->maintenance($asset, $assetIds),
            expenses: $this->expenses($asset, $assetIds),
            documents: $this->documents->get($asset, $assetIds, $leaseIds),
            directActiveLease: $asset->rentable
                ? $this->directActiveLease($asset)
                : null,
        );
    }

    /**
     * @param  array<int, int>  $assetIds
     * @return Collection<int, Asset>
     */
    private function rentableAssets(Asset $asset, array $assetIds): Collection
    {
        return Asset::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('id', $assetIds)
            ->where('rentable', true)
            ->with([
                'leases' => fn ($leases) => $leases
                    ->where('status', 'active')
                    ->with(['tenantProfile:id,user_id', 'tenantProfile.user:id,name'])
                    ->latest('started_at'),
            ])
            ->orderByRaw("CASE WHEN occupancy_status = 'vacant' THEN 0 ELSE 1 END")
            ->orderBy('title_en')
            ->limit(self::LIMIT)
            ->get([
                'id', 'parent_id', 'title_en', 'title_ar', 'code',
                'asset_type', 'occupancy_status', 'currency',
            ]);
    }

    /**
     * @param  array<int, int>  $assetIds
     * @return Collection<int, Lease>
     */
    private function leases(Asset $asset, array $assetIds): Collection
    {
        return $this->leaseQuery($asset, $assetIds)
            ->select([
                'id', 'portfolio_id', 'tenant_profile_id', 'leaseable_type',
                'leaseable_id', 'code', 'status', 'currency', 'started_at', 'ends_at',
            ])
            ->with([
                'tenantProfile:id,user_id',
                'tenantProfile.user:id,name',
                'leaseable',
            ])
            ->withSum('installments as installments_due_total', 'amount_due')
            ->withSum('installments as installments_paid_total', 'amount_paid')
            ->orderByDesc('started_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @param  array<int, int>  $leaseIds
     * @return Collection<int, LeaseInstallment>
     */
    private function collections(array $leaseIds): Collection
    {
        return LeaseInstallment::query()
            ->whereIn('lease_id', $leaseIds)
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->whereDate('due_date', '<=', today()->addDays(30))
            ->with([
                'lease:id,tenant_profile_id,leaseable_type,leaseable_id,code,currency',
                'lease.tenantProfile:id,user_id',
                'lease.tenantProfile.user:id,name',
                'lease.leaseable',
            ])
            ->orderBy('due_date')
            ->orderBy('sequence')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @param  array<int, int>  $assetIds
     * @return Collection<int, MaintenanceRequest>
     */
    private function maintenance(Asset $asset, array $assetIds): Collection
    {
        return MaintenanceRequest::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->whereIn('asset_id', $assetIds)
            ->with([
                'asset:id,title_en,title_ar,code',
                'tenantProfile.user:id,name',
                'assignedTo:id,name',
            ])
            ->latest('requested_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * @param  array<int, int>  $assetIds
     * @return Collection<int, ExpenseEntry>
     */
    private function expenses(Asset $asset, array $assetIds): Collection
    {
        return $this->expenseQuery($asset, $assetIds)
            ->with([
                'asset:id,title_en,title_ar,code',
                'lease:id,leaseable_type,leaseable_id',
                'lease.leaseable',
            ])
            ->latest('incurred_on')
            ->limit(self::LIMIT)
            ->get();
    }

    private function directActiveLease(Asset $asset): ?Lease
    {
        return $this->leaseQuery($asset, [$asset->id])
            ->where('status', 'active')
            ->with(['tenantProfile:id,user_id', 'tenantProfile.user:id,name'])
            ->withSum('installments as installments_due_total', 'amount_due')
            ->withSum('installments as installments_paid_total', 'amount_paid')
            ->first();
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

    /**
     * @param  array<int, int>  $assetIds
     * @return Builder<ExpenseEntry>
     */
    private function expenseQuery(Asset $asset, array $assetIds): Builder
    {
        return ExpenseEntry::query()
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
    }
}
