<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use Illuminate\Support\Collection;

final class AssetRootMap
{
    /**
     * @param  Collection<int, Asset>  $assets
     * @return array<int, int>
     */
    public function build(Collection $assets): array
    {
        $parents = $assets->mapWithKeys(
            fn (Asset $asset): array => [$asset->id => $asset->parent_id],
        )->all();
        $roots = [];

        foreach ($assets as $asset) {
            $roots[$asset->id] = $this->rootId($asset->id, $parents);
        }

        return $roots;
    }

    /**
     * @param  array<int, int|null>  $parents
     */
    private function rootId(int $assetId, array $parents): int
    {
        $current = $assetId;
        $visited = [];

        while (isset($parents[$current])) {
            if (isset($visited[$current])) {
                return $assetId;
            }

            $visited[$current] = true;
            $current = (int) $parents[$current];
        }

        return $current;
    }
}
