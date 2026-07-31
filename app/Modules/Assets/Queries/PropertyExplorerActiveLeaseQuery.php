<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class PropertyExplorerActiveLeaseQuery
{
    public function __construct(private AssetHierarchy $hierarchy) {}

    /** @param Collection<int, Asset> $assets */
    public function attach(Collection $assets): void
    {
        if ($assets->isEmpty()) {
            return;
        }

        $leases = Lease::query()
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $assets->pluck('id')->all())
            ->where('status', 'active')
            ->with([
                'tenantProfile.user:id,name,email,phone',
                'installments:id,lease_id,due_date,amount_due,amount_paid,status',
            ])
            ->latest('started_at')
            ->latest('id')
            ->get()
            ->groupBy('leaseable_id');

        foreach ($assets as $asset) {
            $asset->setRelation(
                'leases',
                new EloquentCollection($leases->get($asset->id, collect())->take(1)->all()),
            );
        }
    }
}
