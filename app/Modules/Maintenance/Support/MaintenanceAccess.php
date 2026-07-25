<?php

namespace App\Modules\Maintenance\Support;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;

class MaintenanceAccess
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    public function ensureCanAccess(User $actor, MaintenanceRequest $request): void
    {
        if ($actor->hasRole('tenant')) {
            abort_unless(
                $request->tenantProfile()->where('user_id', $actor->id)->exists(),
                403,
                trans('app.errors.section_access_denied')
            );

            return;
        }

        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $request->portfolio_id);
        abort_unless(
            $this->assignments->allowsMaintenance($actor, $request),
            403,
            trans('app.errors.property_assignment_access_denied'),
        );
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
