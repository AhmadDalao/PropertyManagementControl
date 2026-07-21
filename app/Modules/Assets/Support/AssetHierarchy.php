<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Modules\Shared\MorphTypes;

class AssetHierarchy
{
    public function __construct(private readonly MorphTypes $morphTypes) {}

    /**
     * @return array<int, string>
     */
    public function leaseableTypes(): array
    {
        return $this->morphTypes->for(new Asset);
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
