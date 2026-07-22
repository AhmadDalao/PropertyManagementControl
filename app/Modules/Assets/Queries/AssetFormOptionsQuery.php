<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Data\AssetFormData;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssetFormOptionsQuery
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly AssetHierarchy $hierarchy,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function get(User $actor, ?Asset $asset = null, array $defaults = []): AssetFormData
    {
        $asset
            ? $this->access->ensureCanManage($actor, $asset)
            : $this->access->ensureManager($actor);
        $portfolioOptions = $this->portfolios->options($actor);
        $portfolioId = $this->selectedPortfolioId($actor, $asset, $defaults, $portfolioOptions);
        $excludedParentIds = $asset
            ? $this->hierarchy->descendantIdsIncluding($asset)
            : [];
        $asset?->loadMissing('currentStakeholders');

        return new AssetFormData(
            portfolioId: $portfolioId,
            portfolios: $portfolioOptions,
            parents: Asset::query()
                ->where('portfolio_id', $portfolioId)
                ->where('status', '!=', 'archived')
                ->when($excludedParentIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedParentIds))
                ->orderBy('title_en')
                ->get(['id', 'title_en', 'title_ar', 'code', 'asset_type']),
            owners: $this->users($portfolioId, ['owner']),
            managers: $this->users($portfolioId, ['owner', 'property_manager']),
            ownerId: $asset?->currentStakeholders->firstWhere('relationship_type', 'owner')?->user_id,
            managerId: $asset?->currentStakeholders->firstWhere('relationship_type', 'manager')?->user_id,
        );
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<int, array{id:int,name:string}>  $options
     */
    private function selectedPortfolioId(User $actor, ?Asset $asset, array $defaults, array $options): int
    {
        $availableIds = array_column($options, 'id');
        $requested = (int) (($asset ? $asset->portfolio_id : null)
            ?? $defaults['portfolio_id']
            ?? $actor->portfolio_id
            ?? 0);

        return in_array($requested, $availableIds, true)
            ? $requested
            : (int) ($availableIds[0] ?? 0);
    }

    /**
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    private function users(int $portfolioId, array $roles): Collection
    {
        return User::query()
            ->where('portfolio_id', $portfolioId)
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', $roles))
            ->orderBy('name')
            ->get(['id', 'name', 'status']);
    }
}
