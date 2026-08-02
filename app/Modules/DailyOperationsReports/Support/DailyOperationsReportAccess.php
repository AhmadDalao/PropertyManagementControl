<?php

namespace App\Modules\DailyOperationsReports\Support;

use App\Models\DailyOperationsReportRun;
use App\Models\User;

final class DailyOperationsReportAccess
{
    public function ensureArchiveActor(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function ensureCanAccess(User $actor, DailyOperationsReportRun $run): void
    {
        $this->ensureArchiveActor($actor);

        if ($actor->hasRole('superadmin')) {
            return;
        }

        abort_unless(
            $run->portfolio_id !== null
            && $run->portfolio_id === $actor->portfolio_id,
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function portfolioId(User $actor, ?int $requestedPortfolioId): ?int
    {
        $this->ensureArchiveActor($actor);

        if ($actor->hasRole('owner')) {
            abort_unless($actor->portfolio_id !== null, 403);

            return $actor->portfolio_id;
        }

        return $requestedPortfolioId;
    }
}
