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
        return $preset->resource === 'portfolio-report'
            && (
                $actor->hasRole('superadmin')
            || $preset->user_id === $actor->id
            || (
                $actor->hasRole('owner')
                && $preset->visibility === 'portfolio'
                && $preset->portfolio_id === $actor->portfolio_id
            )
            );
    }

    public function canViewPreset(User $actor, ReportPreset $preset): bool
    {
        if ($preset->resource !== 'portfolio-report') {
            return false;
        }

        if ($preset->user_id === $actor->id) {
            return true;
        }

        if ($preset->visibility === 'global' && $preset->portfolio_id === null) {
            return true;
        }

        return $actor->portfolio_id !== null
            && $preset->visibility === 'portfolio'
            && $preset->portfolio_id === $actor->portfolio_id;
    }

    public function canEditPreset(User $actor, ReportPreset $preset): bool
    {
        return $preset->resource === 'portfolio-report'
            && ($actor->hasRole('superadmin') || $preset->user_id === $actor->id);
    }

    public function ensureCanViewPreset(User $actor, ReportPreset $preset): void
    {
        $this->ensureManager($actor);
        abort_unless($this->canViewPreset($actor, $preset), 404);
    }

    public function ensureCanEditPreset(User $actor, ReportPreset $preset): void
    {
        $this->ensureManager($actor);
        abort_unless($this->canEditPreset($actor, $preset), 403, trans('app.errors.section_access_denied'));
    }

    public function ensureCanDeletePreset(User $actor, ReportPreset $preset): void
    {
        $this->ensureManager($actor);
        abort_unless($this->canDeletePreset($actor, $preset), 403, trans('app.errors.section_access_denied'));
    }
}
