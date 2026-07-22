<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Support\Facades\DB;

class ArchiveAsset
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly AssetHierarchy $hierarchy,
    ) {}

    public function handle(User $actor, Asset $asset): bool
    {
        $this->access->ensureCanManage($actor, $asset);

        return DB::transaction(function () use ($actor, $asset): bool {
            $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $this->access->ensureCanManage($actor, $locked);
            $assetIds = $this->hierarchy->descendantIdsIncluding($locked);
            Asset::query()->whereIn('id', $assetIds)->orderBy('id')->lockForUpdate()->get(['id']);

            $hasActiveLease = Lease::query()
                ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
                ->whereIn('leaseable_id', $assetIds)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveLease) {
                return false;
            }

            Asset::query()->whereIn('id', $assetIds)->update(['status' => 'archived']);

            return true;
        }, 3);
    }
}
