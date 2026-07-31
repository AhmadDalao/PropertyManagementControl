<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Modules\Assets\Support\AssetHierarchy;

final readonly class PropertyExplorerMetricsQuery
{
    public function __construct(private AssetHierarchy $hierarchy) {}

    /**
     * @param  list<int>  $allowedIds
     * @return array<string, int|float>
     */
    public function forNode(Asset $node, array $allowedIds): array
    {
        $assetIds = array_values(array_intersect(
            $this->hierarchy->descendantIdsIncluding($node),
            $allowedIds,
        ));
        $assets = Asset::query()->whereIn('id', $assetIds);
        $leases = Lease::query()
            ->where('portfolio_id', $node->portfolio_id)
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds);
        $collectibleLeaseIds = (clone $leases)
            ->whereIn('status', ['active', 'expired'])
            ->pluck('id');

        return [
            'assets' => count($assetIds),
            'floors' => (clone $assets)->where('asset_type', 'floor')->count(),
            'units' => (clone $assets)->whereIn('asset_type', ['unit', 'space'])->count(),
            'rentable' => (clone $assets)->where('rentable', true)->count(),
            'occupied' => (clone $assets)
                ->where('rentable', true)
                ->whereIn('occupancy_status', ['occupied', 'partially_occupied'])
                ->count(),
            'vacant' => (clone $assets)
                ->where('rentable', true)
                ->where('occupancy_status', 'vacant')
                ->count(),
            'maintenance' => (clone $assets)->where('occupancy_status', 'maintenance')->count(),
            'active_leases' => (clone $leases)->where('status', 'active')->count(),
            'tenants' => (clone $leases)
                ->where('status', 'active')
                ->distinct()
                ->count('tenant_profile_id'),
            'arrears' => (float) LeaseInstallment::query()
                ->whereIn('lease_id', $collectibleLeaseIds)
                ->whereDate('due_date', '<', today())
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN amount_due > amount_paid THEN amount_due - amount_paid ELSE 0 END), 0) AS total'
                )
                ->value('total'),
        ];
    }
}
