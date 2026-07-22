<?php

namespace App\Modules\Leases\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Shared\MorphTypes;

final class AssetOccupancySynchronizer
{
    public function __construct(private readonly MorphTypes $morphTypes) {}

    public function sync(Asset $asset): void
    {
        if (in_array($asset->occupancy_status, ['maintenance', 'partially_occupied'], true)) {
            return;
        }

        $occupied = Lease::query()
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->where('leaseable_id', $asset->id)
            ->where('status', 'active')
            ->exists();
        $occupancy = $occupied ? 'occupied' : 'vacant';

        if ($asset->occupancy_status !== $occupancy) {
            $asset->update(['occupancy_status' => $occupancy]);
        }
    }
}
