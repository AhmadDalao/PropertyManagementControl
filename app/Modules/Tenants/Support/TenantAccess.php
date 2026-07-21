<?php

namespace App\Modules\Tenants\Support;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class TenantAccess
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

    public function ensureCanManage(User $actor, TenantProfile $tenant): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $tenant->portfolio_id);
    }
}
