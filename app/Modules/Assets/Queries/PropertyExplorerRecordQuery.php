<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class PropertyExplorerRecordQuery
{
    public function __construct(
        private AssetHierarchy $hierarchy,
        private PropertyExplorerActiveLeaseQuery $activeLeases,
    ) {}

    /**
     * @param  list<int>  $allowedIds
     * @param  array{search:string,asset_type:string,occupancy_status:string}  $filters
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginate(Asset $node, array $allowedIds, array $filters): LengthAwarePaginator
    {
        $search = $filters['search'];
        $filtered = $search !== ''
            || $filters['asset_type'] !== 'all'
            || $filters['occupancy_status'] !== 'all';
        $scopeIds = array_values(array_intersect(
            $this->hierarchy->descendantIdsIncluding($node),
            $allowedIds,
        ));
        $query = Asset::query()
            ->whereIn('id', $scopeIds)
            ->where('id', '!=', $node->id)
            ->when(
                ! $filtered,
                fn (Builder $assets) => $assets->where('parent_id', $node->id),
            )
            ->when(
                $filters['asset_type'] !== 'all',
                fn (Builder $assets) => $assets->where('asset_type', $filters['asset_type']),
            )
            ->when(
                $filters['occupancy_status'] !== 'all',
                fn (Builder $assets) => $assets->where('occupancy_status', $filters['occupancy_status']),
            )
            ->when($search !== '', fn (Builder $assets) => $this->search($assets, $search))
            ->with([
                'parent:id,title_en,title_ar,code',
                'currentStakeholders.user:id,name',
            ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy(app()->isLocale('ar') ? 'title_ar' : 'title_en')
            ->orderBy('id');

        $records = $query->paginate(12)->withQueryString();
        $this->activeLeases->attach($records->getCollection());

        return $records;
    }

    /** @param Builder<Asset> $query */
    private function search(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';

        $query->where(function (Builder $assets) use ($like): void {
            $assets
                ->where('title_en', 'like', $like)
                ->orWhere('title_ar', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('unit_label', 'like', $like)
                ->orWhere('level_label', 'like', $like)
                ->orWhereIn(
                    'id',
                    Lease::query()
                        ->select('leaseable_id')
                        ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
                        ->where('status', 'active')
                        ->whereHas('tenantProfile.user', fn (Builder $users) => $users
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)),
                );
        });
    }
}
