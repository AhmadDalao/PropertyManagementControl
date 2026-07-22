<?php

namespace App\Modules\Media\Support;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

final class MediaAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureManager(User $actor): void
    {
        abort_unless($this->canCreate($actor), 403, trans('app.errors.section_access_denied'));
    }

    public function ensureGlobalManager(User $actor): void
    {
        abort_unless($actor->hasRole('superadmin'), 403, trans('app.errors.section_access_denied'));
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @return Builder<MediaFile>
     */
    public function directoryScope(Builder $query, User $actor): Builder
    {
        $this->ensureManager($actor);

        return $this->portfolios->apply($query, $actor);
    }

    public function ensureCanManage(User $actor, MediaFile $mediaFile): void
    {
        abort_unless($this->canManage($actor, $mediaFile), 403, trans('app.errors.section_access_denied'));
    }

    public function ensurePortfolio(User $actor, ?int $portfolioId): void
    {
        $this->ensureManager($actor);

        if ($portfolioId === null) {
            $this->ensureGlobalManager($actor);

            return;
        }

        $this->portfolios->ensureAccess($actor, $portfolioId);
    }

    public function canCreate(?User $actor): bool
    {
        return $actor?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    public function canManage(?User $actor, MediaFile $mediaFile): bool
    {
        if (! $this->canCreate($actor) || $actor === null) {
            return false;
        }

        if ($mediaFile->portfolio_id === null) {
            return $actor->hasRole('superadmin');
        }

        return $actor->hasRole('superadmin') || $actor->portfolio_id === $mediaFile->portfolio_id;
    }
}
