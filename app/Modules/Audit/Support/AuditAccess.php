<?php

namespace App\Modules\Audit\Support;

use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class AuditAccess
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

    public function ensureFilters(User $actor, ?int $portfolioId, ?int $causerId): void
    {
        $this->ensureManager($actor);

        if ($portfolioId !== null) {
            $this->portfolios->ensureAccess($actor, $portfolioId);
        }

        if ($causerId === null || $actor->hasRole('superadmin')) {
            return;
        }

        $causer = User::query()->findOrFail($causerId);
        $this->portfolios->ensureAccess($actor, $causer->portfolio_id);
    }
}
