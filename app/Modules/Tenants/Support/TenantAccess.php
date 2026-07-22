<?php

namespace App\Modules\Tenants\Support;

use App\Models\TenantProfile;
use App\Models\User;

class TenantAccess
{
    public function canManageSection(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    public function canManage(User $actor, TenantProfile $tenant): bool
    {
        return $this->canManageSection($actor)
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $tenant->portfolio_id);
    }

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $this->canManageSection($actor),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function ensureCanManage(User $actor, TenantProfile $tenant): void
    {
        abort_unless(
            $this->canManage($actor, $tenant),
            403,
            trans('app.errors.portfolio_access_denied'),
        );
    }
}
