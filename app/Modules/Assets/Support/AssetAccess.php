<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class AssetAccess
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssignedPropertyScope $assignments,
    ) {}

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

        return $this->assignments->assets(
            $this->portfolios->apply($query, $actor),
            $actor,
        );
    }

    public function ensureCanManage(User $actor, Asset $asset): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $asset->portfolio_id);
        abort_unless(
            $this->assignments->allowsAsset($actor, $asset),
            403,
            trans('app.errors.property_assignment_access_denied'),
        );
    }
}
