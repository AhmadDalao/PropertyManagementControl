<?php

namespace App\Modules\Leases;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Leases\Actions\InstallmentSchedule;
use App\Modules\Shared\MorphTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaseLifecycle
{
    public function __construct(
        private readonly InstallmentSchedule $installments,
        private readonly MorphTypes $morphTypes,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Asset $asset, array $attributes): Lease
    {
        return DB::transaction(function () use ($asset, $attributes): Lease {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $this->guardActiveLeaseAvailability($lockedAsset, (string) $attributes['status']);

            $lease = Lease::query()->create($attributes);
            $this->installments->sync($lease);
            $this->syncAssetOccupancy($lockedAsset);

            return $lease->fresh(['installments']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lease $lease, array $attributes, bool $resyncInstallments = false): Lease
    {
        return DB::transaction(function () use ($lease, $attributes, $resyncInstallments): Lease {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);

            if ($lockedLease->status === 'terminated' && ($attributes['status'] ?? null) !== 'terminated') {
                throw ValidationException::withMessages([
                    'status' => trans('app.errors.lease_terminated_locked'),
                ]);
            }

            $asset = in_array($lockedLease->leaseable_type, $this->assetTypes(), true)
                ? Asset::query()->lockForUpdate()->findOrFail($lockedLease->leaseable_id)
                : null;

            if ($asset) {
                $this->guardActiveLeaseAvailability(
                    $asset,
                    (string) ($attributes['status'] ?? $lockedLease->status),
                    $lockedLease->id,
                );
            }

            $lockedLease->update($attributes);

            if ($resyncInstallments && $lockedLease->payments()->doesntExist()) {
                $this->installments->sync($lockedLease);
            }

            if ($asset) {
                $this->syncAssetOccupancy($asset);
            }

            return $lockedLease->fresh(['installments']);
        });
    }

    /**
     * @return array{expired_leases:int, installment_statuses:int}
     */
    public function synchronize(): array
    {
        $expiredLeases = 0;

        Lease::query()
            ->where('status', 'active')
            ->whereDate('ends_at', '<', today())
            ->orderBy('id')
            ->chunkById(100, function ($leases) use (&$expiredLeases): void {
                /** @var Lease $lease */
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

    private function guardActiveLeaseAvailability(Asset $asset, string $status, ?int $exceptLeaseId = null): void
    {
        if ($status !== 'active') {
            return;
        }

        if (! $asset->rentable || $asset->status !== 'active') {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.asset_not_rentable'),
            ]);
        }

        $query = Lease::query()
            ->whereIn('leaseable_type', $this->assetTypes())
            ->where('leaseable_id', $asset->id)
            ->where('status', 'active');

        if ($exceptLeaseId) {
            $query->whereKeyNot($exceptLeaseId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.asset_already_leased'),
            ]);
        }
    }

    private function syncAssetOccupancy(Asset $asset): void
    {
        if (in_array($asset->occupancy_status, ['maintenance', 'partially_occupied'], true)) {
            return;
        }

        $occupied = Lease::query()
            ->whereIn('leaseable_type', $this->assetTypes())
            ->where('leaseable_id', $asset->id)
            ->where('status', 'active')
            ->exists();

        $occupancy = $occupied ? 'occupied' : 'vacant';

        if ($asset->occupancy_status !== $occupancy) {
            $asset->update(['occupancy_status' => $occupancy]);
        }
    }

    /** @return array<int, string> */
    private function assetTypes(): array
    {
        return $this->morphTypes->for(new Asset);
    }
}
