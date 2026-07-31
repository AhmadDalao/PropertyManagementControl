<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class PropertyExplorerSelectionQuery
{
    public function __construct(
        private AssetAccess $access,
        private AssetHierarchy $hierarchy,
        private PropertyExplorerActiveLeaseQuery $activeLeases,
    ) {}

    /**
     * @param  array{property_id:int|null,node_id:int|null}  $filters
     * @return array{
     *     roots:Collection<int, Asset>,
     *     root:Asset|null,
     *     node:Asset|null,
     *     allowed_ids:list<int>,
     *     breadcrumbs:list<Asset>
     * }
     */
    public function resolve(User $actor, array $filters): array
    {
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';
        $roots = $this->access->directoryScope(Asset::query(), $actor)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->with('portfolio:id,code,name_en,name_ar')
            ->orderBy($titleColumn)
            ->orderBy('code')
            ->get();

        if ($roots->isEmpty()) {
            return $this->emptySelection($roots);
        }

        $root = $filters['property_id'] === null
            ? $roots->first()
            : $roots->firstWhere('id', $filters['property_id']);
        abort_unless($root instanceof Asset, 403, trans('app.errors.property_filter_access_denied'));

        $descendantIds = $this->hierarchy->descendantIdsIncluding($root);
        $allowedIds = $this->access->directoryScope(Asset::query(), $actor)
            ->whereIn('id', $descendantIds)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
        $allowedIds = array_values($allowedIds);
        $nodeId = $filters['node_id'] ?? $root->id;
        $nodes = Asset::query()
            ->whereIn('id', $allowedIds)
            ->get([
                'id',
                'portfolio_id',
                'parent_id',
                'asset_type',
                'usage_type',
                'title_en',
                'title_ar',
                'code',
                'status',
                'occupancy_status',
                'rentable',
                'valuation_amount',
                'currency',
                'area',
                'address',
                'address_ar',
                'sort_order',
            ])
            ->keyBy('id');
        $node = $nodes->get($nodeId);
        abort_unless($node instanceof Asset, 403, trans('app.errors.property_assignment_access_denied'));
        $node->load([
            'parent:id,title_en,title_ar,code',
            'currentStakeholders.user:id,name',
        ]);
        $this->activeLeases->attach(new EloquentCollection([$node]));
        $node->loadCount('children');

        return [
            'roots' => $roots,
            'root' => $root,
            'node' => $node,
            'allowed_ids' => $allowedIds,
            'breadcrumbs' => $this->breadcrumbs($node, $nodes),
        ];
    }

    /**
     * @param  Collection<int, Asset>  $nodes
     * @return list<Asset>
     */
    private function breadcrumbs(Asset $node, Collection $nodes): array
    {
        $breadcrumbs = [];
        $current = $node;
        $visited = [];

        while (! isset($visited[$current->id])) {
            $visited[$current->id] = true;
            array_unshift($breadcrumbs, $current);

            if ($current->parent_id === null) {
                break;
            }

            $parent = $nodes->get($current->parent_id);

            if (! $parent instanceof Asset) {
                break;
            }

            $current = $parent;
        }

        return $breadcrumbs;
    }

    /**
     * @param  Collection<int, Asset>  $roots
     * @return array{
     *     roots:Collection<int, Asset>,
     *     root:null,
     *     node:null,
     *     allowed_ids:list<int>,
     *     breadcrumbs:list<Asset>
     * }
     */
    private function emptySelection(Collection $roots): array
    {
        return [
            'roots' => $roots,
            'root' => null,
            'node' => null,
            'allowed_ids' => [],
            'breadcrumbs' => [],
        ];
    }
}
