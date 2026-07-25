<?php

namespace App\Modules\Shared\Authorization;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\User;

final class ManagerAssetAssignments
{
    /**
     * @var array<int, array{assigned:list<int>,assets:list<int>,roots:list<int>}>
     */
    private array $cache = [];

    /** @var array<int, bool> */
    private array $hasAnyCache = [];

    public function restricts(User $actor): bool
    {
        return $actor->hasRole('property_manager')
            && ! $actor->hasAnyRole(['superadmin', 'owner']);
    }

    /**
     * @return array{assigned:list<int>,assets:list<int>,roots:list<int>}
     */
    public function for(User $actor): array
    {
        if (! $this->restricts($actor)) {
            return ['assigned' => [], 'assets' => [], 'roots' => []];
        }

        if (! isset($this->cache[$actor->id])) {
            $this->cache[$actor->id] = $this->resolve($actor);
            $this->hasAnyCache[$actor->id] = $this->cache[$actor->id]['assigned'] !== [];
        }

        return $this->cache[$actor->id];
    }

    public function hasAny(User $actor): bool
    {
        if (! $this->restricts($actor)) {
            return true;
        }

        return $this->hasAnyCache[$actor->id] ??= AssetStakeholder::query()
            ->where('portfolio_id', $actor->portfolio_id ?? 0)
            ->where('user_id', $actor->id)
            ->where('relationship_type', 'manager')
            ->whereNull('ends_on')
            ->exists();
    }

    /**
     * @return array{assigned:list<int>,assets:list<int>,roots:list<int>}
     */
    private function resolve(User $actor): array
    {
        if ($actor->portfolio_id === null) {
            return ['assigned' => [], 'assets' => [], 'roots' => []];
        }

        /** @var array<int, int|null> $parents */
        $parents = Asset::query()
            ->where('portfolio_id', $actor->portfolio_id)
            ->pluck('parent_id', 'id')
            ->mapWithKeys(fn (mixed $parentId, mixed $id): array => [
                (int) $id => $parentId === null ? null : (int) $parentId,
            ])
            ->all();
        $assigned = AssetStakeholder::query()
            ->where('portfolio_id', $actor->portfolio_id)
            ->where('user_id', $actor->id)
            ->where('relationship_type', 'manager')
            ->whereNull('ends_on')
            ->whereIn('asset_id', array_keys($parents))
            ->distinct()
            ->pluck('asset_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        if ($assigned === []) {
            return ['assigned' => [], 'assets' => [], 'roots' => []];
        }

        $assigned = array_values($assigned);
        $children = [];

        foreach ($parents as $id => $parentId) {
            if ($parentId !== null) {
                $children[$parentId][] = $id;
            }
        }

        $assets = $this->descendants($assigned, $children);
        $roots = array_values(array_unique(array_map(
            fn (int $id): int => $this->rootId($id, $parents),
            $assigned,
        )));
        sort($assigned);
        sort($assets);
        sort($roots);

        return compact('assigned', 'assets', 'roots');
    }

    /**
     * @param  list<int>  $assigned
     * @param  array<int, list<int>>  $children
     * @return list<int>
     */
    private function descendants(array $assigned, array $children): array
    {
        $seen = [];
        $stack = $assigned;

        while ($stack !== []) {
            $id = array_pop($stack);

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;

            foreach ($children[$id] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return array_map('intval', array_keys($seen));
    }

    /**
     * @param  array<int, int|null>  $parents
     */
    private function rootId(int $assetId, array $parents): int
    {
        $current = $assetId;
        $seen = [];

        while (($parents[$current] ?? null) !== null && ! isset($seen[$current])) {
            $seen[$current] = true;
            $current = (int) $parents[$current];
        }

        return $current;
    }
}
