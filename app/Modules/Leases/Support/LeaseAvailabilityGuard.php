<?php

namespace App\Modules\Leases\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Shared\MorphTypes;
use Illuminate\Validation\ValidationException;

final class LeaseAvailabilityGuard
{
    public function __construct(private readonly MorphTypes $morphTypes) {}

    public function ensureAvailable(Asset $asset, string $status, ?int $exceptLeaseId = null): void
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
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->where('leaseable_id', $asset->id)
            ->where('status', 'active');

        if ($exceptLeaseId !== null) {
            $query->whereKeyNot($exceptLeaseId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.asset_already_leased'),
            ]);
        }
    }
}
