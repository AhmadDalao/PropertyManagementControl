<?php

namespace App\Modules\Media\Support;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class MediaAccess
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

    public function ensureGlobalManager(User $actor): void
    {
        abort_unless(
            $actor->hasRole('superadmin'),
            403,
            trans('app.errors.section_access_denied'),
        );
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
        $this->ensureManager($actor);

        if ($mediaFile->portfolio_id === null) {
            $this->ensureGlobalManager($actor);

            return;
        }

        $this->portfolios->ensureAccess($actor, $mediaFile->portfolio_id);
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
}
