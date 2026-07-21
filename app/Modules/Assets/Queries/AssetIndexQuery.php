<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\PropertyMapPresenter;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AssetIndexQuery
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly PropertyMapPresenter $propertyMap,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->filters($request);
        $baseQuery = $this->access->directoryScope(Asset::query(), $actor);
        $assets = (clone $baseQuery)
            ->with(['portfolio', 'parent', 'stakeholders.user'])
            ->withCount([
                'children',
                'leases as active_leases_count' => fn ($query) => $query->where('status', 'active'),
            ]);

        $this->applyFilters($assets, $filters);

        $mapQuery = clone $baseQuery;
        $this->tables->exact($mapQuery, $filters, 'portfolio_id');
        $countScope = clone $baseQuery;
        $this->tables->exact($countScope, $filters, 'portfolio_id');

        return [
            'assets' => $this->tables->paginate($assets, $filters, [
                'created_at',
                'title_en',
                'code',
                'asset_type',
                'usage_type',
                'status',
                'occupancy_status',
                'valuation_amount',
            ]),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts($countScope, ['active', 'inactive', 'archived'], $filters),
            'insights' => $this->insights($baseQuery, $filters),
            'propertyMap' => $this->propertyMap->forQuery($mapQuery),
            'portfolioOptions' => $this->portfolios->options($actor),
        ];
    }

    /** @return Builder<Asset> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->filters($request);
        $assets = $this->access
            ->directoryScope(Asset::query(), $actor)
            ->with(['portfolio', 'parent']);
        $this->applyFilters($assets, $filters);

        return $assets;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'status' => 'all',
            'asset_type' => 'all',
            'usage_type' => 'all',
            'occupancy_status' => 'all',
            'rentable' => 'all',
        ]);
    }

    /**
     * @param  Builder<Asset>  $assets
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $assets, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'asset_type', 'usage_type', 'occupancy_status'] as $filter) {
            $this->tables->exact($assets, $filters, $filter);
        }

        if (($filters['rentable'] ?? 'all') !== 'all') {
            $assets->where('rentable', $filters['rentable'] === 'yes');
        }

        $this->tables->search($assets, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'code',
            'level_label',
            'unit_label',
            'address',
            'address_ar',
            fn (Builder $query, string $search, string $like) => $query
                ->orWhere('meta_json->map->zone', 'like', $like)
                ->orWhere('meta_json->map->zone_en', 'like', $like)
                ->orWhere('meta_json->map->zone_ar', 'like', $like)
                ->orWhere('meta_json->map->land_number', 'like', $like),
            fn (Builder $query, string $search, string $like) => $query->orWhereHas(
                'parent',
                fn (Builder $parents) => $parents
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $query, string $search, string $like) => $query->orWhereHas(
                'stakeholders.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<Asset>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float>
     */
    private function insights(Builder $baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        $this->tables->exact($query, $filters, 'portfolio_id');
        $totalAssets = (clone $query)->count();
        $rentableAssets = (clone $query)->where('rentable', true)->count();
        $vacantRentableAssets = (clone $query)
            ->where('rentable', true)
            ->where('occupancy_status', 'vacant')
            ->count();
        $occupiedRentableAssets = (clone $query)
            ->where('rentable', true)
            ->whereIn('occupancy_status', ['occupied', 'partially_occupied'])
            ->count();
        $assignmentScope = (clone $query)->whereNull('parent_id');

        return [
            'total_assets' => $totalAssets,
            'total_value' => (float) (clone $query)->sum('valuation_amount'),
            'rentable_assets' => $rentableAssets,
            'vacant_rentable_assets' => $vacantRentableAssets,
            'occupied_assets' => (clone $query)->where('occupancy_status', 'occupied')->count(),
            'maintenance_assets' => (clone $query)->where('occupancy_status', 'maintenance')->count(),
            'buildings' => (clone $query)->where('asset_type', 'building')->count(),
            'floors' => (clone $query)->where('asset_type', 'floor')->count(),
            'units' => (clone $query)->where('asset_type', 'unit')->count(),
            'spaces' => (clone $query)->where('asset_type', 'space')->count(),
            'missing_owner' => (clone $assignmentScope)->whereDoesntHave(
                'stakeholders',
                fn ($stakeholderQuery) => $stakeholderQuery
                    ->where('relationship_type', 'owner')
                    ->where('is_primary', true)
            )->count(),
            'missing_manager' => (clone $assignmentScope)->whereDoesntHave(
                'stakeholders',
                fn ($stakeholderQuery) => $stakeholderQuery
                    ->where('relationship_type', 'manager')
                    ->where('is_primary', true)
            )->count(),
            'rentable_occupancy_rate' => $rentableAssets > 0
                ? round(($occupiedRentableAssets / $rentableAssets) * 100, 1)
                : 0,
        ];
    }
}
