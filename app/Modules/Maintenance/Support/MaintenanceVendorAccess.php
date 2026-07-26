<?php

namespace App\Modules\Maintenance\Support;

use App\Models\MaintenanceVendor;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

final class MaintenanceVendorAccess
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

    public function ensureCanAccess(User $actor, MaintenanceVendor $vendor): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $vendor->portfolio_id);
    }
}
