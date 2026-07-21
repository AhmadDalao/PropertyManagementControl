<?php

namespace App\Modules\Assets\Queries;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Support\Collection;

class PropertyMapActivityQuery
{
    public function __construct(private readonly AssetHierarchy $hierarchy) {}

    /**
     * @param  Collection<int, int>  $assetIds
     * @return array{leases:Collection<int, int>,maintenance:Collection<int, int>}
     */
    public function forAssetIds(Collection $assetIds): array
    {
        if ($assetIds->isEmpty()) {
            return ['leases' => collect(), 'maintenance' => collect()];
        }

        $leaseCounts = Lease::query()
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds)
            ->where('status', 'active')
            ->selectRaw('leaseable_id, COUNT(*) as aggregate')
            ->groupBy('leaseable_id')
            ->pluck('aggregate', 'leaseable_id')
            ->map(fn (mixed $count): int => (int) $count);
        $maintenanceCounts = MaintenanceRequest::query()
            ->whereIn('asset_id', $assetIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->selectRaw('asset_id, COUNT(*) as aggregate')
            ->groupBy('asset_id')
            ->pluck('aggregate', 'asset_id')
            ->map(fn (mixed $count): int => (int) $count);

        /** @var Collection<int, int> $leaseCounts */
        /** @var Collection<int, int> $maintenanceCounts */
        return ['leases' => $leaseCounts, 'maintenance' => $maintenanceCounts];
    }
}
