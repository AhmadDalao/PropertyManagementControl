<?php

namespace App\Modules\Reports\Support;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class ReportAccess
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

    public function ensurePortfolioFilter(User $actor, ?int $portfolioId): void
    {
        $this->ensureManager($actor);

        if ($portfolioId !== null) {
            $this->portfolios->ensureAccess($actor, $portfolioId);
        }
    }

    /** @param array<string, mixed> $filters */
    public function portfolioIdForPreset(User $actor, string $visibility, array $filters): ?int
    {
        $this->ensureManager($actor);

        if ($visibility === 'global') {
            abort_unless($actor->hasRole('superadmin'), 403, trans('app.errors.section_access_denied'));

            return null;
        }

        if (! $actor->hasRole('superadmin')) {
            return $actor->portfolio_id;
        }

        if ($visibility === 'portfolio') {
            $portfolioId = $filters['portfolio_id'] ?? null;
            abort_unless(is_int($portfolioId), 422, trans('app.errors.portfolio_required'));

            return $portfolioId;
        }

        return null;
    }

    public function canDeletePreset(User $actor, ReportPreset $preset): bool
    {
        return $actor->hasRole('superadmin')
            || $preset->user_id === $actor->id
            || (
                $actor->hasRole('owner')
                && $preset->visibility === 'portfolio'
                && $preset->portfolio_id === $actor->portfolio_id
            );
    }

    public function ensureCanDeletePreset(User $actor, ReportPreset $preset): void
    {
        $this->ensureManager($actor);
        abort_unless($this->canDeletePreset($actor, $preset), 403, trans('app.errors.section_access_denied'));
    }
}
