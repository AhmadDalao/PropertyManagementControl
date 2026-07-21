<?php

namespace App\Modules\Portfolios\Support;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class PortfolioAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureViewer(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function canView(User $actor, Portfolio $portfolio): bool
    {
        return $actor->hasRole('superadmin')
            || ($actor->hasAnyRole(['owner', 'property_manager'])
                && $actor->portfolio_id === $portfolio->id);
    }

    public function ensureCanView(User $actor, Portfolio $portfolio): void
    {
        $this->ensureViewer($actor);
        abort_unless(
            $this->canView($actor, $portfolio),
            403,
            trans('app.errors.portfolio_access_denied'),
        );
    }

    public function canCreate(User $actor): bool
    {
        return $actor->hasRole('superadmin');
    }

    public function ensureCanCreate(User $actor): void
    {
        abort_unless($this->canCreate($actor), 403, trans('app.errors.manage_portfolio_denied'));
    }

    public function canUpdate(User $actor, Portfolio $portfolio): bool
    {
        return $actor->hasRole('superadmin')
            || ($actor->hasRole('owner') && $actor->portfolio_id === $portfolio->id);
    }

    public function ensureCanUpdate(User $actor, Portfolio $portfolio): void
    {
        abort_unless(
            $this->canUpdate($actor, $portfolio),
            403,
            trans('app.errors.manage_portfolio_denied'),
        );
    }

    public function canArchive(User $actor): bool
    {
        return $actor->hasRole('superadmin');
    }

    public function ensureCanArchive(User $actor): void
    {
        abort_unless($this->canArchive($actor), 403, trans('app.errors.manage_portfolio_denied'));
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @return Builder<Portfolio>
     */
    public function directoryScope(Builder $query, User $actor): Builder
    {
        $this->ensureViewer($actor);

        return $this->portfolios->apply($query, $actor, 'id');
    }
}
