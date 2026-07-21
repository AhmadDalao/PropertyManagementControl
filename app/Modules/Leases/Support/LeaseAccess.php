<?php

namespace App\Modules\Leases\Support;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class LeaseAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureCanAccess(User $actor, Lease $lease): void
    {
        if ($actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            $this->portfolios->ensureAccess($actor, $lease->portfolio_id);

            return;
        }

        abort_unless(
            $actor->hasRole('tenant')
                && $lease->tenantProfile()->where('user_id', $actor->id)->exists(),
            403,
            trans('app.errors.lease_access_denied')
        );
    }

    public function ensureCanManage(User $actor, Lease $lease): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $lease->portfolio_id);
    }

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied')
        );
    }
}
