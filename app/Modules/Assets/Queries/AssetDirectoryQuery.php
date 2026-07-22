<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AssetDirectoryQuery
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'status' => 'all',
            'asset_type' => 'all',
            'usage_type' => 'all',
            'occupancy_status' => 'all',
            'rentable' => 'all',
        ]);
    }

    /** @return Builder<Asset> */
    public function base(User $actor): Builder
    {
        return $this->access->directoryScope(Asset::query(), $actor);
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
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
                'level_label',
                'unit_label',
                'created_at',
            ])
            ->with([
                'portfolio:id,name_en,name_ar,showcase_dataset_id',
                'parent:id,title_en,title_ar,code',
                'currentStakeholders' => fn ($stakeholders) => $stakeholders
                    ->where('is_primary', true)
                    ->select(['id', 'asset_id', 'user_id', 'relationship_type', 'is_primary']),
                'currentStakeholders.user:id,name',
            ])
            ->withCount([
                'children',
                'leases as active_leases_count' => fn ($leases) => $leases->where('status', 'active'),
            ]);
    }

    /**
     * @param  Builder<Asset>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'asset_type', 'usage_type', 'occupancy_status'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        if (($filters['rentable'] ?? 'all') !== 'all') {
            $query->where('rentable', $filters['rentable'] === 'yes');
        }

        $this->tables->search($query, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'code',
            'level_label',
            'unit_label',
            'address',
            'address_ar',
            fn (Builder $assets, string $search, string $like) => $assets
                ->orWhere('meta_json->map->zone', 'like', $like)
                ->orWhere('meta_json->map->zone_en', 'like', $like)
                ->orWhere('meta_json->map->zone_ar', 'like', $like)
                ->orWhere('meta_json->map->land_number', 'like', $like),
            fn (Builder $assets, string $search, string $like) => $assets->orWhereHas(
                'parent',
                fn (Builder $parents) => $parents
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $assets, string $search, string $like) => $assets->orWhereHas(
                'currentStakeholders.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<Asset>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyPortfolio(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
    }
}
