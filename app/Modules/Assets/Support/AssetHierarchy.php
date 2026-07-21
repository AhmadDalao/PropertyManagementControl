<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;

class AssetHierarchy
{
    /**
     * @return array<int, string>
     */
    public function leaseableTypes(): array
    {
        $asset = new Asset;

        return array_values(array_unique([Asset::class, $asset->getMorphClass()]));
    }

    /**
     * @return array<int, int>
     */
    public function descendantIdsIncluding(Asset $asset): array
    {
        $ids = [$asset->id];
        $stack = [$asset->id];

        while ($stack !== []) {
            $children = Asset::query()->whereIn('parent_id', $stack)->pluck('id')->all();
            $stack = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique([...$ids, ...$children]));
        }

        return $ids;
    }
}
