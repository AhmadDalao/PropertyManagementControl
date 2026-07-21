<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use Illuminate\Support\Collection;

class PropertyMapHierarchy
{
    /**
     * @param  Collection<int, Asset>  $candidates
     * @param  Collection<int, Asset>  $nodes
     * @return Collection<int, Collection<int, int>>
     */
    public function descendants(Collection $candidates, Collection $nodes): Collection
    {
        /** @var Collection<int, Collection<int, Asset>> $children */
        $children = $nodes->groupBy(
            fn (Asset $asset): int => (int) ($asset->parent_id ?? 0),
        );

        return $candidates->mapWithKeys(
            fn (Asset $asset): array => [
                $asset->id => collect($this->descendantIds($asset->id, $children)),
            ],
        );
    }

    /**
     * @param  Collection<int, Collection<int, int>>  $descendants
     * @return Collection<int, int>
     */
    public function scopedIds(Collection $descendants): Collection
    {
        return $descendants
            ->flatten()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, Collection<int, Asset>>  $children
     * @return array<int, int>
     */
    private function descendantIds(int $assetId, Collection $children): array
    {
        $ids = [$assetId];
        $queue = [$assetId];

        while ($queue !== []) {
            $parentId = array_shift($queue);

            foreach ($children->get($parentId, collect()) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }
}
