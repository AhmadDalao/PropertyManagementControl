<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class AssetAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public function directoryScope(Builder $query, User $actor): Builder
    {
        $this->ensureManager($actor);

        return $this->portfolios->apply($query, $actor);
    }

    public function ensureCanManage(User $actor, Asset $asset): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $asset->portfolio_id);
    }
}
