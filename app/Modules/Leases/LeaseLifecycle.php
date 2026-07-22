<?php

namespace App\Modules\Leases;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Leases\Actions\InstallmentSchedule;
use App\Modules\Leases\Support\AssetOccupancySynchronizer;
use App\Modules\Leases\Support\LeaseAvailabilityGuard;
use App\Modules\Leases\Support\LeaseTransitionGuard;
use Illuminate\Support\Facades\DB;

final class LeaseLifecycle
{
    public function __construct(
        private readonly InstallmentSchedule $installments,
        private readonly LeaseAvailabilityGuard $availability,
        private readonly LeaseTransitionGuard $transitions,
        private readonly AssetOccupancySynchronizer $occupancy,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(Asset $asset, array $attributes): Lease
    {
        return DB::transaction(function () use ($asset, $attributes): Lease {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $this->availability->ensureAvailable($lockedAsset, (string) $attributes['status']);
            $lease = Lease::query()->create($attributes);
            $this->installments->sync($lease);
            $this->occupancy->sync($lockedAsset);

            return $lease->fresh(['installments']);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Lease $lease, array $attributes): Lease
    {
        return DB::transaction(function () use ($lease, $attributes): Lease {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);
            $targetStatus = (string) ($attributes['status'] ?? $lockedLease->status);
            $this->transitions->ensureAllowed($lockedLease->status, $targetStatus);
            $asset = $lockedLease->leaseable instanceof Asset
                ? Asset::query()->lockForUpdate()->findOrFail($lockedLease->leaseable_id)
                : null;

            if ($asset) {
                $this->availability->ensureAvailable($asset, $targetStatus, $lockedLease->id);
            }

            $lockedLease->update($attributes);

            if ($asset) {
                $this->occupancy->sync($asset);
            }

            return $lockedLease->fresh(['installments']);
        });
    }

    /** @return array{expired_leases:int, installment_statuses:int} */
    public function synchronize(): array
    {
        $expiredLeases = 0;

        Lease::query()
            ->where('status', 'active')
            ->whereDate('ends_at', '<', today())
            ->orderBy('id')
            ->chunkById(100, function ($leases) use (&$expiredLeases): void {
                foreach ($leases as $lease) {
                    $this->update($lease, ['status' => 'expired']);
                    $expiredLeases++;
                }
            });

        return [
            'expired_leases' => $expiredLeases,
            'installment_statuses' => $this->installments->refreshStatuses(),
        ];
    }
}
